<?php
namespace Bolt\Boltpay\Plugin\Magento\Checkout\CustomerData;

use Bolt\Boltpay\Helper\Api as ApiHelper;
use Magento\Checkout\CustomerData\Cart as CustomerDataCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Bolt\Boltpay\Helper\Cart as BoltHelperCart;
use Bolt\Boltpay\Helper\Bugsnag;
use Bolt\Boltpay\Helper\FeatureSwitch\Decider;
use Bolt\Boltpay\Helper\Config as ConfigHelper;
use Magento\Framework\DataObjectFactory;
use Magento\Framework\Serialize\SerializerInterface as Serializer;
use Magento\Framework\App\CacheInterface;
use Magento\Sales\Model\Order;

/**
 * Process quote bolt data
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Cart
{
    private const HINTS_TYPE = 'cart';

    private const PRE_FETCH_CART_CUSTOMER_DATA_CACHE_TAG = 'PRE_FETCH_CART_CUSTOMER_DATA_CACHE_TAG';

    private const PRE_FETCH_CART_CUSTOMER_DATA_CACHE_LIFETIME = 3600; // one hour

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var BoltHelperCart
     */
    private $boltHelperCart;

    /**
     * @var Bugsnag
     */
    private $bugsnag;

    /**
     * @var Decider
     */
    private $featureSwitches;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @param CheckoutSession $checkoutSession
     * @param BoltHelperCart $boltHelperCart
     * @param Bugsnag $bugsnag
     * @param Decider $featureSwitches
     * @param ConfigHelper $configHelper
     * @param DataObjectFactory $dataObjectFactory
     * @param ApiHelper $apiHelper
     * @param Serializer $serializer
     * @param CacheInterface $cache
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        BoltHelperCart $boltHelperCart,
        Bugsnag $bugsnag,
        Decider $featureSwitches,
        ConfigHelper $configHelper,
        DataObjectFactory $dataObjectFactory,
        ApiHelper $apiHelper,
        Serializer $serializer,
        CacheInterface $cache
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->boltHelperCart = $boltHelperCart;
        $this->bugsnag = $bugsnag;
        $this->featureSwitches = $featureSwitches;
        $this->configHelper = $configHelper;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->apiHelper = $apiHelper;
        $this->serializer = $serializer;
        $this->cache = $cache;
    }

    /**
     * Add bolt data to result
     *
     * @param CustomerDataCart $subject
     * @param array $result
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSectionData(CustomerDataCart $subject, array $customerData)
    {
        if (!$this->featureSwitches->isEnabledFetchCartViaApi() || !$this->configHelper->isActive()) {
            return $customerData;
        }
        $quote = $this->getQuote();
        $customerData['quoteMaskedId'] = null;
        $customerData['boltCartHints'] = null;
        if ($quote->getId()) {
            try {
                $order = $this->boltHelperCart->getOrderByQuoteId($quote->getId());
                if ($order && $order->getStatus() != Order::STATE_PENDING_PAYMENT) {
                    $this->boltHelperCart->deactivateSessionQuote($quote);
                    return $customerData;
                }
                $customerData['quoteMaskedId'] = $this->boltHelperCart->getQuoteMaskedId((int)$quote->getId());
                $customerData['boltCartHints'] = $this->boltHelperCart->getHints($quote->getId(), self::HINTS_TYPE);
                $this->preFetchCart($quote, $customerData);
            } catch (\Exception $e) {
                $this->bugsnag->notifyException($e);
            }
        }
        return $customerData;
    }

    /**
     * Get active quote
     *
     * @return Quote
     */
    private function getQuote()
    {
        return $this->checkoutSession->getQuote();
    }

    /**
     * Pre-fetch cart main thread
     *
     * @param Quote $quote
     * @param array $customerData
     * @return void
     * @throws LocalizedException
     */
    private function preFetchCart(Quote $quote, array $customerData): void
    {
        if (!$this->featureSwitches->isEnabledPreFetchCartViaApi()) {
            return;
        }
        $customerDataHash = $this->getCustomerDataHash($customerData);
        try {
            // makes bolt pre-fetch cart request if customer-data is not in cache
            if (!$this->getCustomerDataHashFromCache($customerDataHash)) {
                $this->preFetchCartBoltRequest($quote, $customerData);
                $this->saveCustomerDataHashToCache($customerDataHash);
            }
        } catch (\Exception $e) {
            $this->bugsnag->notifyException($e);
        }
    }
    /**
     * Make pre fetch cart data to Bolt
     *
     * @param Quote $quote
     * @param array $customerData
     * @return void
     * @throws AlreadyExistsException
     * @throws LocalizedException
     * @throws \Zend_Http_Client_Exception
     */
    private function preFetchCartBoltRequest(Quote $quote, array $customerData): void
    {
        $apiKey = $this->configHelper->getApiKey($quote->getStoreId());
        $publishableKey = $this->configHelper->getPublishableKeyCheckout($quote->getStoreId());
        if (!$apiKey || !$publishableKey) {
            throw new LocalizedException(
                __('Bolt API Key or Publishable Key - Multi Step is not configured')
            );
        }
        $requestData = $this->dataObjectFactory->create();
        $requestData->setDynamicApiUrl(ApiHelper::API_PRE_FETCH_CART)
            ->setApiKey($apiKey)
            ->setStatusOnly(true)
            ->setRequestMethod('POST')
            ->setHeaders(
                [
                    'X-Publishable-Key' => $publishableKey
                ]
            )->setApiData(
                [
                    'order_reference' => $this->boltHelperCart->getQuoteMaskedId((int)$quote->getId())
                ]
            );
        $request = $this->apiHelper->buildRequest($requestData);
        $this->apiHelper->sendRequest($request);
    }

    /**
     * Returns uniq hash of customer data
     *
     * @param array $customerData
     * @return string
     */
    private function getCustomerDataHash(array $customerData): string
    {
        return hash('md5', $this->serializer->serialize($customerData));
    }

    /**
     * Save customer data to cache
     *
     * @param string $customerDataHash
     * @return bool
     */
    private function saveCustomerDataHashToCache(string $customerDataHash): bool
    {
        return $this->cache->save(
            'true',
            $customerDataHash, [self::PRE_FETCH_CART_CUSTOMER_DATA_CACHE_TAG],
            self::PRE_FETCH_CART_CUSTOMER_DATA_CACHE_LIFETIME
        );
    }

    /**
     * Returns the customer data from cache
     *
     * @param string $customerDataHash
     * @return string|null
     */
    private function getCustomerDataHashFromCache(string $customerDataHash): ?string
    {
        return $this->cache->load($customerDataHash);
    }
}
