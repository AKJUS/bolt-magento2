<?php
/**
 * Bolt magento2 plugin
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Bolt
 * @package    Bolt_Boltpay
 * @copyright  Copyright (c) 2017-2024 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\Boltpay\Model\Api;

use Bolt\Boltpay\Api\Data\ShippingOptionsInterface;
use Bolt\Boltpay\Api\ShippingMethodsInterface;
use Bolt\Boltpay\Helper\Hook as HookHelper;
use Bolt\Boltpay\Helper\Cart as CartHelper;
use Magento\Directory\Model\Region as RegionModel;
use Magento\Framework\Exception\LocalizedException;
use Bolt\Boltpay\Api\Data\ShippingOptionsInterfaceFactory;
use Bolt\Boltpay\Api\Data\ShippingTaxInterfaceFactory;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Bolt\Boltpay\Api\Data\ShippingOptionInterface;
use Bolt\Boltpay\Api\Data\ShippingOptionInterfaceFactory;
use Bolt\Boltpay\Helper\Bugsnag;
use Bolt\Boltpay\Helper\MetricsClient;
use Bolt\Boltpay\Helper\Log as LogHelper;
use Bolt\Boltpay\Helper\Shared\CurrencyUtils;
use Magento\Framework\Webapi\Rest\Response;
use Bolt\Boltpay\Helper\Config as ConfigHelper;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Bolt\Boltpay\Model\ErrorResponse as BoltErrorResponse;
use Bolt\Boltpay\Helper\Session as SessionHelper;
use Bolt\Boltpay\Exception\BoltException;
use Bolt\Boltpay\Helper\Discount as DiscountHelper;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Framework\Serialize\SerializerInterface as Serialize;
use Bolt\Boltpay\Model\EventsForThirdPartyModules;

/**
 * Class ShippingMethods
 * Shipping and Tax hook endpoint. Get shipping methods using shipping address and cart details
 *
 * @package Bolt\Boltpay\Model\Api
 */
class ShippingMethods implements ShippingMethodsInterface
{
    const NO_SHIPPING_SERVICE = 'No Shipping Required';
    const NO_SHIPPING_REFERENCE = 'noshipping';
    const BOLT_SHIPPING_TAX_CACHE_TAG = 'BOLT_SHIPPING_TAX_CACHE_TAG';

    const E_BOLT_CUSTOM_ERROR = 6103;
    const E_BOLT_GENERAL_ERROR = 6009;

    /**
     * @var HookHelper
     */
    private $hookHelper;

    /**
     * @var CartHelper
     */
    private $cartHelper;

    /**
     * @var RegionModel
     */
    private $regionModel;

    /**
     * @var ShippingOptionsInterfaceFactory
     */
    private $shippingOptionsInterfaceFactory;

    /**
     * @var ShippingTaxInterfaceFactory
     */
    private $shippingTaxInterfaceFactory;

    /**
     * Shipping method converter
     *
     * @var ShippingMethodConverter
     */
    private $converter;

    /**
     * @var ShippingOptionInterfaceFactory
     */
    private $shippingOptionInterfaceFactory;

    /**
     * @var Bugsnag
     */
    private $bugsnag;

    /**
     * @var MetricsClient
     */
    private $metricsClient;

    /**
     * @var LogHelper
     */
    private $logHelper;

    /**
     * @var BoltErrorResponse
     */
    private $errorResponse;

    /**
     * @var Response
     */
    private $response;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var PriceHelper
     */
    private $priceHelper;

    /** @var SessionHelper */
    private $sessionHelper;

    /** @var DiscountHelper */
    private $discountHelper;

    // Totals adjustment threshold
    private $threshold = 1;

    private $taxAdjusted = false;

    /** @var Quote */
    protected $quote;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    /**
     * @var Serialize
     */
    private $serialize;

    protected $_oldShippingAddress;

    /**
     * @var EventsForThirdPartyModules
     */
    private $eventsForThirdPartyModules;

    /**
     * Assigns local references to global resources
     *
     * @param HookHelper                      $hookHelper
     * @param RegionModel                     $regionModel
     * @param ShippingOptionsInterfaceFactory $shippingOptionsInterfaceFactory
     * @param ShippingTaxInterfaceFactory     $shippingTaxInterfaceFactory
     * @param CartHelper                      $cartHelper
     * @param ShippingMethodConverter         $converter
     * @param ShippingOptionInterfaceFactory  $shippingOptionInterfaceFactory
     * @param Bugsnag                         $bugsnag
     * @param MetricsClient                   $metricsClient
     * @param LogHelper                       $logHelper
     * @param BoltErrorResponse               $errorResponse
     * @param Response                        $response
     * @param ConfigHelper                    $configHelper
     * @param Request                         $request
     * @param CacheInterface                  $cache
     * @param PriceHelper                     $priceHelper
     * @param SessionHelper                   $sessionHelper
     * @param DiscountHelper                  $discountHelper
     * @param RuleFactory                     $ruleFactory
     * @param Serialize                       $serialize
     * @param EventsForThirdPartyModules      $eventsForThirdPartyModules
     */
    public function __construct(
        HookHelper $hookHelper,
        RegionModel $regionModel,
        ShippingOptionsInterfaceFactory $shippingOptionsInterfaceFactory,
        ShippingTaxInterfaceFactory $shippingTaxInterfaceFactory,
        CartHelper $cartHelper,
        ShippingMethodConverter $converter,
        ShippingOptionInterfaceFactory $shippingOptionInterfaceFactory,
        Bugsnag $bugsnag,
        MetricsClient $metricsClient,
        LogHelper $logHelper,
        BoltErrorResponse $errorResponse,
        Response $response,
        ConfigHelper $configHelper,
        Request $request,
        CacheInterface $cache,
        PriceHelper $priceHelper,
        SessionHelper $sessionHelper,
        DiscountHelper $discountHelper,
        RuleFactory $ruleFactory,
        Serialize $serialize,
        EventsForThirdPartyModules $eventsForThirdPartyModules
    ) {
        $this->hookHelper = $hookHelper;
        $this->cartHelper = $cartHelper;
        $this->regionModel = $regionModel;
        $this->shippingOptionsInterfaceFactory = $shippingOptionsInterfaceFactory;
        $this->shippingTaxInterfaceFactory = $shippingTaxInterfaceFactory;
        $this->converter = $converter;
        $this->shippingOptionInterfaceFactory = $shippingOptionInterfaceFactory;
        $this->bugsnag = $bugsnag;
        $this->metricsClient = $metricsClient;
        $this->logHelper = $logHelper;
        $this->errorResponse = $errorResponse;
        $this->response = $response;
        $this->configHelper = $configHelper;
        $this->request = $request;
        $this->cache = $cache;
        $this->priceHelper = $priceHelper;
        $this->sessionHelper = $sessionHelper;
        $this->discountHelper = $discountHelper;
        $this->ruleFactory = $ruleFactory;
        $this->serialize = $serialize;
        $this->eventsForThirdPartyModules = $eventsForThirdPartyModules;
    }

    /**
     * Check if cart items data has changed by comparing
     * SKUs, quantities and totals in quote and received cart data.
     * Also checks an empty cart / quote case.
     * A cart can hold multiple items with the same SKU, therefore
     * the quantities and totals are matches separately.
     *
     * @param array $cart cart details
     * @throws LocalizedException
     */
    protected function checkCartItems($cart)
    {
        $cartItems = ['quantity' => [], 'total' => []];
        foreach ($cart['items'] as $item) {
            $sku = $item['sku'];
            if (!isset($cartItems['quantity'][$sku])) {
                $cartItems['quantity'][$sku] = 0;
            }
            if (!isset($cartItems['total'][$sku])) {
                $cartItems['total'][$sku] = 0;
            }
            $cartItems['quantity'][$sku] += $item['quantity'];
            $cartItems['total'][$sku] += $item['total_amount'];
        }

        $quoteItems = ['quantity' => [], 'total' => []];
        foreach ($this->quote->getAllVisibleItems() as $item) {
            $sku = $this->cartHelper->getSkuFromQuoteItem($item);
            $quantity = round($item->getQty());
            $unitPrice = round($item->getCalculationPrice(), 2);
            if (!isset($quoteItems['quantity'][$sku])) {
                $quoteItems['quantity'][$sku] = 0;
            }
            if (!isset($quoteItems['total'][$sku])) {
                $quoteItems['total'][$sku] = 0;
            }
            $quoteItems['quantity'][$sku] += $quantity;
            $quoteItems['total'][$sku] += CurrencyUtils::toMinor($unitPrice * $quantity, $this->quote->getQuoteCurrencyCode());
        }

        $total = $this->quote->getTotals();
        if (isset($total['giftwrapping']) && ($total['giftwrapping']->getGwId() || $total['giftwrapping']->getGwItemIds())) {
            $giftWrapping = $total['giftwrapping'];
            $sku = trim($giftWrapping->getCode());
            if (!isset($quoteItems['quantity'][$sku])) {
                $quoteItems['quantity'][$sku] = 0;
            }
            if (!isset($quoteItems['total'][$sku])) {
                $quoteItems['total'][$sku] = 0;
            }
            if ($total['giftwrapping']->getGwId()) {
                $quoteItems['quantity'][$sku] += 1;
            }
            if ($total['giftwrapping']->getGwItemIds()) {
                $quoteItems['quantity'][$sku] += count($total['giftwrapping']->getGwItemIds());
            }
            $quoteItems['total'][$sku] += CurrencyUtils::toMinor($giftWrapping->getGwPrice() + $giftWrapping->getGwItemsPrice() + $giftWrapping->getGwCardPrice(), $this->quote->getQuoteCurrencyCode());
        }

        if (!$quoteItems['quantity'] && !$quoteItems['total']) {
            throw new BoltException(
                __('The cart is empty. Please reload the page and checkout again.'),
                null,
                self::E_BOLT_CUSTOM_ERROR
            );
        }

        if ($cartItems['quantity'] != $quoteItems['quantity'] || $cartItems['total'] != $quoteItems['total']) {
            $this->bugsnag->registerCallback(function ($report) use ($cart, $cartItems, $quoteItems) {

                list($quoteItemData) = $this->cartHelper->getCartItems(
                    $this->quote,
                    $this->quote->getStoreId()
                );

                $report->setMetaData([
                    'CART_MISMATCH' => [
                        'cart_total' => $cartItems['total'],
                        'quote_total' => $quoteItems['total'],
                        'cart_items' => $cart['items'],
                        'quote_items' => $quoteItemData,
                    ]
                ]);
            });
            if ($cartItems['quantity'] != $quoteItems['quantity']) {
                throw new BoltException(
                    __('The quantity of items in your cart has changed and needs to be revised. Please reload the page and checkout again.'),
                    null,
                    self::E_BOLT_CUSTOM_ERROR
                );
            } else {
                throw new BoltException(
                    __('Your cart total has changed and needs to be revised. Please reload the page and checkout again.'),
                    null,
                    self::E_BOLT_CUSTOM_ERROR
                );
            }
        }
    }

    /**
     * Validate request address
     *
     * @param $addressData
     * @throws BoltException
     */
    private function validateAddressData($addressData)
    {
        $this->validateEmail($addressData['email']);
    }

    /**
     * Validate request email
     *
     * @param $email
     * @throws BoltException
     */
    private function validateEmail($email)
    {
        if (!$this->cartHelper->validateEmail($email)) {
            throw new BoltException(
                __('Invalid email: %1', $email),
                null,
                BoltErrorResponse::ERR_UNIQUE_EMAIL_REQUIRED
            );
        }
    }

    /**
     * Get all available shipping methods and tax data.
     *
     * @api
     *
     * @param array $cart cart details
     * @param array $shipping_address shipping address
     *
     * @return ShippingOptionsInterface|void
     * @throws \Exception
     */
    public function getShippingMethods($cart, $shipping_address)
    {
        $startTime = $this->metricsClient->getCurrentTime();
        try {
            $shippingAndTax = $this->getShippingAndTax($cart, $shipping_address);
            $this->metricsClient->processMetric("ship_tax.success", 1, "ship_tax.latency", $startTime);
            return $shippingAndTax;
        } catch (\Magento\Framework\Webapi\Exception $e) {
            $this->metricsClient->processMetric("ship_tax.failure", 1, "ship_tax.latency", $startTime);
            $this->catchExceptionAndSendError($e, $e->getMessage(), $e->getCode(), $e->getHttpCode());
        } catch (BoltException $e) {
            $this->metricsClient->processMetric("ship_tax.failure", 1, "ship_tax.latency", $startTime);
            $msg = substr($e->getMessage(), 0, 16) === "Unknown quote id" ?
                'Something went wrong with your cart. Please reload the page and checkout again.' :
                $e->getMessage();
            $this->catchExceptionAndSendError($e, $msg, $e->getCode());
        } catch (\Exception $e) {
            $this->metricsClient->processMetric("ship_tax.failure", 1, "ship_tax.latency", $startTime);
            $msg = __('Unprocessable Entity') . ': ' . $e->getMessage();
            $this->catchExceptionAndSendError($e, $msg, self::E_BOLT_GENERAL_ERROR, 422);
        }
    }

    /**
     * Get shipping and tax options
     *
     * @param array $cart
     * @param array $shipping_address
     * @return ShippingOptionsInterface
     * @throws BoltException
     */
    public function getShippingAndTax($cart, $shipping_address)
    {
        if (isset($cart['currency'])) {
            $this->cartHelper->setCurrentCurrencyCode($cart['currency']);
        }
        $cart = $this->eventsForThirdPartyModules->runFilter('filterCartBeforeLegacyShippingAndTax', $cart);
        // get immutable quote id stored with transaction
        $immutableQuoteId = $this->cartHelper->getImmutableQuoteIdFromBoltCartArray($cart);
        $immutableQuote = $this->getQuoteById($immutableQuoteId);
        if (!$immutableQuote) {
            throw new BoltException(
                __('Unknown quote id: %1.', $immutableQuoteId),
                null,
                self::E_BOLT_CUSTOM_ERROR
            );
        }

        if (!$immutableQuote->getCustomerId()
            && $this->cartHelper->getFeatureSwitchDeciderHelper()->isSetCustomerNameToOrderForGuests()) {
            $immutableQuote->addData(
                [
                    \Magento\Sales\Api\Data\OrderInterface::CUSTOMER_FIRSTNAME => $shipping_address['first_name'],
                    \Magento\Sales\Api\Data\OrderInterface::CUSTOMER_LASTNAME  => $shipping_address['last_name'],
                ]
            );
        }

        $this->preprocessHook($immutableQuote->getStoreId());

        $parentQuoteId = $cart['order_reference'];
        $parentQuote = $this->getQuoteById($parentQuoteId);
        $this->cartHelper->replicateQuoteData($immutableQuote, $parentQuote);
        $this->quote = $parentQuote;
        $this->quote->getStore()->setCurrentCurrencyCode($this->quote->getQuoteCurrencyCode());
        $this->checkCartItems($cart);
        // Load logged in customer checkout and customer sessions from cached session id.
        // Replace parent quote with immutable quote in checkout session.
        $this->sessionHelper->loadSession($this->quote, $cart['metadata'] ?? []);

        $addressData = $this->cartHelper->handleSpecialAddressCases($shipping_address);

        if (isset($addressData['email']) && $addressData['email'] !== null) {
            $this->validateAddressData($addressData);
        }

        $shippingOptionsModel = $this->shippingEstimation($this->quote, $addressData);

        if ($this->taxAdjusted) {
            $this->bugsnag->registerCallback(function ($report) use ($shippingOptionsModel) {
                $report->setMetaData([
                    'SHIPPING OPTIONS' => [print_r($shippingOptionsModel, 1)]
                ]);
            });
            $this->bugsnag->notifyError('Cart Totals Mismatch', "Totals adjusted.");
        }

        /** @var \Magento\Quote\Model\Quote $parentQuote */
        $parentQuote = $this->getQuoteById($cart['order_reference']);
        if ($this->couponInvalidForShippingAddress($parentQuote->getCouponCode())) {
            $address = $parentQuote->isVirtual() ? $parentQuote->getBillingAddress() : $parentQuote->getShippingAddress();
            $additionalAmount = abs(CurrencyUtils::toMinor($address->getDiscountAmount(), $parentQuote->getQuoteCurrencyCode()));

            $shippingOptionsModel->addAmountToShippingOptions($additionalAmount);
        }

        return $shippingOptionsModel;
    }

    /**
     * @param        $exception
     * @param string $msg
     * @param int    $code
     * @param int    $httpStatusCode
     */
    protected function catchExceptionAndSendError($exception, $msg = '', $code = self::E_BOLT_GENERAL_ERROR, $httpStatusCode = 422)
    {
        $this->bugsnag->notifyException($exception);

        $this->sendErrorResponse($code, $msg, $httpStatusCode);
    }

    /**
     * @param $quoteId
     * @throws LocalizedException
     */
    protected function throwUnknownQuoteIdException($quoteId)
    {
        throw new LocalizedException(
            __('Unknown quote id: %1.', $quoteId)
        );
    }

    /**
     * @param $quoteId
     * @return \Magento\Quote\Api\Data\CartInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuoteById($quoteId)
    {
        return $this->cartHelper->getQuoteById($quoteId);
    }

    /**
     * @throws LocalizedException
     * @throws \Magento\Framework\Webapi\Exception
     */
    protected function preprocessHook($storeId)
    {
        HookHelper::$fromBolt = true;
        $this->hookHelper->preProcessWebhook($storeId);
    }

    /**
     * Fetch and apply external quote data, not stored within a quote or totals (third party modules DB tables)
     * If data is applied it is used as a part of the cache identifier.
     *
     * @param Quote $quote
     * @return string
     */
    public function applyExternalQuoteData($quote)
    {
        $data = '';
        $this->discountHelper->applyExternalDiscountData($quote);
        if ($quote->getAmrewardsPoint()) {
            $data .= $quote->getAmrewardsPoint();
        }

        return $this->eventsForThirdPartyModules->runFilter('filterApplyExternalQuoteData', $data, $quote);
    }

    /**
     * Get Shipping and Tax from cache or run the Shipping options collection routine, store it in cache and return.
     *
     * @param Quote $quote
     * @param array $addressData
     *
     * @return ShippingOptionsInterface
     * @throws LocalizedException
     */
    public function shippingEstimation($quote, $addressData)
    {
        // Take into account external data applied to quote in thirt party modules
        $externalData = $this->applyExternalQuoteData($quote);
        $regionName = $addressData['region'] ?? null;
        $countryCode = $addressData['country_code'] ?? null;
        $postalCode = $addressData['postal_code'] ?? null;
        $locality = $addressData['locality'] ?? null;
        $streetAddress1 = $addressData['street_address1'] ?? null;
        $streetAddress2 = $addressData['street_address2'] ?? null;
        $email = $addressData['email'] ?? null;
        $company = $addressData['company'] ?? null;


        ////////////////////////////////////////////////////////////////////////////////////////
        // Check cache storage for estimate. If the quote_id, total_amount, items, country_code,
        // applied rules (discounts), region and postal_code match then use the cached version.
        ////////////////////////////////////////////////////////////////////////////////////////
        if ($prefetchShipping = $this->configHelper->getPrefetchShipping($quote->getStoreId())) {
            // use parent quote id for caching.
            // if everything else matches the cache is used more efficiently this way
            $parentQuoteId = $quote->getBoltParentQuoteId();

            $cacheIdentifier = $parentQuoteId.'_'.round($quote->getSubtotal()*100).'_'.
                $countryCode. '_'.$regionName.'_'.$postalCode. '_'.
                $streetAddress1.'_'.$streetAddress2.'_'.$externalData;

            // include products in cache key
            foreach ($quote->getAllVisibleItems() as $item) {
                $cacheIdentifier .= '_'.$this->cartHelper->getSkuFromQuoteItem($item).'_'.$item->getQty();
            }

            // include applied rule ids (discounts) in cache key
            $appliedRuleIds = $quote->getAppliedRuleIds();
            if ($appliedRuleIds) {
                $ruleIds = str_replace(',', '_', $appliedRuleIds);
                if ($ruleIds) {
                    $cacheIdentifier .= '_'.$ruleIds;
                }
            }

            // extend cache identifier with custom address fields
            $cacheIdentifier .= $this->cartHelper->convertCustomAddressFieldsToCacheIdentifier($quote);

            $cacheIdentifier = hash('md5', $cacheIdentifier);

            if ($serialized = $this->cache->load($cacheIdentifier)) {
                $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();
                $address->setShippingMethod(null)->save();
                try {
                    $shippingOptionsData = $this->serialize->unserialize($serialized);
                    //re-create the object expected as the return
                    return $this->createShippingOptionsModelFromArray(
                        $shippingOptionsData['shipping_options'],
                        $shippingOptionsData['tax_result']['amount']
                    );
                } catch (\InvalidArgumentException $e) {
                    $this->bugsnag->notifyException($e);
                }
            }
        }
        ////////////////////////////////////////////////////////////////////////////////////////

        // Get region id
        $region = $this->regionModel->loadByName($regionName, $countryCode);

        // Accept valid email or an empty variable (when run from prefetch controller)
        if ($email) {
            $this->validateEmail($email);
        }

        // Reformat address data
        $addressData = [
            'country_id' => $countryCode,
            'postcode'   => $postalCode,
            'region'     => $regionName,
            'region_id'  => $region ? $region->getId() : null,
            'city'       => $locality,
            'street'     => trim($streetAddress1 . "\n" . $streetAddress2),
            'email'      => $email,
            'company'    => $company
        ];

        foreach ($addressData as $key => $value) {
            if (empty($value)) {
                unset($addressData[$key]);
            }
        }

        $shippingMethods = $this->getShippingOptions($quote, $addressData);

        $shippingOptionsModel = $this->getShippingOptionsData($shippingMethods);

        // Cache the calculated result
        if ($prefetchShipping) {
            $this->cache->save($this->serialize->serialize($shippingOptionsModel), $cacheIdentifier, [self::BOLT_SHIPPING_TAX_CACHE_TAG], 3600);
        }

        return $shippingOptionsModel;
    }

    /**
     * Set shipping methods to the ShippingOptions object
     *
     * @param $shippingMethods
     */
    protected function getShippingOptionsData($shippingMethods)
    {
        $shippingOptionsModel = $this->shippingOptionsInterfaceFactory->create();

        $shippingTaxModel = $this->shippingTaxInterfaceFactory->create();
        $shippingTaxModel->setAmount(0);

        $shippingOptionsModel->setShippingOptions($shippingMethods);
        $shippingOptionsModel->setTaxResult($shippingTaxModel);

        return $shippingOptionsModel;
    }

    /**
     * Reset shipping calculation
     *
     * On some store setups shipping prices are conditionally changed
     * depending on some custom logic. If it is done as a plugin for
     * some method in the Magento shipping flow, then that method
     * may be (indirectly) called from our Shipping And Tax flow more
     * than once, resulting in wrong prices. This function resets
     * address shipping calculation but can seriously slow down the
     * process (on a system with many shipping options available).
     * Use it carefully only when necesarry.
     *
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @param null|int                           $storeId
     */
    private function resetShippingCalculationIfNeeded($shippingAddress, $storeId = null)
    {
        if ($this->configHelper->getResetShippingCalculation($storeId)) {
            $shippingAddress->removeAllShippingRates();
            $shippingAddress->setCollectShippingRates(true);
        }
    }

    /**
     * @param Quote $quote
     * @param \Magento\Quote\Model\Quote\Address $shippingAddress
     * @return array
     */
    public function generateShippingMethodArray($quote, $shippingAddress)
    {
        $shippingMethodArray = [];
        $shippingRates = $shippingAddress->getGroupedAllShippingRates();

        $this->resetShippingCalculationIfNeeded($shippingAddress, $quote->getStoreId());

        foreach ($shippingRates as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $shippingMethodArray[] = $this->converter->modelToDataObject($rate, $quote->getQuoteCurrencyCode());
            }
        }

        return $shippingMethodArray;
    }

    /**
     * Collects shipping options for the quote and received address data
     *
     * @param Quote $quote
     * @param array $addressData
     *
     * @return ShippingOptionInterface[]
     */
    public function getShippingOptions($quote, $addressData)
    {

        if ($quote->isVirtual()) {
            $billingAddress = $quote->getBillingAddress();
            $billingAddress->addData($addressData);

            $quote->collectTotals();

            $this->cartHelper->collectAddressTotals($quote, $billingAddress);
            $taxAmount = CurrencyUtils::toMinor($billingAddress->getTaxAmount(), $quote->getQuoteCurrencyCode());

            return [
                $this->shippingOptionInterfaceFactory
                    ->create()
                    ->setService(self::NO_SHIPPING_SERVICE)
                    ->setCost(0)
                    ->setReference(self::NO_SHIPPING_REFERENCE)
                    ->setTaxAmount($taxAmount)
            ];
        }

        $appliedQuoteCouponCode = $quote->getCouponCode();

        $shippingAddress = $quote->getShippingAddress();
        $shippingAddress->addData($addressData);
        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->setShippingMethod(null);

        $quote->collectTotals();
        $this->cartHelper->collectAddressTotals($quote, $shippingAddress);

        $shippingMethodArray = $this->generateShippingMethodArray($quote, $shippingAddress);

        $shippingMethods = [];
        $errors = [];

        foreach ($shippingMethodArray as $shippingMethod) {
            $service = $shippingMethod->getCarrierTitle() . ' - ' . $shippingMethod->getMethodTitle();
            $method  = $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode();

            if ($this->configHelper->isPickupInStoreShippingMethodCode($method)) {
                $this->_oldShippingAddress = $quote->getShippingAddress()->getData();
                $addressData = $this->configHelper->getPickupAddressData();
                $quote->getShippingAddress()->addData($addressData);
            }

            $this->resetShippingCalculationIfNeeded($shippingAddress);

            $shippingAddress->setShippingMethod($method);
            // Since some types of coupon only work with specific shipping options,
            // for each shipping option, it need to recalculate the shipping discount amount
            if (! empty($appliedQuoteCouponCode)) {
                $shippingAddress->setCollectShippingRates(true)
                                ->collectShippingRates()->save();
                $quote->setCouponCode('')->collectTotals()->save();
                $quote->setCouponCode($appliedQuoteCouponCode)->collectTotals()->save();
            }

            $this->cartHelper->collectAddressTotals($quote, $shippingAddress);
            if ($this->doesDiscountApplyToShipping($quote)) {
                /**
                 * Unset values for shipping_amount_for_discount and base_shipping_amount_for_discount to allow
                 * @see \Magento\SalesRule\Model\Validator::processShippingAmount
                 * to calculate shipping discount amount properly during totals collection.
                 * Amount for discount gets set to 0 by {@see \Magento\Tax\Model\Sales\Total\Quote\Tax::clearValues}
                 * in a previous quote totals collection call.
                 */
                $shippingAddress->unsShippingAmountForDiscount();
                $shippingAddress->unsBaseShippingAmountForDiscount();
                // In order to get correct shipping discounts the following method must be called twice.
                // Being a bug in Magento, or a bug in the tested store version, shipping discounts
                // are not collected the first time the method is called.
                // There was one loop step delay in applying discount to shipping options when method was called once.
                $this->cartHelper->collectAddressTotals($quote, $shippingAddress);
            }

            $discountAmount = $this->eventsForThirdPartyModules->runFilter("collectShippingDiscounts", $shippingAddress->getShippingDiscountAmount(), $quote, $shippingAddress);

            $cost        = $shippingAddress->getShippingAmount() - $discountAmount;
            $roundedCost = CurrencyUtils::toMinor($cost, $quote->getQuoteCurrencyCode());

            $currencyCode = $quote->getQuoteCurrencyCode();
            $diff = CurrencyUtils::toMinorWithoutRounding($cost, $currencyCode) - $roundedCost;

            $taxAmount = round(CurrencyUtils::toMinorWithoutRounding($shippingAddress->getTaxAmount(), $currencyCode) + $diff);

            if ($discountAmount >= DiscountHelper::MIN_NONZERO_VALUE) {
                if ($cost == 0) {
                    $service .= ' [free&nbsp;shipping&nbsp;discount]';
                } else {
                    $discount = $this->priceHelper->currency($discountAmount, true, false);
                    $service .= " [$discount" . "&nbsp;discount]";
                }
                $service = html_entity_decode($service);
            }

            if (abs((float)$diff) >= $this->threshold) {
                $this->taxAdjusted = true;
                $this->bugsnag->registerCallback(function ($report) use (
                    $method,
                    $diff,
                    $service,
                    $roundedCost,
                    $taxAmount
                ) {
                    $report->setMetaData([
                        'TOTALS_DIFF' => [
                            $method => [
                                'diff'       => $diff,
                                'service'    => $service,
                                'reference'  => $method,
                                'cost'       => $roundedCost,
                                'tax_amount' => $taxAmount,
                            ]
                        ]
                    ]);
                });
            }

            if ($this->configHelper->isPickupInStoreShippingMethodCode($method)) {
                $quote->getShippingAddress()->addData($this->_oldShippingAddress);
            }

            $error = $shippingMethod->getErrorMessage();

            if ($error) {
                $errors[] = [
                    'service'    => $service,
                    'reference'  => $method,
                    'cost'       => $roundedCost,
                    'tax_amount' => $taxAmount,
                    'error'      => $error,
                ];

                continue;
            }

            $roundedCost = $this->eventsForThirdPartyModules->runFilter(
                "filterShippingAmount",
                $roundedCost,
                $quote
            );

            $shippingMethods[] = $this->shippingOptionInterfaceFactory
                ->create()
                ->setService($service)
                ->setCost($roundedCost)
                ->setReference($method)
                ->setTaxAmount($taxAmount);
        }

        $shippingAddress->setShippingMethod(null);
        $this->cartHelper->collectAddressTotals($quote, $shippingAddress);
        $shippingAddress->save();

        if ($errors) {
            $this->bugsnag->registerCallback(function ($report) use ($errors, $addressData) {
                $report->setMetaData([
                    'SHIPPING METHOD' => [
                      'address' => $addressData,
                      'errors'  => $errors
                    ]
                ]);
            });

            $this->bugsnag->notifyError('Shipping Method Error', var_export($errors, true));
        }

        if (!$shippingMethods) {
            $this->bugsnag->registerCallback(function ($report) use ($quote, $addressData) {
                $report->setMetaData([
                    'SHIPPING AND_TAX' => [
                        'address' => $addressData,
                        'immutable quote ID' => $quote->getId(),
                        'parent quote ID' => $quote->getBoltParentQuoteId(),
                        'Store Id'  => $quote->getStoreId()
                    ]
                ]);
            });

            throw new BoltException(
                __('No Shipping Methods retrieved'),
                null,
                BoltErrorResponse::ERR_SERVICE
            );
        }

        return $shippingMethods;
    }

    /**
     * @param $quote
     * @return bool
     */
    protected function doesDiscountApplyToShipping($quote)
    {
        $appliedRuleIds = $quote->getAppliedRuleIds();
        if ($appliedRuleIds) {
            foreach (explode(',', $appliedRuleIds) as $appliedRuleId) {
                try {
                    $rule = $this->ruleFactory->create()->load($appliedRuleId);
                    if ($rule->getApplyToShipping()) {
                        return true;
                    }
                } catch (\Throwable $exception) {
                    $this->bugsnag->notifyException($exception);
                }
            }
        }

        return false;
    }

    /**
     * @param      $errCode
     * @param      $message
     * @param      $httpStatusCode
     */
    protected function sendErrorResponse($errCode, $message, $httpStatusCode)
    {
        $encodeErrorResult = $this->errorResponse->prepareErrorMessage($errCode, $message);

        $this->response->setHttpResponseCode($httpStatusCode);
        $this->response->setBody($encodeErrorResult);
        $this->response->sendResponse();
    }

    /**
     *
     * If the coupon exists on the parent quote, and doesn't exist on the immutable quote, it means that the discount
     * isn't allowed to be applied due to discount shipping address restrictions and should be removed. Since at this
     * point Bolt has already applied the discount, the discount amount is added back to the shipping.
     *
     * @param $parentQuoteCoupon
     * @return bool
     */
    protected function couponInvalidForShippingAddress(
        $parentQuoteCoupon
    ) {
        $ignoredShippingAddressCoupons = $this->configHelper->getIgnoredShippingAddressCoupons($this->quote->getStoreId());

        return $parentQuoteCoupon &&
                in_array(strtolower((string)$parentQuoteCoupon), $ignoredShippingAddressCoupons) &&
                !$this->quote->setTotalsCollectedFlag(false)->collectTotals()->getCouponCode();
    }

    /**
     * (Re)Creates shipping options model from arrays containing shipping options and tax result amount
     *
     * @param array[] $shippingOptions
     * @param float $amount
     *
     * @return ShippingOptionsInterface
     */
    protected function createShippingOptionsModelFromArray($shippingOptions, $amount = 0)
    {
        return $this->shippingOptionsInterfaceFactory->create()
            ->setShippingOptions(
                array_map(
                    function ($shippingOptionData) {
                        return $this->shippingOptionInterfaceFactory->create()
                            ->setService($shippingOptionData['service'])
                            ->setCost($shippingOptionData['cost'])
                            ->setReference($shippingOptionData['reference'])
                            ->setTaxAmount($shippingOptionData['tax_amount']);;
                    },
                    $shippingOptions
                )
            )->setTaxResult($this->shippingTaxInterfaceFactory->create()->setAmount($amount));
    }
}
