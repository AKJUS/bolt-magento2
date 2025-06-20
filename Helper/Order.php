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

namespace Bolt\Boltpay\Helper;

use Bolt\Boltpay\Exception\BoltException;
use Bolt\Boltpay\Helper\Api as ApiHelper;
use Bolt\Boltpay\Helper\Config as ConfigHelper;
use Bolt\Boltpay\Helper\Shared\CurrencyUtils;
use Bolt\Boltpay\Model\Api\CreateOrder;
use Bolt\Boltpay\Model\Payment;
use Bolt\Boltpay\Model\Response;
use Magento\Customer\Api\Data\GroupInterface;
use Magento\Directory\Model\Region as RegionModel;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order as OrderModel;
use Magento\Sales\Model\Order\Email\Container\InvoiceIdentity as InvoiceEmailIdentity;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Payment\Transaction\Builder as TransactionBuilder;
use Bolt\Boltpay\Model\Service\InvoiceService;
use Magento\Sales\Model\OrderIncrementIdChecker;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Model\Order\Invoice;
use Magento\Framework\DataObjectFactory;
use Bolt\Boltpay\Helper\Log as LogHelper;
use Bolt\Boltpay\Helper\Cart as CartHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Bolt\Boltpay\Helper\Session as SessionHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Bolt\Boltpay\Helper\Discount as DiscountHelper;
use \Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Api\CreditmemoManagementInterface;
use Bolt\Boltpay\Model\ResourceModel\WebhookLog\CollectionFactory as WebhookLogCollectionFactory;
use Bolt\Boltpay\Model\WebhookLogFactory;
use Bolt\Boltpay\Helper\FeatureSwitch\Decider;
use Bolt\Boltpay\Model\CustomerCreditCardFactory;
use Bolt\Boltpay\Model\ResourceModel\CustomerCreditCard\CollectionFactory as CustomerCreditCardCollectionFactory;
use Bolt\Boltpay\Model\EventsForThirdPartyModules;
use Magento\Sales\Api\TransactionRepositoryInterface;

/**
 * Class Order
 * Boltpay Order helper
 *
 * @package Bolt\Boltpay\Helper
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Order extends AbstractHelper
{
    // Bolt transaction states
    const TS_PENDING               = 'cc_payment:pending';
    const TS_AUTHORIZED            = 'cc_payment:authorized';
    const TS_CAPTURED              = 'cc_payment:captured';
    const TS_PARTIAL_VOIDED        = 'cc_payment:partial_voided';
    const TS_COMPLETED             = 'cc_payment:completed';
    const TS_CANCELED              = 'cc_payment:cancelled';
    const TS_REJECTED_REVERSIBLE   = 'cc_payment:rejected_reversible';
    const TS_REJECTED_IRREVERSIBLE = 'cc_payment:rejected_irreversible';
    const TS_ZERO_AMOUNT           = 'zero_amount:completed';
    const TS_CREDIT_IN_PROGRESS    = 'cc_credit:in_progress';
    const TS_CREDIT_COMPLETED      = 'cc_credit:completed';
    const TS_CREDIT_CREATED      = 'cc_credit:created';

    const MISMATCH_TOLERANCE = 1;

    const BOLT_ORDER_STATE_NEW = 'bolt_new';
    const BOLT_ORDER_STATUS_PENDING = 'bolt_pending';
    const MAGENTO_ORDER_STATUS_PENDING = 'pending';

    const TT_PAYMENT = 'cc_payment';
    const TT_CREDIT = 'cc_credit';
    const TT_PAYPAL_PAYMENT = 'paypal_payment';
    const TT_PAYPAL_REFUND = 'paypal_refund';
    const TT_APM_PAYMENT = 'apm_payment';
    const TT_APM_REFUND = 'apm_refund';

    const VALID_HOOKS_FOR_ORDER_CREATION = [Hook::HT_PENDING, Hook::HT_PAYMENT];

    // Payment method was used for Bolt transaction
    const TP_VANTIV = 'vantiv';
    const TP_PAYPAL = 'paypal';
    const TP_AFTERPAY = 'afterpay';
    const TP_METHOD_DISPLAY = [
        'paypal' => 'PayPal',
        'afterpay' => 'Afterpay',
        'affirm' => 'Affirm',
        'braintree' => 'Braintree',
        'applepay' => 'ApplePay',
        'amazon_pay' => 'AmazonPay',
        'credova' => 'Credova'
    ];

    /**
     * @var string[] Associative array containing supported credit card networks as keys and their respective labels as
     *               values. Used for displaying in Order grids when related configuration is enabled.
     */
    const SUPPORTED_CC_TYPES = [
        'amex'       => 'Amex',
        'discover'   => 'Discover',
        'mastercard' => 'MC',
        'visa'       => 'Visa',
    ];

    /**
     * @var ApiHelper
     */
    private $apiHelper;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var RegionModel
     */
    private $regionModel;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var OrderSender
     */
    private $emailSender;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var InvoiceSender
     */
    private $invoiceSender;

    /**
     * @var TransactionBuilder
     */
    private $transactionBuilder;

    /**
     * @var TimezoneInterface
     */
    private $timezone;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var DataObjectFactory
     */
    private $dataObjectFactory;

    /**
     * @var LogHelper
     */
    private $logHelper;

    /**
     * @var Bugsnag
     */
    private $bugsnag;

    /**
     * @var CartHelper
     */
    private $cartHelper;

    /**
     * @var \Magento\Payment\Model\Info
     */
    private $quotePaymentInfoInstance = null;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /** @var SessionHelper */
    private $sessionHelper;

    /** @var DiscountHelper */
    private $discountHelper;

    /** @var DateTime */
    protected $date;

    /** @var WebhookLogCollectionFactory  */
    protected $webhookLogCollectionFactory;

    /** @var WebhookLogFactory */
    protected $webhookLogFactory;

    /** @var Decider */
    private $featureSwitches;

    /**
     * @var CheckboxesHandler
     */
    private $checkboxesHandler;

    /**
     * @var CustomFieldsHandler
     */
    private $customFieldsHandler;

    /**
     * @var CustomerCreditCardFactory
     */
    protected $customerCreditCardFactory;

    /**
     * @var CustomerCreditCardCollectionFactory
     */
    protected $customerCreditCardCollectionFactory;

    /** @var CreditmemoFactory */
    protected $creditmemoFactory;

    /** @var CreditmemoManagementInterface  */
    protected $creditmemoManagement;

    /** @var EventsForThirdPartyModules  */
    private $eventsForThirdPartyModules;

    /**
     * @var array transaction info cache
     */
    private $transactionInfo;

    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var OrderIncrementIdChecker|null
     */
    private $orderIncrementIdChecker;

    /**
     * @var Create|null
     */
    private $adminOrderCreateModel;

    /**
     * @var GiftOptionsHandler
     */
    private $giftOptionsHandler;

    /**
     * @var TransactionRepositoryInterface
     */
    private $transactionRepository;

    /**
     * Order constructor.
     *
     * @param Context                             $context
     * @param Api                                 $apiHelper
     * @param Config                              $configHelper
     * @param RegionModel                         $regionModel
     * @param QuoteManagement                     $quoteManagement
     * @param OrderSender                         $emailSender
     * @param InvoiceService                      $invoiceService
     * @param InvoiceSender                       $invoiceSender
     * @param SearchCriteriaBuilder               $searchCriteriaBuilder
     * @param OrderRepository                     $orderRepository
     * @param TransactionBuilder                  $transactionBuilder
     * @param TimezoneInterface                   $timezone
     * @param DataObjectFactory                   $dataObjectFactory
     * @param Log                                 $logHelper
     * @param Bugsnag                             $bugsnag
     * @param Cart                                $cartHelper
     * @param ResourceConnection                  $resourceConnection
     * @param Session                             $sessionHelper
     * @param Discount                            $discountHelper
     * @param DateTime                            $date
     * @param WebhookLogCollectionFactory         $webhookLogCollectionFactory
     * @param WebhookLogFactory                   $webhookLogFactory
     * @param Decider                             $featureSwitches
     * @param CheckboxesHandler                   $checkboxesHandler
     * @param CustomFieldsHandler                 $customFieldsHandler
     * @param CustomerCreditCardFactory           $customerCreditCardFactory
     * @param CustomerCreditCardCollectionFactory $customerCreditCardCollectionFactory
     * @param CreditmemoFactory                   $creditmemoFactory
     * @param CreditmemoManagementInterface       $creditmemoManagement
     * @param EventsForThirdPartyModules          $eventsForThirdPartyModules
     * @param GiftOptionsHandler                  $giftOptionsHandler
     * @param TransactionRepositoryInterface      $transactionRepository
     * @param OrderManagementInterface|null       $orderManagement
     * @param OrderIncrementIdChecker|null        $orderIncrementIdChecker
     * @param Create|null                         $adminOrderCreateModel
     */
    public function __construct(
        Context $context,
        ApiHelper $apiHelper,
        ConfigHelper $configHelper,
        RegionModel $regionModel,
        QuoteManagement $quoteManagement,
        OrderSender $emailSender,
        InvoiceService $invoiceService,
        InvoiceSender $invoiceSender,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepository $orderRepository,
        TransactionBuilder $transactionBuilder,
        TimezoneInterface $timezone,
        DataObjectFactory $dataObjectFactory,
        LogHelper $logHelper,
        Bugsnag $bugsnag,
        CartHelper $cartHelper,
        ResourceConnection $resourceConnection,
        SessionHelper $sessionHelper,
        DiscountHelper $discountHelper,
        DateTime $date,
        WebhookLogCollectionFactory $webhookLogCollectionFactory,
        WebhookLogFactory $webhookLogFactory,
        Decider $featureSwitches,
        CheckboxesHandler $checkboxesHandler,
        CustomFieldsHandler $customFieldsHandler,
        CustomerCreditCardFactory $customerCreditCardFactory,
        CustomerCreditCardCollectionFactory $customerCreditCardCollectionFactory,
        CreditmemoFactory $creditmemoFactory,
        CreditmemoManagementInterface $creditmemoManagement,
        EventsForThirdPartyModules $eventsForThirdPartyModules,
        GiftOptionsHandler $giftOptionsHandler,
        TransactionRepositoryInterface $transactionRepository,
        ?OrderManagementInterface $orderManagement = null,
        ?OrderIncrementIdChecker $orderIncrementIdChecker = null,
        ?Create $adminOrderCreateModel = null
    ) {
        parent::__construct($context);
        $this->apiHelper = $apiHelper;
        $this->configHelper = $configHelper;
        $this->regionModel = $regionModel;
        $this->quoteManagement = $quoteManagement;
        $this->emailSender = $emailSender;
        $this->invoiceService = $invoiceService;
        $this->invoiceSender = $invoiceSender;
        $this->transactionBuilder = $transactionBuilder;
        $this->timezone = $timezone;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->dataObjectFactory = $dataObjectFactory;
        $this->logHelper = $logHelper;
        $this->bugsnag = $bugsnag;
        $this->cartHelper = $cartHelper;
        $this->resourceConnection = $resourceConnection;
        $this->sessionHelper = $sessionHelper;
        $this->discountHelper = $discountHelper;
        $this->date = $date;
        $this->webhookLogCollectionFactory = $webhookLogCollectionFactory;
        $this->webhookLogFactory = $webhookLogFactory;
        $this->featureSwitches = $featureSwitches;
        $this->checkboxesHandler = $checkboxesHandler;
        $this->customFieldsHandler = $customFieldsHandler;
        $this->customerCreditCardFactory = $customerCreditCardFactory;
        $this->customerCreditCardCollectionFactory = $customerCreditCardCollectionFactory;
        $this->creditmemoFactory = $creditmemoFactory;
        $this->creditmemoManagement = $creditmemoManagement;
        $this->eventsForThirdPartyModules = $eventsForThirdPartyModules;
        $this->giftOptionsHandler = $giftOptionsHandler;
        $this->transactionRepository = $transactionRepository;
        $this->orderManagement = $orderManagement
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(OrderManagementInterface::class);
        $this->orderIncrementIdChecker = $orderIncrementIdChecker
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(OrderIncrementIdChecker::class);
        $this->adminOrderCreateModel = $adminOrderCreateModel
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(Create::class);
    }

    /**
     * Fetch transaction details info
     *
     * @param string $reference
     * @param        $storeId
     *
     * @return Response
     * @throws LocalizedException
     *
     * @api
     */
    public function fetchTransactionInfo($reference, $storeId = null)
    {
        if (isset($this->transactionInfo[$reference])) {
            return $this->transactionInfo[$reference];
        }
        //Request Data
        $requestData = $this->dataObjectFactory->create();
        $requestData->setDynamicApiUrl(ApiHelper::API_FETCH_TRANSACTION . "/" . $reference);
        $requestData->setApiKey($this->configHelper->getApiKey($storeId));
        $requestData->setRequestMethod('GET');
        //Build Request
        $request = $this->apiHelper->buildRequest($requestData, $storeId);

        $result = $this->apiHelper->sendRequest($request);
        $response = $result->getResponse();
        $this->transactionInfo[$reference] = $response;

        return $response;
    }

    /**
     * Set quote shipping method from transaction data
     *
     * @param Quote $quote
     * @param $transaction
     *
     * @throws \Exception
     */
    protected function setShippingMethod($quote, $transaction)
    {
        if ($quote->isVirtual()) {
            return;
        }

        if (isset($transaction->order->cart->in_store_shipments)) {
            $this->eventsForThirdPartyModules->dispatchEvent("setInStoreShippingMethodForPrepareQuote", $quote, $transaction);
        } else {
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true);
            $shippingMethod = $transaction->order->cart->shipments[0]->reference;
            $shippingAddress->setShippingMethod($shippingMethod)->save();
        }
    }

    /**
     * Set Quote address data helper method.
     *
     * @param Address $quoteAddress
     * @param $address
     *
     * @throws \Exception
     */
    public function setAddress($quoteAddress, $address)
    {
        $address = $this->cartHelper->handleSpecialAddressCases($address);

        $regionName = $address->region ?? null;
        $countryCode = $address->country_code ?? null;
        $firstName = $address->first_name ?? null;
        $lastName = $address->last_name ?? null;
        $streetAddress1 = $address->street_address1 ?? null;
        $streetAddress2 = $address->street_address2 ?? null;
        $locality = $address->locality ?? null;
        $postalCode = $address->postal_code ?? null;
        $phoneNumber = $address->phone_number ?? null;
        $company = $address->company ?? null;
        $emailAddress = $address->email_address ?? null;

        $region = $this->regionModel->loadByName($regionName, $countryCode);

        $addressData = [
            'firstname'    => $firstName,
            'lastname'     => $lastName,
            'street'       => trim($streetAddress1 . "\n" . $streetAddress2),
            'city'         => $locality,
            'country_id'   => $countryCode,
            'region'       => $regionName,
            'postcode'     => $postalCode,
            'telephone'    => $phoneNumber,
            'region_id'    => $region ? $region->getId() : null,
            'company'      => $company,
        ];

        if ($this->cartHelper->validateEmail($emailAddress)) {
            $addressData['email'] = $emailAddress;
        }

        // discard empty address fields
        foreach ($addressData as $key => $value) {
            if (empty($value)) {
                unset($addressData[$key]);
            }
        }

        $quoteAddress->setShouldIgnoreValidation(true);
        $quoteAddress->addData($addressData)->save();
    }

    /**
     * Set Quote shipping address data.
     *
     * @param Quote $quote
     * @param $transaction
     *
     * @return void
     * @throws \Exception
     */
    protected function setShippingAddress($quote, $transaction)
    {
        if ($address = $this->eventsForThirdPartyModules->runFilter('isInStorePickupShipping', null, $quote, $transaction)) {
            $this->eventsForThirdPartyModules->dispatchEvent("setInStoreShippingAddressForPrepareQuote", $quote, $transaction);
        } else {
            $address = $transaction->order->cart->shipments[0]->shipping_address ?? null;
            $referenceShipmentMethod = $transaction->order->cart->shipments[0]->reference ?? null;
            if ($address) {
                $this->setAddress($quote->getShippingAddress(), $address);
                if (isset($referenceShipmentMethod) && $this->configHelper->isPickupInStoreShippingMethodCode($referenceShipmentMethod)) {
                    $addressData = $this->configHelper->getPickupAddressData();
                    $quote->getShippingAddress()->addData($addressData);
                }
            }
        }
    }

    /**
     * Set Quote billing address data.
     *
     * @param Quote $quote
     * @param $transaction
     *
     * @throws \Exception
     */
    protected function setBillingAddress($quote, $transaction)
    {
        $address = $transaction->order->cart->billing_address ?? null;
        if ($address) {
            $this->setAddress($quote->getBillingAddress(), $address);
        }
    }

    /**
     * Set quote customer email and guest checkout parameters
     *
     * @param Quote  $quote
     * @param string $email
     * @param \stdClass $transaction
     *
     * @return void
     */
    protected function addCustomerDetails($quote, $email, $transaction)
    {
        $quote->setCustomerEmail($email);
        if (!$quote->getCustomerId()) {
            if ($this->featureSwitches->isSetCustomerNameToOrderForGuests()) {
                $quote->setCustomerFirstname($transaction->order->cart->billing_address->first_name);
                $quote->setCustomerLastname($transaction->order->cart->billing_address->last_name);
            }
            if ($quote->getData('bolt_checkout_type') != CartHelper::BOLT_CHECKOUT_TYPE_BACKOFFICE) {
                $quote->setCustomerId(null);
                $quote->setCheckoutMethod('guest');
                $quote->setCustomerIsGuest(true);
                $quote->setCustomerGroupId(GroupInterface::NOT_LOGGED_IN_ID);
            }
        }
    }

    /**
     * Set Quote payment method, 'boltpay'
     *
     * @param Quote $quote
     *
     * @throws LocalizedException
     * @throws \Exception
     */
    private function setPaymentMethod($quote)
    {
        $quote->setPaymentMethod(Payment::METHOD_CODE);
        // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => Payment::METHOD_CODE])->save();
    }

    /**
     * Check for Tax mismatch between Bolt and Magento.
     * Override store value with the Bolt one if a mismatch was found.
     *
     * @param \stdClass $transaction
     * @param OrderModel $order
     * @param Quote $quote
     * @throws \Exception
     */
    private function adjustTaxMismatch($transaction, $order, $quote)
    {
        $currencyCode = $order->getOrderCurrencyCode();
        $boltTaxAmount = CurrencyUtils::toMajor($transaction->order->cart->tax_amount->amount, $currencyCode);
        $boltTotalAmount = CurrencyUtils::toMajor($transaction->order->cart->total_amount->amount, $currencyCode);
        $precision = CurrencyUtils::getPrecisionForCurrencyCode($currencyCode);
        $orderTaxAmount = round($order->getTaxAmount(), $precision);

        if ($boltTaxAmount != $orderTaxAmount) {
            $order->setTaxAmount($boltTaxAmount);
            $order->setGrandTotal($boltTotalAmount);

            $this->bugsnag->registerCallback(function ($report) use ($quote, $boltTaxAmount, $orderTaxAmount) {

                $address = $quote->isVirtual() ? $quote->getBillingAddress() : $quote->getShippingAddress();

                $report->setMetaData([
                    'TAX MISMATCH' => [
                        'Store Applied Taxes' => $address->getAppliedTaxes(),
                        'Bolt Tax Amount' => $boltTaxAmount,
                        'Store Tax Amount' => $orderTaxAmount,
                        'Quote ID' => $quote->getId(),
                    ]
                ]);
            });

            $diff = round($boltTaxAmount - $orderTaxAmount, 2);
            $this->bugsnag->notifyError('Tax Mismatch', "Totals adjusted by $diff");
        }
    }

    /**
     * Check if the order has been created in the meanwhile
     * from another request, hook vs. frontend on a slow network / server
     *
     * @param int|string $parentQuoteId
     * @return bool|OrderModel
     */
    private function checkExistingOrder($parentQuoteId)
    {
        /** @var OrderModel $order */
        if ($order = $this->getExistingOrder(null, $parentQuoteId)) {
            $this->bugsnag->notifyError(
                'Duplicate Order Creation Attempt',
                null
            );
            return $order;
        }
        return false;
    }

    /**
     * Transform Quote to Order and send email to the customer.
     *
     * @param Quote $immutableQuote
     * @param \stdClass $transaction
     *
     * @param string|null $boltTraceId
     * @return AbstractExtensibleModel|OrderInterface|null|object
     * @throws LocalizedException
     * @throws \Exception
     */
    protected function createOrder($immutableQuote, $transaction, $boltTraceId = null)
    {
        // Load and prepare parent quote
        /** @var Quote $quote */
        $quote = $this->prepareQuote($immutableQuote, $transaction);

        if ($order = $this->checkExistingOrder($quote->getId())) {
            return $order;
        }
        try {
            /** @var OrderModel $order */
            $order = $this->quoteManagement->submit($quote);
        } catch (\Exception $e) {
            if ($order = $this->checkExistingOrder($quote->getId())) {
                return $order;
            }
            throw $e;
        }
        if ($order === null) {
            throw new LocalizedException(__(
                'Quote Submit Error. Parent Quote ID: %1 Immutable Quote ID: %2',
                $quote->getId(),
                $immutableQuote->getId()
            ));
        }
        if (Hook::$fromBolt) {
            $order->addStatusHistoryComment(
                "BOLTPAY INFO :: This order was created via Bolt Webhook<br>Bolt traceId: $boltTraceId"
            );
        }
        $this->orderPostprocess($order, $quote, $transaction);
        return $order;
    }

    /**
     * Save additional order data and dispatch the after submit event
     *
     * @param OrderModel $order
     * @param Quote $quote
     * @param \stdClass $transaction
     */
    protected function orderPostprocess($order, $quote, $transaction)
    {
        // set PPC quote status to complete so it is not considered active anymore
        if ($quote->getBoltCheckoutType() == CartHelper::BOLT_CHECKOUT_TYPE_PPC) {
            $quote->setBoltCheckoutType(CartHelper::BOLT_CHECKOUT_TYPE_PPC_COMPLETE);
            $this->cartHelper->quoteResourceSave($quote);
        }
        // Check and fix tax mismatch
        if ($this->configHelper->shouldAdjustTaxMismatch()) {
            $this->adjustTaxMismatch($transaction, $order, $quote);
        }

        if ($this->configHelper->getPriceFaultTolerance()) {
            /////////////////////////////////////////////////////////////////////////
            /// Historically, we have honored a price tolerance of 1 cent on
            /// an order due calculations outside of the Magento framework context
            /// for discounts, shipping and tax.  We must still respect this feature
            /// and adjust the order final price according to fault tolerance which
            /// will now default to 1 cent unless a hidden option overrides this value
            /////////////////////////////////////////////////////////////////////////
            $this->adjustPriceMismatch($transaction, $order, $quote);
        }
        // Save reference to the Bolt transaction with the order
        if (isset($transaction->reference)) {
            $order->addStatusHistoryComment(
                __('Bolt transaction: %1', $this->formatReferenceUrl($transaction->reference))
            );
        }
        // Add the user_note to the order comments and make it visible for the customer.
        if (isset($transaction->order->user_note)) {
            $this->setOrderUserNote($order, $transaction->order->user_note);
        }
        // Add the gift options to the order comments.
        $this->giftOptionsHandler->handle($order, $transaction);
        $this->eventsForThirdPartyModules->dispatchEvent('orderPostprocess', $order, $quote, $transaction);
        $order->save();
    }

    /**
     * @param $transaction
     * @param OrderModel $order
     * @param Quote $quote
     * @throws \Exception
     */
    private function adjustPriceMismatch($transaction, $order, $quote)
    {
        $currencyCode = $order->getOrderCurrencyCode();
        $priceFaultTolerance = $this->configHelper->getPriceFaultTolerance();
        $boltTotalAmount = $transaction->order->cart->total_amount->amount;
        $magentoTotalAmount = CurrencyUtils::toMinor($order->getGrandTotal(), $currencyCode);
        $totalMismatch = $boltTotalAmount - $magentoTotalAmount;

        if (abs($totalMismatch) > 0 && abs($totalMismatch) <= $priceFaultTolerance) {
            $boltGrandTotal = CurrencyUtils::toMajor($boltTotalAmount, $currencyCode);
            if ($this->featureSwitches->isIncludeMismatchAmountIntoTaxWhenAdjustingPriceMismatch()){
                $order->setTaxAmount($order->getTaxAmount() + CurrencyUtils::toMajor($totalMismatch, $currencyCode))
                    ->setBaseTaxAmount($order->getBaseTaxAmount() + CurrencyUtils::toMajor($totalMismatch, $currencyCode));
            }

            $order->setGrandTotal($boltGrandTotal);

            $this->bugsnag->registerCallback(function ($report) use ($quote, $boltTotalAmount, $magentoTotalAmount) {

                $report->setMetaData([
                    'TOTAL MISMATCH' => [
                        'Bolt Total Amount' => $boltTotalAmount,
                        'Magento Total Amount' => $magentoTotalAmount,
                        'Quote ID' => $quote->getId(),
                    ]
                ]);
            });

            $diff = round(CurrencyUtils::toMajor($totalMismatch, $currencyCode), 2);
            $this->bugsnag->notifyError('Total Mismatch', "Totals adjusted by $diff");
        }
    }

    /**
     * Assign data to the order payment instance
     *
     * @param OrderPaymentInterface $payment
     * @param \stdClass $transaction
     * @return void
     */
    public function setOrderPaymentInfoData($payment, $transaction)
    {
        if (!empty($transaction->from_credit_card->expiration)) {
            $paymentData = [
                'expiration' => $transaction->from_credit_card->expiration
            ];
            $payment->setAdditionalInformation(array_merge((array)$payment->getAdditionalInformation(), $paymentData));
        }

        if (empty($payment->getCcLast4()) && ! empty($transaction->from_credit_card->last4)) {
            $payment->setCcLast4($transaction->from_credit_card->last4);
        }
        if (empty($payment->getCcType()) && ! empty($transaction->from_credit_card->network)) {
            $payment->setCcType($transaction->from_credit_card->network);
        }
        if (!empty($transaction->from_credit_card->token_type) && $transaction->from_credit_card->token_type == "applepay") {
            $payment->setAdditionalData($transaction->from_credit_card->token_type);
        }

        if (!empty($transaction->authorization_id)){
            $paymentData = [
                'payment_processor_authorization_id' => $transaction->authorization_id,
            ];
            $payment->setAdditionalInformation(array_merge((array)$payment->getAdditionalInformation(), $paymentData));
        }

        if (
            !empty($transaction->processor)
            && $transaction->processor == 'adyen_gateway'
            && !empty($transaction->authorization->metadata->processor_token_alias)
        ){
            $paymentData = [
                'adyen_processor_token_alias' => $transaction->authorization->metadata->processor_token_alias,
            ];
            $payment->setAdditionalInformation(array_merge((array)$payment->getAdditionalInformation(), $paymentData));
        }

        if (!empty($transaction->from_credit_card->bin)) {
            $paymentData = [
                'bin' => $transaction->from_credit_card->bin
            ];
            $payment->setAdditionalInformation(array_merge((array)$payment->getAdditionalInformation(), $paymentData));
        }

        if (
            !empty($transaction->processor)
            && $transaction->processor == 'credova'
            && !empty($transaction->transaction_properties->credova_public_id)
            && !empty($transaction->transaction_properties->credova_application_id)
        ) {
            $paymentData = [
                'credova_public_id' => $transaction->transaction_properties->credova_public_id,
                'credova_application_id' => $transaction->transaction_properties->credova_application_id
            ];
            $payment->setAdditionalInformation(array_merge((array)$payment->getAdditionalInformation(), $paymentData));
        }


        $payment->save();
    }

    /**
     * Delete redundant immutable quotes.
     *
     * @param Quote $quote
     */
    private function deleteRedundantQuotes($quote)
    {
        $this->eventsForThirdPartyModules->dispatchEvent("beforeOrderDeleteRedundantQuotes", $quote);

        $connection = $this->resourceConnection->getConnection();

        // get table name with prefix
        $tableName = $this->resourceConnection->getTableName('quote');

        $bind = [
            'bolt_parent_quote_id = ?' => $quote->getBoltParentQuoteId(),
            'entity_id != ?' => $quote->getBoltParentQuoteId()
        ];

        $connection->delete($tableName, $bind);
    }

    /**
     * After the order gets approved by Bolt set its state/status
     * to Magento default for the new orders "new"/"Pending".
     * Required for the order to be imported by some ERP systems.
     *
     * @param OrderModel $order
     * @param string $state
     */
    public function resetOrderState($order)
    {
        $order->setState(self::BOLT_ORDER_STATE_NEW);
        $order->setStatus(self::BOLT_ORDER_STATUS_PENDING);
        $order->addStatusHistoryComment(
            "BOLTPAY INFO :: This order was approved by Bolt"
        );
        $order->save();
    }

    /**
     * Check if the hook is valid for creating a missing order.
     * Throw an exception otherwise.
     *
     * @param string|null $hookType
     * @throws BoltException
     */
    public function verifyOrderCreationHookType($hookType)
    {
        if (( Hook::$fromBolt || isset($hookType) )
            && ! in_array($hookType, static::VALID_HOOKS_FOR_ORDER_CREATION)
        ) {
            throw new BoltException(
                __('Order creation is forbidden from hook of type: %1', $hookType),
                null,
                CreateOrder::E_BOLT_REJECTED_ORDER
            );
        }
    }

    /**
     * Load Order by quote id
     *
     * @param string $quoteId
     *
     * @return OrderInterface|false
     */
    public function getOrderByQuoteId($quoteId)
    {
        return $this->cartHelper->getOrderByQuoteId($quoteId);
    }

    public function getOrderById($orderId)
    {
        return $this->orderRepository->get($orderId);
    }

    /**
     * Save/create the order (checkout, orphaned transaction),
     * Update order payment / transaction data (checkout, web hooks)
     *
     * @param string $reference Bolt transaction reference
     * @param null|int   $storeId
     * @param null|string   $boltTraceId
     * @param null|string   $hookType
     * @param null|array   $hookPayload
     *
     * @return array|mixed
     * @throws LocalizedException
     */
    public function saveUpdateOrder($reference, $storeId = null, $boltTraceId = null, $hookType = null, $hookPayload = null)
    {
        $transaction = $this->fetchTransactionInfo($reference, $storeId);

        $parentQuoteId = $transaction->order->cart->order_reference;

        ///////////////////////////////////////////////////////////////
        // Get order id and immutable quote id stored with transaction.
        // Take into account orders created with data in old format
        // where only reserved_order_id was stored in display_id field
        // and the immutable quote_id in order_reference
        ///////////////////////////////////////////////////////////////
        $incrementId = isset($transaction->order->cart->display_id) ?
            $transaction->order->cart->display_id :
            null;
        $quoteId = isset($transaction->order) ?
            $this->cartHelper->getImmutableQuoteIdFromBoltOrder($transaction->order) :
            null;

        if (!$quoteId) {
            $quoteId = $parentQuoteId;
        }
        ///////////////////////////////////////////////////////////////

        ///////////////////////////////////////////////////////////////
        // try loading (immutable) quote from entity id. if called from
        // hook the quote might have been cleared, resulting in error.
        // prevent failure and log event to bugsnag.
        ///////////////////////////////////////////////////////////////
        $immutableQuote = $this->cartHelper->getQuoteById($quoteId);
        if (!$immutableQuote && $parentQuoteId) {
            // if the immutable quote is not found, attempt to load the original quote and use it as immutable.
            $immutableQuote = $this->cartHelper->getQuoteById($parentQuoteId);
        }

        if (!$immutableQuote) {
            $this->bugsnag->registerCallback(function ($report) use ($incrementId, $quoteId, $storeId) {
                $report->setMetaData([
                    'ORDER' => [
                        'incrementId' => $incrementId,
                        'quoteId' => $quoteId,
                        'Magento StoreId' => $storeId
                    ]
                ]);
            });
        }
        ///////////////////////////////////////////////////////////////

        // check if the order exists
        $order = $this->getExistingOrder($incrementId, $parentQuoteId);

        // if not create the order
        if (!$order || !$order->getId()) {
            if (!$immutableQuote) {
                $exception = new LocalizedException(__('Unknown quote id: %1', $quoteId));
                if (Hook::$fromBolt && in_array($transaction->status, [Payment::TRANSACTION_AUTHORIZED, Payment::TRANSACTION_CANCELLED])) {
                    if ($transaction->status == Payment::TRANSACTION_AUTHORIZED) {
                        $this->voidTransactionOnBolt($transaction->id, $storeId);
                    }

                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
                    //// Allow missing quote failed hooks to resend 10 times so the Administrator can be notified via email
                    ///  On the eleventh time that the failed hook was sent to Magento, we return $this in order to have the hook return successfully.
                    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

                    if (!$this->featureSwitches->isLogMissingQuoteFailedHooksEnabled()) {
                        $this->bugsnag->notifyException($exception);
                        return [null, null];
                    }

                    /** @var \Bolt\Boltpay\Model\ResourceModel\WebhookLog\Collection $webhookLogCollection */
                    $webhookLogCollection = $this->webhookLogCollectionFactory->create();

                    if ($webhookLog = $webhookLogCollection->getWebhookLogByTransactionId($transaction->id, $hookType)) {
                        $numberOfMissingQuoteFailedHooks = $webhookLog->getNumberOfMissingQuoteFailedHooks();
                        if ($numberOfMissingQuoteFailedHooks > 10) {
                            $this->bugsnag->notifyException($exception);
                            return [null, null];
                        }

                        $webhookLog->incrementAttemptCount();
                    } else {
                        /** @var \Bolt\Boltpay\Model\WebhookLog $webhookLog */
                        $webhookLog = $this->webhookLogFactory->create();

                        $webhookLog->recordAttempt($transaction->id, $hookType);
                    };
                }

                throw $exception;
            }
            $this->verifyOrderCreationHookType($hookType);
            $order = $this->createOrder($immutableQuote, $transaction, $boltTraceId);
        }

        $parentQuote = $this->cartHelper->getQuoteById($parentQuoteId);

        if ($parentQuote) {
            $this->dispatchPostCheckoutEvents($order, $parentQuote);

            $this->eventsForThirdPartyModules->dispatchEvent("deleteRedundantDiscounts", $parentQuote);

            // Delete redundant cloned quotes
            $this->deleteRedundantQuotes($parentQuote);
        }

        if (Hook::$fromBolt) {
            // if called from hook update order payment transactions
            $this->updateOrderPayment($order, $transaction, null, $hookType, $hookPayload);
            // Check for total amount mismatch between magento and bolt order.
            if (
                $this->featureSwitches->isIgnoreTotalValidationWhenCreditHookIsSentToMagentoEnabled()
                && $hookType === Hook::HT_CREDIT
                && $this->isTotalValidationIgnored($transaction, $order)
            ) {
                return [$parentQuote, $order];
            }
            $this->holdOnTotalsMismatch($order, $transaction);
        }

        return [$parentQuote, $order];
    }

    /**
     * @param $transaction
     * @param $order
     * @return bool
     * @throws \Exception
     */
    public function isTotalValidationIgnored($transaction, $order)
    {
        $currencyCode = $order->getOrderCurrencyCode();
        $refundAmount = $transaction->amount->amount;
        $boltTotal = $transaction->order->cart->total_amount->amount;
        $magentoTotal = CurrencyUtils::toMinor($order->getGrandTotal(), $currencyCode);

        return $boltTotal - $refundAmount == $magentoTotal;
    }

    /**
     * @param $transactionId
     * @param $storeId
     * @param OrderInterface $order
     * @return $this
     * @throws LocalizedException
     */
    public function voidTransactionOnBolt($transactionId, $storeId)
    {
        //Get transaction data
        $transactionData = [
            'transaction_id' => $transactionId,
            'skip_hook_notification' => true
        ];
        $apiKey = $this->configHelper->getApiKey($storeId);

        //Request Data
        $requestData = $this->dataObjectFactory->create();
        $requestData->setApiData($transactionData);
        $requestData->setDynamicApiUrl(ApiHelper::API_VOID_TRANSACTION);
        $requestData->setApiKey($apiKey);
        //Build Request
        $request = $this->apiHelper->buildRequest($requestData, $storeId);
        $result = $this->apiHelper->sendRequest($request);
        $response = $result->getResponse();

        if (empty($response)) {
            throw new LocalizedException(
                __('Bad void response from boltpay')
            );
        }

        $status = $response->status ?? null;
        if ($status != 'cancelled') {
            throw new LocalizedException(__('Payment void error.'));
        }

        return $this;
    }

    /**
     * Save credit card information for logged-in customer based on their Bolt transaction reference and store id
     *
     * @param $reference
     * @param $storeId
     * @return bool
     */
    public function saveCustomerCreditCard($reference, $storeId)
    {
        try {
            if (!$this->featureSwitches->isSaveCustomerCreditCardEnabled()){
                return false;
            }

            $transaction = $this->fetchTransactionInfo($reference, $storeId);
            $parentQuoteId = $transaction->order->cart->order_reference ?? false;
            $quote = $this->cartHelper->getQuoteById($parentQuoteId);

            if (!$quote) {
                $immutableQuoteId = $this->cartHelper->getImmutableQuoteIdFromBoltOrder($transaction->order);
                $quote = $this->cartHelper->getQuoteById($immutableQuoteId);
            }

            if (!$quote) {
                return false;
            }

            $customerId = $quote->getCustomerId();
            $boltConsumerId = $transaction->from_consumer->id ?? false;
            $boltCreditCard = $transaction->from_credit_card ?? false;
            $boltCreditCardId = $boltCreditCard->id ?? false;

            if (!$customerId || !$boltConsumerId || !$boltCreditCardId) {
                return false;
            }

            $doesCardExist = $this->customerCreditCardCollectionFactory->create()
                            ->doesCardExist($customerId, $boltConsumerId, $boltCreditCardId);

            if ($doesCardExist) {
                return false;
            }

            $this->customerCreditCardFactory->create()
                ->saveCreditCard($customerId, $boltConsumerId, $boltCreditCardId, $boltCreditCard);

            return true;
        } catch (\Exception $exception) {
            $this->bugsnag->notifyException($exception);
            return false;
        }
    }

    /**
     * Fetch and apply external quote data, not stored within the quote or totals
     * (usually in 3rd party modules DB tables)
     *
     * @param Quote $quote
     */
    public function applyExternalQuoteData($quote)
    {
        $this->discountHelper->applyExternalDiscountData($quote);
    }

    /**
     * @param OrderInterface $order
     * @param Quote $quote
     */
    public function dispatchPostCheckoutEvents($order, $quote)
    {
        //Add a flag instead of using reserved order ID
        if ($quote->getBoltDispatched()) {
            return; // already dispatched
        }

        $this->applyExternalQuoteData($quote);


        $quote->setInventoryProcessed(true);

        if ($order->getAppliedRuleIds() === null) {
            $order->setAppliedRuleIds('');
        }

        $this->logHelper->addInfoLog('[-= dispatchPostCheckoutEvents =-]');
        $this->_eventManager->dispatch(
            'checkout_submit_all_after',
            [
                'order' => $order,
                'quote' => $quote
            ]
        );

        // Set dispatched to be true. Prevents dispatching more then once.
        $quote->setBoltDispatched(true);
        $this->cartHelper->quoteResourceSave($quote);
    }

    /**
     * Cancel or detete the failed payment order depending on settings via feature switches
     *
     * @param string $displayId
     * @param string $immutableQuoteId
     * @return string log message
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function deleteOrCancelFailedPaymentOrder($display_id, $immutableQuoteId)
    {
        if ($this->featureSwitches->isCancelFailedPaymentOrderInsteadOfDeleting()) {
            /** @see \Bolt\Boltpay\Helper\Order::deleteOrderByIncrementId */
            $this->cancelFailedPaymentOrder($display_id, $immutableQuoteId);
            return 'Order was canceled: ' . $display_id;
        }

        $order = $this->getExistingOrder($display_id);

        if (!$order) {
            return 'Order is already deleted: ' . $display_id;
        }

        // the order may already be in a canceled state due to the rejected_irreversible hook before authorization.
        // in this case, we don't need to delete it.
        if ($order->isCanceled()) {
            return 'Order is already canceled:' . $display_id;
        }

        $this->deleteOrderByIncrementId($display_id, $immutableQuoteId);
        return 'Order was deleted: ' . $display_id;
    }

    /**
     * Try to fetch and price-validate already existing order.
     * Use case: order has been created but the payment fails due to the invalid credit card data.
     * Then the customer enters the correct card info, the previously created order is used if the amounts match.
     *
     * @param Quote $quote
     * @param \stdClass $transaction
     * @return bool|false|OrderModel
     * @throws \Exception
     */
    public function processExistingOrder($quote, $transaction)
    {
        // check if the order has been created in the meanwhile
        if ($order = $this->getExistingOrder(null, $quote->getId())) {

            if ($order->isCanceled()) {
                throw new BoltException(
                    __(
                        'Order has been canceled due to the previously declined payment. Quote ID: %1 Order Increment ID %2',
                        $quote->getId(),
                        $order->getIncrementId()
                    ),
                    null,
                    CreateOrder::E_BOLT_REJECTED_ORDER
                );
            }

            if ($order->getState() === OrderModel::STATE_PENDING_PAYMENT) {
                // Order for the same quote is created, it is in pending payment status
                // and we try to create a new one
                // It means order was created for unsuccessful payment attempt and wasn't deleted yet.
                // We can safely delete it
                $incrementId = $order->getIncrementId();
                $immutableQuoteId = $this->cartHelper->getImmutableQuoteIdFromBoltOrder($transaction->order);
                $message = $this->deleteOrCancelFailedPaymentOrder($incrementId, $immutableQuoteId);
                $this->bugsnag->notifyError(
                    "Existing order is in pending payment",
                    $message . "quote id: " . $quote->getId()
                );
                return false;
            }

            if ($this->hasSamePrice($order, $transaction)) {
                return $order;
            }
            $this->deleteOrder($order);
        }
        return false;
    }

    /**
     * @param Quote $quote
     * @param array $orderData
     *
     * @return false|OrderModel|null
     * @throws LocalizedException
     */
    public function submitQuote($quote, $orderData = [])
    {
        /** @var OrderModel $order */
        try {
            $order = $this->quoteManagement->submit($quote, $orderData);
        } catch (\Exception $e) {
            $order = $this->getOrderByQuoteId($quote->getId());
            if ($order && $order->getPayment() && $order->getPayment()->getMethod() === Payment::METHOD_CODE) {
                $this->bugsnag->registerCallback(
                    function ($report) use ($order) {
                        $report->setMetaData(
                            [
                                'CREATE ORDER' => [
                                    'order_id' => $order->getId(),
                                    'order_increment_id' => $order->getIncrementId(),
                                ]
                            ]
                        );
                    }
                );
                $this->bugsnag->notifyException($e);
            } else {
                throw $e;
            }
        }
        return $order;
    }

    /**
     * @param Quote $quote
     * @param \stdClass $transaction
     * @return OrderModel
     * @throws BoltException
     * @throws LocalizedException
     */
    public function processNewOrder($quote, $transaction)
    {
        $orderData = [];
        if (isset($transaction->order->cart->metadata->original_order_entity_id) &&
            $originalOrderId = $transaction->order->cart->metadata->original_order_entity_id
        ) {
            try {
                $originalOrder = $this->orderRepository->get($originalOrderId);
                $originalId = $originalOrder->getOriginalIncrementId();
                if (!$originalId) {
                    $originalId = $originalOrder->getIncrementId();
                }
                /**
                 * If expected increment id is already used, we must generate a new one by incrementing the edit digit
                 * This happens when a failed payment occurred while editing an order
                 * and failed orders are cancelled instead of deleted
                 *
                 * @see \Bolt\Boltpay\Helper\FeatureSwitch\Definitions::M2_CANCEL_FAILED_PAYMENT_ORDERS_INSTEAD_OF_DELETING
                 *
                 * Example:
                 * Regular order is created with increment id 100000000
                 * Payment fails when editing said order after the pre-auth already created order 100000000-1
                 * Our next order should have increment id equal to 100000000-2 while being linked to the first order
                 */
                $previousEditIncrement = $originalOrder->getEditIncrement();
                $editIncrement = $previousEditIncrement + 1;
                while ($this->orderIncrementIdChecker->isIncrementIdUsed($originalId . '-' . $editIncrement)) {
                    $editIncrement++;
                }
                $orderData = [
                    'original_increment_id'   => $originalId,
                    'relation_parent_id'      => $originalOrder->getId(),
                    'relation_parent_real_id' => $originalOrder->getIncrementId(),
                    'edit_increment'          => $editIncrement,
                    'increment_id'            => $originalId . '-' . $editIncrement
                ];
                $quote->setReservedOrderId($orderData['increment_id']);
            } catch (\Exception $e) {
                $this->bugsnag->notifyException($e);
                //original order doesn't exist, proceed regularly
            }
        }
        $order = $this->submitQuote($quote, $orderData);

        if (!$order) {
            $this->bugsnag->registerCallback(function ($report) use ($quote) {
                $report->setMetaData([
                    'CREATE ORDER' => [
                        'pre-auth order.create' => true,
                        'parent quote ID' => $quote->getId(),
                    ]
                ]);
            });
            throw new BoltException(
                __(
                    'Quote Submit Error. Parent Quote ID: %1',
                    $quote->getId()
                ),
                null,
                CreateOrder::E_BOLT_GENERAL_ERROR
            );
        }
        // When the create_order hook (thread 1) takes a lot of time to execute and returns a timeout, the authorize/payment hook (thread 2) is sent to Magento.
        // It updates the order prior to the create_order hook (thread 1) process.
        // This ensures that the returned order in the create_order hook is the latest updated order.
        $existingOrder = $this->eventsForThirdPartyModules->runFilter('beforeGetOrderByIdProcessNewOrder', $this->cartHelper->getOrderById($order->getId()), $order);
        $orderPayment = $existingOrder->getPayment();
        if ($orderPayment && $orderPayment->getMethod() !== Payment::METHOD_CODE) {
            throw new LocalizedException(__(
                'Payment method assigned to order %1 is: %2',
                $order->getIncrementId(),
                $orderPayment->getMethod()
            ));
        }

        $order = $existingOrder;

        $order->addStatusHistoryComment(
            "BOLTPAY INFO :: This order was created via Bolt Pre-Auth Webhook"
        );

        if (isset($originalOrder)) {
            try {
                $originalOrder->setRelationChildId($order->getId());
                $originalOrder->setRelationChildRealId($order->getIncrementId());
                $originalOrder->save();
                $this->orderManagement->cancel($originalOrder->getEntityId());
                $order->save();
            } catch (\Exception $e) {
                $this->bugsnag->notifyException($e);
            }
        }

        $this->orderPostprocess($order, $quote, $transaction);
        return $order;
    }

    /**
     * @param OrderModel $order
     * @param \stdClass $transaction
     * @return bool
     * @throws \Exception
     */
    protected function hasSamePrice($order, $transaction)
    {
        $currencyCode = $order->getOrderCurrencyCode();
        /** @var OrderModel $order */
        $taxAmount = CurrencyUtils::toMinor($order->getTaxAmount(), $currencyCode);
        $shippingAmount = CurrencyUtils::toMinor($order->getShippingAmount(), $currencyCode);
        $grandTotalAmount = CurrencyUtils::toMinor($order->getGrandTotal(), $currencyCode);

        $transactionTaxAmount = $transaction->order->cart->tax_amount->amount;
        $transactionShippingAmount = $transaction->order->cart->shipping_amount->amount;
        $transactionGrandTotalAmount = $transaction->order->cart->total_amount->amount;
        $priceFaultTolerance =  $this->configHelper->getPriceFaultTolerance();
        return abs($taxAmount - $transactionTaxAmount) <= $priceFaultTolerance
            && abs($shippingAmount - $transactionShippingAmount) <= $priceFaultTolerance
            && $grandTotalAmount == $transactionGrandTotalAmount;
    }

    /**
     * Cancel and delete the order
     *
     * @param OrderModel $order
     * @param bool $failureEventDispatched
     * @return void
     */
    public function deleteOrder($order, $failureEventDispatched = false)
    {
        $this->eventsForThirdPartyModules->dispatchEvent("beforeFailedPaymentOrderSave", $order);
        if (!$failureEventDispatched) {
            $quoteId = $order->getQuoteId();
            $quote = $this->cartHelper->getQuoteById($quoteId);
            $this->_eventManager->dispatch(
                'sales_model_service_quote_submit_failure',
                [
                    'order' => $order,
                    'quote' => $quote
                ]
            );
        }
        try {
            $this->orderManagement->cancel($order->getEntityId());
            $this->orderRepository->delete($order);
        } catch (\Exception $e) {
            $this->bugsnag->registerCallback(function ($report) use ($order) {
                $report->setMetaData([
                    'DELETE ORDER' => [
                        'order increment ID' => $order->getIncrementId(),
                        'order entity ID' => $order->getId(),
                    ]
                ]);
            });
            $this->bugsnag->notifyException($e);
            $this->orderRepository->delete($order);
        }
    }

    /**
     * Try to cancel the order. Covers the case when the payment was declined before authorization (blacklisted cc).
     * It is called upon rejected_irreversible hook.
     *
     * @param $incrementId
     * @param $quoteId
     * @return bool
     * @throws BoltException
     */
    public function tryDeclinedPaymentCancelation($incrementId, $immutableQuoteId)
    {
        $order = $this->getExistingOrder($incrementId);

        if (!$order) {
            throw new BoltException(
                __(
                    'Order Cancelation Error. Order does not exist. Order #: %1 Immutable Quote ID: %2',
                    $incrementId,
                    $immutableQuoteId
                ),
                null,
                CreateOrder::E_BOLT_GENERAL_ERROR
            );
        }

        if ($order->getState() === OrderModel::STATE_PENDING_PAYMENT) {
            $this->cancelOrder($order);
            $order->addCommentToStatusHistory(__('BOLTPAY INFO :: Order was canceled due to Bolt rejection before authorization'));
            $this->orderRepository->save($order);
        }
        return $order->getState() === OrderModel::STATE_CANCELED;
    }

    /**
     * @param $incrementId
     * @throws \Exception
     */
    public function deleteOrderByIncrementId($incrementId, $immutableQuoteId)
    {
        $order = $this->getExistingOrder($incrementId);

        if (!$order) {
            $this->bugsnag->notifyError(
                "Order Delete Error",
                "Order does not exist. Order #: $incrementId, Immutable Quote ID: $immutableQuoteId"
            );
            return;
        }

        $state = $order->getState();
        if ($state !== OrderModel::STATE_PENDING_PAYMENT) {
            throw new BoltException(
                __(
                    'Order Delete Error. Order is in invalid state. Order #: %1 State: %2 Immutable Quote ID: %3',
                    $incrementId,
                    $state,
                    $immutableQuoteId
                ),
                null,
                CreateOrder::E_BOLT_GENERAL_ERROR
            );
        }

        $parentQuoteId = $order->getQuoteId();
        $parentQuote = $this->cartHelper->getQuoteById($parentQuoteId);
        $this->_eventManager->dispatch(
            'sales_model_service_quote_submit_failure',
            [
                'order' => $order,
                'quote' => $parentQuote
            ]
        );
        $this->deleteOrder($order, true);
        // reactivate session quote - the condition excludes PPC quotes
        if ($parentQuoteId != $immutableQuoteId) {
            $this->cartHelper->quoteResourceSave($parentQuote->setIsActive(true));
        }
        // reset PPC quote checkout type so it can be treated as active
        if ($parentQuote->getBoltCheckoutType() == CartHelper::BOLT_CHECKOUT_TYPE_PPC_COMPLETE) {
            $parentQuote->setBoltCheckoutType(CartHelper::BOLT_CHECKOUT_TYPE_PPC);
            $this->cartHelper->quoteResourceSave($parentQuote->setIsActive(false));
        }
    }

    /**
     * @param $orderIncrementId
     * @param null $quoteId
     * @return false|OrderInterface|OrderModel
     */
    public function getExistingOrder($orderIncrementId, $quoteId = null)
    {
        /** @var OrderModel $order */
        $order = $this->cartHelper->getOrderByIncrementId($orderIncrementId, true);

        // If we had timeout issue on the create_order hook and the third party change the order increment id
        // then we use the parent quote id as a fallback to get existing order
        if ($quoteId && (!$order || !$order->getId())) {
            $order = $this->getOrderByQuoteId($quoteId);
        }

        return $order;
    }

    /**
     * Update quote change time and dispatch after save event
     *
     * @param Quote $quote
     */
    protected function quoteAfterChange($quote)
    {
        $quote->setUpdatedAt($this->date->gmtDate());
        $this->_eventManager->dispatch(
            'sales_quote_save_after',
            [
                'quote' => $quote
            ]
        );
    }

    /**
     * Load and prepare parent quote
     *
     * @param Quote $immutableQuote
     * @param  \stdClass $transaction
     * @return Quote
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\SessionException
     */
    public function prepareQuote($immutableQuote, $transaction)
    {
        /** @var Quote $quote */
        $boltParentQuoteId = $immutableQuote->getBoltParentQuoteId();
        if ($boltParentQuoteId) {
            $quote = $this->cartHelper->getQuoteById($boltParentQuoteId);
            if ($quote) {
                $this->cartHelper->replicateQuoteData($immutableQuote, $quote);
            } else {
                // if the parent quote is removed then we create order by the immutable quote
                $quote = $immutableQuote;
            }

        } else {
            // In Product page checkout case we created quote ourselves so we can change it and no need to work with quote copy
            $quote = $immutableQuote;
        }
        $this->quoteAfterChange($quote);

        // Load logged in customer checkout and customer sessions from cached session id.
        // Replace quote in checkout session.
        $this->sessionHelper->loadSession($quote, (array)$transaction->order->cart->metadata);

        $this->setShippingAddress($quote, $transaction);
        $this->setBillingAddress($quote, $transaction);
        $this->quoteAfterChange($quote);

        $this->setShippingMethod($quote, $transaction);
        $this->quoteAfterChange($quote);

        if ($this->cartHelper->checkIfQuoteHasCartFixedAmountAndApplyToShippingRuleAndTableRateShippingMethod($quote, $quote->getShippingAddress()->getShippingMethod())) {
            // If a customer applies a cart rule (fixed amount for whole cart and apply to shipping) and the table rate shipping method,
            // we must re-set FreeMethodWeight of the parent quote from the immutable quote to get the correct shipping amount
            $immutableQuote->collectTotals();
            $quote->getShippingAddress()->setFreeMethodWeight(
                $immutableQuote->getShippingAddress()->getFreeMethodWeight()
            );
        }

        $this->setPaymentMethod($quote);
        $this->quoteAfterChange($quote);

        if (isset($transaction->order->cart->in_store_shipments)) {
            $email = $transaction->order->cart->billing_address->email_address ??
                $transaction->order->cart->in_store_shipments[0]->shipment->email_address ?? null;
        } else {
            $email = $transaction->order->cart->billing_address->email_address ??
                $transaction->order->cart->shipments[0]->shipping_address->email_address ?? null;
        }

        $this->addCustomerDetails($quote, $email, $transaction);

        $this->cartHelper->quoteResourceSave($quote);

        $this->bugsnag->registerCallback(function ($report) use ($quote, $immutableQuote) {
            $report->setMetaData([
                'CREATE ORDER' => [
                    'parent quote ID' => $quote->getId(),
                    'immutable quote ID' => $immutableQuote->getId()
                ]
            ]);
        });

        if ($quote->getData('bolt_checkout_type') == \Bolt\Boltpay\Helper\Cart::BOLT_CHECKOUT_TYPE_BACKOFFICE) {
            $quote->getCustomer()->setGroupId($quote->getCustomerGroupId());
            $this->adminOrderCreateModel->setData(
                ['account' => ['email' => $quote->getCustomerEmail(), 'group_id' => $quote->getCustomerGroupId()]]
            )
                ->setQuote($quote)
                ->_prepareCustomer();
        }

        return $quote;
    }

    /**
     * Add user note as status history comment
     *
     * @param OrderModel $order
     * @param string     $userNote
     */
    public function setOrderUserNote($order, $userNote)
    {
        $order
            ->addStatusHistoryComment($userNote)
            ->setIsVisibleOnFront(true)
            ->setIsCustomerNotified(false);
        $order->setData($this->configHelper->getOrderCommentField($order->getStoreId()), $userNote);
    }

    /**
     * Creates a link to the transaction page on the Bolt merchant dasboard
     * to be saved with the order payment info message.
     *
     * @param string $reference
     * @return string
     */
    public function formatReferenceUrl($reference)
    {
        $url = $this->configHelper->getMerchantDashboardUrl().'/transaction/'.$reference;
        return '<a href="'.$url.'">'.$reference.'</a>';
    }

    /**
     * Get processed items (captures or refunds) as an array
     *
     * @param OrderPaymentInterface $payment
     * @param string $itemType 'captures' | 'refunds'
     * @return array
     */
    private function getProcessedItems($payment, $itemType)
    {
        return array_filter(explode(',', ($payment->getAdditionalInformation($itemType) ?: '')));
    }

    /**
     * Get processed capture ids array
     *
     * @param OrderPaymentInterface $payment
     * @return array
     */
    private function getProcessedCaptures($payment)
    {
        return $this->getProcessedItems($payment, 'captures');
    }

    /**
     * Get processed refund ids array
     *
     * @param OrderPaymentInterface $payment
     * @return array
     */
    private function getProcessedRefunds($payment)
    {
        return $this->getProcessedItems($payment, 'refunds');
    }

    /**
     * Format the (class internal) unambiguous transaction state out of the type, status and previously recordered status.
     * Infer eventually missing states. Represent Bolt partial capture AUTHORIZED state as CAPTURED.
     *
     * @param \stdClass $transaction
     * @param OrderPaymentInterface $payment
     * @param null $hookType
     * @return string
     */
    public function getTransactionState($transaction, $payment, $hookType = null)
    {
        $transactionType = $transaction->type;
        // If it is an apm type, it needs to behave as regular payment/credit.
        // Since there are previous states saved, it needs to mimic "cc_payment"/"cc_credit"
        if (in_array($transactionType, [self::TT_PAYPAL_PAYMENT, self::TT_APM_PAYMENT])) {
            $transactionType = self::TT_PAYMENT;
        }
        if (in_array($transactionType, [self::TT_PAYPAL_REFUND, self::TT_APM_REFUND])) {
            $transactionType = self::TT_CREDIT;
        }
        $transactionState = $transactionType.":".$transaction->status;
        $prevTransactionState = $payment->getAdditionalInformation('transaction_state');
        $transactionReference = $payment->getAdditionalInformation('transaction_reference');
        $transactionId = $payment->getAdditionalInformation('real_transaction_id');
        $processedCaptures = $this->getProcessedCaptures($payment);

        // No previous state recorded.
        // Unless the state is TS_ZERO_AMOUNT, TS_COMPLETED (valid start transaction states, as well as TS_PENDING)
        // or TS_CREDIT_COMPLETED (for historical reasons, old orders refund,
        // legacy code when order was created, no state recorded) put it in TS_PENDING state.
        // It can correlate with the $transactionState or not in case the hook is late due connection problems and
        // the status has changed in the meanwhile.
        if (!$this->featureSwitches->isAPIDrivenIntegrationEnabled() && !$prevTransactionState && !$transactionReference && !$transactionId) {
            if (in_array($transactionState, [self::TS_ZERO_AMOUNT, self::TS_COMPLETED, self::TS_CREDIT_COMPLETED])) {
                return $transactionState;
            }
            return self::TS_PENDING;
        }

        // The previously recorded state is either TS_PENDING or TS_REJECTED_REVERSIBLE. Authorization occurred but also
        // some of the funds were captured before the TS_AUTHORIZED state is recorded in Magento. Mark the transaction
        // as TS_AUTHORIZED and wait for the next hook to record the CAPTURE.
        if (in_array($prevTransactionState, [self::TS_PENDING, self::TS_REJECTED_REVERSIBLE]) &&
            $transactionState == self::TS_AUTHORIZED &&
            $transaction->captures
        ) {
            return self::TS_AUTHORIZED;
        }

        // The previously recorded state is either TS_PENDING or TS_REJECTED_REVERSIBLE and the transaction state is
        // TS_COMPLETED. If there is more than one capture in $transaction->captures array then the TS_AUTHORIZED state
        // is missing. Set the state to TS_AUTHORIZED and process captures on next hook requests.
        if (in_array($prevTransactionState, [self::TS_PENDING, self::TS_REJECTED_REVERSIBLE]) &&
            $transactionState == self::TS_COMPLETED &&
            count($transaction->captures) > 1
        ) {
            return self::TS_AUTHORIZED;
        }

        // The transaction was in TS_AUTHORIZED state, now it's TS_COMPLETED but not all partial captures are
        // processed. Mark it TS_CAPTURED.
        if ($prevTransactionState == self::TS_AUTHORIZED &&
            $transactionState == self::TS_COMPLETED &&
            count($transaction->captures) - count($processedCaptures) > 1
        ) {
            return self::TS_CAPTURED;
        }

        // The transaction was TS_AUTHORIZED, now it has captures, put it in TS_CAPTURED state.
        if ($transactionState == self::TS_AUTHORIZED &&
            $transaction->captures
        ) {
            return self::TS_CAPTURED;
        }

        // Previous partial capture was partially or fully refunded. Transaction is still TS_AUTHORIZED on Bolt side.
        // Set it to TS_CAPTURED.
        if ($prevTransactionState == self::TS_CREDIT_COMPLETED &&
            $transactionState == self::TS_AUTHORIZED
        ) {
            return self::TS_CAPTURED;
        }

        // The transaction was in TS_CAPTURED state, now it's TS_COMPLETED and hook type is TYPE_VOID
        // Mark it TS_PARTIAL_VOIDED.
        if ($prevTransactionState == self::TS_CAPTURED &&
            $transactionState == self::TS_COMPLETED &&
            $hookType == Transaction::TYPE_VOID
        ) {
            return self::TS_PARTIAL_VOIDED;
        }

        // return transaction state as it is in fetched transaction info. No need to change it.
        return $transactionState;
    }

    /**
     * Record total amount mismatch between magento and bolt order.
     * Log the error in order comments and report via bugsnag.
     * Put the order ON HOLD if it's a mismatch.
     *
     * @param OrderModel $order
     * @param \stdClass $transaction
     * @throws \Exception
     */
    private function holdOnTotalsMismatch($order, $transaction)
    {
        $currencyCode = $order->getOrderCurrencyCode();
        $boltTotal = $transaction->order->cart->total_amount->amount;
        $storeTotal = CurrencyUtils::toMinor($order->getGrandTotal(), $currencyCode);

        // Stop if no mismatch
        if ($boltTotal == $storeTotal) {
            return;
        }

        // Put the order ON HOLD and add the status message.
        // Do it once only, skip on subsequent hooks
        if ($order->getState() != OrderModel::STATE_HOLDED) {
            // Add order status history comment
            $comment = __(
                'BOLTPAY INFO :: THERE IS A MISMATCH IN THE ORDER PAID AND ORDER RECORDED.<br>
             Paid amount: %1 Recorded amount: %2<br>Bolt transaction: %3',
                CurrencyUtils::toMajor($boltTotal, $currencyCode),
                $order->getGrandTotal(),
                $this->formatReferenceUrl($transaction->reference)
            );
            $order->addStatusHistoryComment($comment);

            // Put the order on hold
            $this->setOrderState($order, OrderModel::STATE_HOLDED);
        }

        // Get the order and quote id
        $incrementId = isset($transaction->order->cart->display_id) ?
            $transaction->order->cart->display_id :
            null;
        $quoteId = isset($transaction->order) ?
            $this->cartHelper->getImmutableQuoteIdFromBoltOrder($transaction->order) :
            null;

        if (!$quoteId) {
            $quoteId = $transaction->order->cart->order_reference;
        }

        // If the quote exists collect cart data for bugsnag
        if ($quote = $this->cartHelper->getQuoteById($quoteId)) {
            $cart = $this->cartHelper->getCartData(true, false, $quote);
        } else {
            $cart = ['The quote does not exist.'];
        }

        // Log the debug info
        $this->bugsnag->registerCallback(function ($report) use (
            $transaction,
            $cart,
            $incrementId,
            $boltTotal,
            $storeTotal
        ) {
            $report->setMetaData([
                'TOTALS_MISMATCH' => [
                    'Reference' => $transaction->reference,
                    'Order ID' => $incrementId,
                    'Bolt Total' => $boltTotal,
                    'Store Total' => $storeTotal,
                    'Bolt Cart' => $transaction->order->cart,
                    'Store Cart' => $cart
                ]
            ]);
        });

        throw new LocalizedException(__(
            'Order Totals Mismatch Reference: %1 Order: %2 Bolt Total: %3 Store Total: %4',
            $transaction->reference,
            $incrementId,
            $boltTotal,
            $storeTotal
        ));
    }

    /**
     * Get (informal) transaction status to be stored with status history comment
     *
     * @param string $transactionState
     * @return string
     */
    private function getBoltTransactionStatus($transactionState)
    {
        return [
            self::TS_ZERO_AMOUNT => 'ZERO AMOUNT COMPLETED',
            self::TS_PENDING => 'UNDER REVIEW',
            self::TS_AUTHORIZED => 'AUTHORIZED',
            self::TS_CAPTURED => 'CAPTURED',
            self::TS_COMPLETED => 'COMPLETED',
            self::TS_CANCELED => 'CANCELED',
            self::TS_REJECTED_REVERSIBLE => 'REVERSIBLE REJECTED',
            self::TS_REJECTED_IRREVERSIBLE => 'IRREVERSIBLE REJECTED',
            self::TS_CREDIT_IN_PROGRESS => 'Refund is in progress. See actual refund status in Bolt merchant dashboard',
            self::TS_CREDIT_CREATED => 'Refund is in progress. See actual refund status in Bolt merchant dashboard',
            self::TS_CREDIT_COMPLETED => Hook::$fromBolt ? 'REFUNDED UNSYNCHRONISED' : 'REFUNDED'
        ][$transactionState];
    }

    /**
     * Generate data to be stored with the transaction
     *
     * @param OrderModel $order
     * @param \stdClass $transaction
     * @param null|int $amount
     * @return array containing transaction information
     */
    private function formatTransactionData($order, $transaction, $amount)
    {
        return [
            'Time' => $this->timezone->formatDateTime(
                date('Y-m-d H:i:s', (int)($transaction->date / 1000)),
                2,
                2
            ),
            'Reference' => $transaction->reference,
            'Amount' => $this->formatAmountForDisplay($order, $amount / 100),
            'Transaction ID' => $transaction->id
        ];
    }

    /**
     * Return the first unprocessed capture from the captures array (or null)
     *
     * @param OrderPaymentInterface $payment
     * @param \stdClass $transaction
     * @return mixed
     */
    private function getUnprocessedCapture($payment, $transaction)
    {
        try {
            $processedCaptures = $this->getProcessedCaptures($payment);
            $unprocessedCaptures = array_filter(
                $transaction->captures,
                function ($capture) use ($processedCaptures) {
                    return !in_array($capture->id, $processedCaptures) && $capture->status == 'succeeded';
                }
            );
            return end($unprocessedCaptures);
        } catch (\Exception $exception) {
            $this->bugsnag->notifyException($exception);
            return false;
        }
    }

    /**
     * Change order state taking transition constraints into account.
     *
     * @param OrderModel $order
     * @param string $state
     * @param bool $saveOrder
     */
    public function setOrderState($order, $state, $saveOrder = true)
    {
        $prevState = $order->getState();
        if ($state == OrderModel::STATE_HOLDED) {
            // Ensure order is in one of the "can hold" states [STATE_NEW | STATE_PROCESSING]
            // to avoid no state on admin order unhold
            if ($prevState !== OrderModel::STATE_PROCESSING) {
                $order->setState(OrderModel::STATE_PROCESSING);
                $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_PROCESSING));
            }
            try {
                $order->hold();
            } catch (\Exception $e) {
                // Put the order in "on hold" state even if the previous call fails
                $order->setState(OrderModel::STATE_HOLDED);
                $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_HOLDED));
            }
        } elseif ($state == OrderModel::STATE_CANCELED) {
            try {
                // Use registerCancellation method to cancel order and restock product saleable quantity
                $order->registerCancellation('', false);
            } catch (\Exception $e) {
                // Put the order in "cancelled" state even if the previous call fails
                $order->setState(OrderModel::STATE_CANCELED);
                $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_CANCELED));
            }
        } else {
            $order->setState($state);
            $order->setStatus($order->getConfig()->getStateDefaultStatus($state));
        }
        if ($saveOrder) {
           $order->save();
        }
    }

    /**
     * @param OrderModel $order
     */
    protected function cancelOrder($order)
    {
        try {
            $this->orderManagement->cancel($order->getEntityId());
            $order->setState(OrderModel::STATE_CANCELED);
            $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_CANCELED));
        } catch (\Exception $e) {
            // Put the order in "canceled" state even if the previous call fails
            $this->bugsnag->notifyException($e);
            $order->setState(OrderModel::STATE_CANCELED);
            $order->setStatus($order->getConfig()->getStateDefaultStatus(OrderModel::STATE_CANCELED));
        }
        $order->save();
    }

    /**
     * Check if order payment method was set to 'boltpay'
     *
     * @param OrderPaymentInterface $payment
     * @throws LocalizedException
     */
    private function checkPaymentMethod($payment)
    {
        $paymentMethod = $payment->getMethod();

        if ($paymentMethod != Payment::METHOD_CODE) {
            throw new LocalizedException(__(
                'Payment method assigned to order is: %1',
                $paymentMethod
            ));
        }
    }

    /**
     * Map class internal Bolt transaction state representation to Magento order state
     *
     * @param string $transactionState
     * @param OrderModel $order
     * @return string
     */
    public function transactionToOrderState($transactionState, $order)
    {
        if ($transactionState == self::TS_CREDIT_IN_PROGRESS || $transactionState == self::TS_CREDIT_CREATED || $transactionState == self::TS_CREDIT_COMPLETED) {
            if ($order->getTotalRefunded() == $order->getGrandTotal() && $order->getTotalRefunded() == $order->getTotalPaid() && !$order->canShip()) {
                return OrderModel::STATE_CLOSED;
            }
            return (Hook::$fromBolt && !$this->featureSwitches->isCreatingCreditMemoFromWebHookEnabled()) ? OrderModel::STATE_HOLDED : OrderModel::STATE_PROCESSING;
        }

        return [
            self::TS_ZERO_AMOUNT => OrderModel::STATE_PROCESSING,
            self::TS_PENDING => OrderModel::STATE_PAYMENT_REVIEW,
            self::TS_AUTHORIZED => OrderModel::STATE_PROCESSING,
            self::TS_CAPTURED => OrderModel::STATE_PROCESSING,
            self::TS_COMPLETED => OrderModel::STATE_PROCESSING,
            self::TS_CANCELED => OrderModel::STATE_CANCELED,
            self::TS_REJECTED_REVERSIBLE => OrderModel::STATE_PAYMENT_REVIEW,
            self::TS_REJECTED_IRREVERSIBLE => OrderModel::STATE_CANCELED,
        ][$transactionState];
    }

    /**
     * Update order payment / transaction data
     *
     * @param OrderModel $order
     * @param null|\stdClass $transaction
     * @param null|string $reference
     * @param null $hookType
     * @param null|array   $hookPayload
     *
     * @throws \Exception
     * @throws LocalizedException
     */
    public function updateOrderPayment($order, $transaction = null, $reference = null, $hookType = null, $hookPayload = null)
    {
        // Fetch transaction info if transaction is not passed as a parameter
        if ($reference && !$transaction) {
            $storeId = $order->getStoreId() ?: null;
            $transaction = $this->fetchTransactionInfo($reference, $storeId);
        } else {
            $reference = $transaction->reference;
        }

        /** @var OrderPaymentInterface $payment */
        $payment = $order->getPayment();

        $this->checkPaymentMethod($payment);

        // Get the last stored transaction parameters
        $prevTransactionState = $payment->getAdditionalInformation('transaction_state');
        $prevTransactionReference = $payment->getAdditionalInformation('transaction_reference');

        // Get the transaction state
        $transactionState = $this->getTransactionState($transaction, $payment, $hookType);

        $newCapture = in_array($transactionState, [self::TS_CAPTURED, self::TS_COMPLETED])
            ? $this->getUnprocessedCapture($payment, $transaction)
            : null;

        // Skip if there is no state change (i.e. fetch transaction call from admin panel / Payment model)
        // Reference check and $newCapture were added to support multiple refunds and captures,
        // valid same state transitions
        if ($transactionState == $prevTransactionState &&
            $reference == $prevTransactionReference &&
            !$newCapture &&
            !($hookType == Hook::HT_PENDING && $order->getState() == OrderModel::STATE_PENDING_PAYMENT) &&
            !($hookType == Hook::HT_AUTH && $order->getState() == OrderModel::STATE_PAYMENT_REVIEW)
        ) {
            if ($this->isAnAllowedUpdateFromAdminPanel($order, $transactionState)) {
                $payment->setIsTransactionApproved(true);
            }
            return;
        }

        // The order has already been canceled, i.e. PERMANENTLY REJECTED
        // Discard subsequent REJECTED_IRREVERSIBLE hooks processing and return (resulting in 200 OK response to Bolt)
        if ($transactionState == self::TS_REJECTED_IRREVERSIBLE &&
            $prevTransactionState == self::TS_REJECTED_IRREVERSIBLE
        ) {
            return;
        }

        // preset default payment / transaction values
        // before more specific changes below
        if ($newCapture) {
            $amount = $newCapture->amount->amount;
        } else {
            $amount = $transaction->amount->amount;
        }
        $transactionId = $transaction->id;

        $realTransactionId = $parentTransactionId = $payment->getAdditionalInformation('real_transaction_id');
        $realTransactionId = $realTransactionId ?: $transaction->id;
        $paymentAuthorized = (bool)$payment->getAdditionalInformation('authorized');

        $processedCaptures = $this->getProcessedCaptures($payment);
        $processedRefunds = $this->getProcessedRefunds($payment);

        switch ($transactionState) {

            case self::TS_ZERO_AMOUNT:
                $transactionType = Transaction::TYPE_ORDER;
                break;

            case self::TS_PENDING:
                $transactionType = Transaction::TYPE_ORDER;
                break;

            case self::TS_AUTHORIZED:
                $transactionType = Transaction::TYPE_AUTH;
                $transactionId = $transaction->id.'-auth';
                break;

            case self::TS_CAPTURED:
                if (!$newCapture) {
                    return;
                }
                $transactionType = Transaction::TYPE_CAPTURE;
                $transactionId = $transaction->id.'-capture-'.$newCapture->id;
                $parentTransactionId = $transaction->id.'-auth';
                break;

            case self::TS_PARTIAL_VOIDED:
                $authorizationTransaction = $payment->getAuthorizationTransaction();
                $authorizationTransaction->closeAuthorization();
                $order->addCommentToStatusHistory($this->getVoidMessage($payment));
                $order->save();
                return;

            case self::TS_COMPLETED:
                if (!$newCapture) {
                    return;
                }
                $transactionType = Transaction::TYPE_CAPTURE;
                if ($paymentAuthorized) {
                    $transactionId = $transaction->id.'-capture-'.$newCapture->id;
                    $parentTransactionId = $transaction->id.'-auth';
                } else {
                    $transactionId = $transaction->id.'-payment';
                }
                break;

            case self::TS_CANCELED:
                $transactionType = Transaction::TYPE_VOID;
                $transactionId = $transaction->id.'-void';
                $parentTransactionId = $paymentAuthorized ? $transaction->id.'-auth' : $transaction->id;
                break;

            case self::TS_REJECTED_REVERSIBLE:
                $transactionType = Transaction::TYPE_ORDER;
                $transactionId = $transaction->id.'-rejected_reversible';
                break;

            case self::TS_REJECTED_IRREVERSIBLE:
                $transactionType = Transaction::TYPE_ORDER;
                $transactionId = $transaction->id.'-rejected_irreversible';
                break;

            case self::TS_CREDIT_IN_PROGRESS:
            case self::TS_CREDIT_CREATED:
            case self::TS_CREDIT_COMPLETED:
                if (in_array($transaction->id, $processedRefunds)) {
                    return;
                }
                $transactionType = Transaction::TYPE_REFUND;
                $transactionId = $transaction->id . '-refund';

                if (Hook::$fromBolt && $this->featureSwitches->isCreatingCreditMemoFromWebHookEnabled()) {
                    $this->createCreditMemoForHookRequest($order, $transaction);
                }
                break;

            default:
                throw new LocalizedException(__(
                    'Unhandled transaction state : %1',
                    $transactionState
                ));
                break;
        }

        // format the last transaction data for storing within the order payment record instance

        if ($newCapture) {
            array_push($processedCaptures, $newCapture->id);
        }

        if ($transactionState == self::TS_CREDIT_COMPLETED) {
            array_push($processedRefunds, $transaction->id);
        }

        $paymentData = [
            'real_transaction_id' => $realTransactionId,
            'transaction_reference' => $transaction->reference,
            'transaction_state' => $transactionState,
            'authorized' => $paymentAuthorized || in_array($transactionState, [self::TS_AUTHORIZED, self::TS_CAPTURED]),
            'refunds' => implode(',', $processedRefunds),
            'processor' => $transaction->processor,
            'token_type' => $transaction->from_credit_card->token_type ?? $transaction->processor
        ];

        $message = __(
            'BOLTPAY INFO :: PAYMENT Status: %1 Amount: %2<br>Bolt transaction: %3',
            $this->getBoltTransactionStatus($transactionState),
            $this->formatAmountForDisplay($order, $amount / 100),
            $this->formatReferenceUrl($transaction->reference)
        );

        // update order payment instance
        $payment->setParentTransactionId($parentTransactionId);
        $payment->setTransactionId($transactionId);
        $payment->setLastTransId($transactionId);
        $payment->setAdditionalInformation(array_merge((array)$payment->getAdditionalInformation(), $paymentData));
        $payment->setIsTransactionClosed($transactionType != Transaction::TYPE_AUTH);

        $this->setOrderPaymentInfoData($payment, $transaction);

        if ($order->getState() === OrderModel::STATE_PENDING_PAYMENT) {
            // handle checkboxes and custom fields
            if (isset($hookPayload['checkboxes']) && $hookPayload['checkboxes']) {
                $this->checkboxesHandler->handle($order, $hookPayload['checkboxes']);
            }

            if (isset($hookPayload['custom_fields']) && $hookPayload['custom_fields']) {
                $this->customFieldsHandler->handle($order, $hookPayload['custom_fields']);
                $this->eventsForThirdPartyModules->dispatchEvent("afterHandleCustomField", $order, $hookPayload['custom_fields'], $transaction);
            }

            // set order state and status
            $this->resetOrderState($order);
        }
        $orderState = $this->transactionToOrderState($transactionState, $order);

        // If the action is not triggered by Bolt API request and transaction type is credit, there is not need to save order.
        $ifSaveOrder = Hook::$fromBolt || ($transactionState != self::TS_CREDIT_IN_PROGRESS && $transactionState != self::TS_CREDIT_COMPLETED && $transactionState != self::TS_CREDIT_CREATED);
        $this->setOrderState($order, $orderState, $ifSaveOrder);

        // Send order confirmation email to customer.
        if (! $order->getEmailSent()) {
            try {
                $this->emailSender->send($order);
            } catch (\Exception $e) {
                $this->bugsnag->notifyException($e);
            }
        }

        // We will create an invoice if we have zero amount or new capture.
        if (!$this->featureSwitches->isIgnoreHookForInvoiceCreationEnabled() && ($this->isCaptureHookRequest($newCapture) || $this->isZeroAmountHook($transactionState))) {
            $currencyCode = $order->getOrderCurrencyCode();
            $this->validateCaptureAmount($order, CurrencyUtils::toMajor($amount, $currencyCode));
            $captureAmount = $this->prepareCaptureAmountForInvoice($order, CurrencyUtils::toMajor($amount, $currencyCode));
            if ($captureAmount > 0) {
                $notify = $this->scopeConfig->isSetFlag(
                    InvoiceEmailIdentity::XML_PATH_EMAIL_ENABLED,
                    ScopeInterface::SCOPE_STORE,
                    $order->getStoreId());
                $invoice = $this->createOrderInvoice($order, $captureAmount, $notify, $realTransactionId);
            }
            $payment->setAdditionalInformation(
                array_merge((array)$payment->getAdditionalInformation(), ['captures' => implode(',', $processedCaptures)])
            );

        }

        if (!$order->getTotalDue()) {
            $payment->setShouldCloseParentTransaction(true);
        }

        if ($newCapture && !empty($invoice)) {
            $this->_eventManager->dispatch(
                'sales_order_payment_capture',
                ['payment' => $payment, 'invoice' => $invoice]
            );
        }

        $transactionData = $this->formatTransactionData($order, $transaction, $amount);
        // build a new transaction record and assign it to the order and payment
        /** @var Transaction $payment_transaction */
        $payment_transaction = $this->transactionBuilder->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($transactionId)
            ->setAdditionalInformation([
                Transaction::RAW_DETAILS => $transactionData
            ])
            ->setFailSafe(true)
            ->build($transactionType);

        $payment->addTransactionCommentsToOrder(
            $payment_transaction,
            $message
        );

        $payment_transaction->save();

        if ($transactionType == Transaction::TYPE_VOID || ($transactionType == Transaction::TYPE_CAPTURE && $this->isFullyCaptured($order, $transaction))) {
            try {
                $orderTransaction = $this->transactionRepository->getByTransactionType(
                    \Magento\Sales\Model\Order\Payment\Transaction::TYPE_AUTH,
                    $payment->getId(),
                    $order->getId()
                );
            } catch (\Magento\Framework\Exception\InputException $e) {
                $this->bugsnag->notifyException($e);
                $orderTransaction = null;
            }

            if ($orderTransaction && !$orderTransaction->getIsClosed()) {
                $orderTransaction->setIsClosed(1);
                $this->transactionRepository->save($orderTransaction);
            }
        }

        // save payment and order
        $payment->save();
        if ($ifSaveOrder) {
            $order->save();
        }

        $this->eventsForThirdPartyModules->dispatchEvent(
            'afterUpdateOrderPayment',
            $order,
            $transaction,
            $transactionState,
            $prevTransactionState
        );
    }

    /**
     * @param OrderInterface $order
     * @param \stdClass $transaction
     * @return bool
     */
    private function isFullyCaptured ($order, $transaction)
    {
        if (isset($transaction->captures)) {
            $capturedAmount = 0;
            foreach ($transaction->captures as $capture) {
                $capturedAmount += $capture->amount->amount;
            }

            $grandTotalConverted = CurrencyUtils::toMinor($order->getGrandTotal(), $order->getOrderCurrencyCode());
            if ($capturedAmount == $grandTotalConverted) {
                return true;
            } else {
                $diff = $capturedAmount - $grandTotalConverted;
                if (abs($diff) <= self::MISMATCH_TOLERANCE) {
                    return true;
                }
                return false;
            }
        }

        return false;
    }

    /**
     * Create an invoice for the order.
     *
     * @param OrderModel $order
     * @param string|null $transactionId
     * @param float $amount
     * @param bool $notify
     *
     * @return bool
     * @throws \Exception
     * @throws LocalizedException
     */
    public function createOrderInvoice($order, $amount, $notify = false, $transactionId = null)
    {
        $currencyCode = $order->getOrderCurrencyCode();
        try {
            if (CurrencyUtils::toMinor($order->getTotalInvoiced() + $amount, $currencyCode) === CurrencyUtils::toMinor($order->getGrandTotal(), $currencyCode)) {
                $invoice = $this->invoiceService->prepareInvoice($order);
            } else {
                $invoice = $this->invoiceService->prepareInvoiceWithoutItems($order, $amount);
            }
        } catch (\Exception $e) {
            $this->bugsnag->notifyException($e);
            throw $e;
        }
        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->setTransactionId($transactionId);
        $invoice->setGrandTotal($amount);
        $invoice->setBaseGrandTotal($amount);
        $invoice->register();
        $invoice->save();

        $order->addRelatedObject($invoice);

        // in unexpected case order total paid could be empty, we should set it
        if (!$order->getTotalPaid()) {
            if ($order->getTotalInvoiced()) {
                $order->setTotalPaid((float)$order->getTotalInvoiced());
            } else {
                $order->setTotalPaid($amount);
            }
        }

        // pre-save required order data with will be overwritten during invoice email sending
        $baseSubtotal = $order->getBaseSubtotal();
        $baseTaxAmount = $order->getBaseTaxAmount();
        $baseShippingAmount = $order->getBaseShippingAmount();
        if ($this->scopeConfig->isSetFlag(
            InvoiceEmailIdentity::XML_PATH_EMAIL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $order->getStoreId()
        ) && !$invoice->getEmailSent()) {
            try {
                $this->invoiceSender->send($invoice);
            } catch (\Exception $e) {
                $this->bugsnag->notifyException($e);
            }

            // restore order data
            $order->setBaseSubtotal($baseSubtotal);
            $order->setBaseTaxAmount($baseTaxAmount);
            $order->setBaseShippingAmount($baseShippingAmount);
            //Add notification comment to order
            $order->addStatusHistoryComment(
                __('Invoice #%1 is created. Notification email is sent to customer.', $invoice->getId())
            )->setIsCustomerNotified(true);
        }

        return $invoice;
    }

    /**
     * @param OrderModel $order
     * @param $transaction
     * @throws \Exception
     */
    public function createCreditMemoForHookRequest(OrderModel $order, $transaction)
    {
        $currencyCode = $order->getOrderCurrencyCode();
        $refundAmount = CurrencyUtils::toMajor($transaction->amount->amount, $currencyCode);
        $this->validateRefundAmount($order, $refundAmount);

        $boltTotal = CurrencyUtils::toMajor($transaction->order->cart->total_amount->amount, $currencyCode);
        $adjustment = [];
        if ($refundAmount < $boltTotal) {
            $adjustment = [
                'adjustment_positive' => $refundAmount,
                'shipping_amount' => 0
            ];

            foreach ($order->getAllItems() as $item) {
                $adjustment['qtys'][$item->getId()] = 0;
            }
        }

        $creditMemo = $this->creditmemoFactory->createByOrder($order, $adjustment);

        $creditMemo->setAutomaticallyCreated(true)
                    ->addComment(__('The credit memo has been created automatically.'));

        $this->creditmemoManagement->refund($creditMemo, true);
    }

    /**
     * @param OrderModel $order
     * @param $refundAmount
     * @throws \Exception
     */
    protected function validateRefundAmount(OrderModel $order, $refundAmount)
    {
        if (!isset($refundAmount) || !is_numeric($refundAmount) || $refundAmount < 0) {
            throw new \Exception(__('Refund amount is invalid'));
        }

        $totalRefunded = $order->getTotalRefunded() ?: 0;
        $totalPaid = $order->getTotalPaid();
        $availableRefund = CurrencyUtils::toMinor($totalPaid - $totalRefunded, $order->getOrderCurrencyCode());

        if ($availableRefund < $refundAmount) {
            throw new \Exception(
                __('Refund amount is invalid: refund amount [%1], available refund [%2]', $refundAmount, $availableRefund)
            );
        }
    }

    /**
     * @param $transactionState
     * @return bool
     */
    public function isZeroAmountHook($transactionState)
    {
        return Hook::$fromBolt && ($transactionState === self::TS_ZERO_AMOUNT);
    }

    /**
     * Check if the hook is a capture request
     *
     * @param \stdClass $newCapture first unprocessed capture from the captures array
     *
     * @return bool
     */
    protected function isCaptureHookRequest($newCapture)
    {
        return  Hook::$fromBolt && $newCapture;
    }

    /**
     * @param OrderInterface $order
     * @param float          $captureAmount
     *
     * @return void
     * @throws \Exception
     */
    protected function validateCaptureAmount(OrderInterface $order, $captureAmount)
    {
        if (!isset($captureAmount) || !is_numeric($captureAmount) || $captureAmount < 0) {
            throw new \Exception(__('Capture amount is invalid'));
        }
    }

    /**
     * @param OrderInterface $order
     * @param $captureAmount
     * @return int|mixed
     * @throws \Exception
     */
    protected function prepareCaptureAmountForInvoice(OrderInterface $order, $captureAmount)
    {
        $currencyCode = $order->getOrderCurrencyCode();
        $previouslyCaptured = CurrencyUtils::toMinor($order->getTotalInvoiced(), $currencyCode);
        $captureAmountMinor = CurrencyUtils::toMinor($captureAmount, $currencyCode);
        $totalInvoicedAfterCurrentCapture = $previouslyCaptured + $captureAmountMinor;
        $grandTotal = CurrencyUtils::toMinor($order->getGrandTotal(), $currencyCode);

        if ($totalInvoicedAfterCurrentCapture > $grandTotal) {
            $captureAmount = CurrencyUtils::toMajor($grandTotal - $previouslyCaptured, $currencyCode);
        }

        return $captureAmount;
    }

    /**
     * @param OrderInterface $order
     * @param $transactionState
     * @return bool
     */
    protected function isAnAllowedUpdateFromAdminPanel(OrderInterface $order, $transactionState)
    {
        return !Hook::$fromBolt &&
               in_array($transactionState, [self::TS_AUTHORIZED, self::TS_COMPLETED]) &&
               $order->getState() === OrderModel::STATE_PAYMENT_REVIEW;
    }

    /**
     * @param OrderPaymentInterface $payment
     *
     * @return \Magento\Framework\Phrase|string
     */
    protected function getVoidMessage(OrderPaymentInterface $payment)
    {
        $authorizationTransaction = $payment->getAuthorizationTransaction();
        /** @var OrderModel $order */
        $order = $payment->getOrder();
        $voidAmount = $order->getGrandTotal() - $order->getTotalPaid();

        $message = __('BOLT notification: Transaction authorization has been voided.');
        $message .= ' ' .__('Amount: %1.', $this->formatAmountForDisplay($order, $voidAmount));
        $message .= ' ' .__('Transaction ID: %1.', $authorizationTransaction->getHtmlTxnId());

        return $message;
    }

    // Visible for testing
    /**
     * @param OrderModel $order
     * @param float $amount
     *
     * @return string
     */
    public function formatAmountForDisplay($order, $amount)
    {
        return $order->getOrderCurrency()->formatTxt($amount);
    }

    /**
     * @param $displayId
     *
     * @return array - [incrementId, quoteId]
     */
    public function getDataFromDisplayID($displayId)
    {
        return array_pad(
            explode(' / ', $displayId),
            2,
            null
        );
    }

    /**
     * @param $quoteId
     * @return int|null
     */
    public function getStoreIdByQuoteId($quoteId)
    {
        if (empty($quoteId)) {
            return null;
        }
        $quote = $this->cartHelper->getQuoteById($quoteId);
        return $quote ? $quote->getStoreId() : null;
    }

    /**
     * @param string $displayId
     * @return int|null
     */
    public function getOrderStoreIdByDisplayId($displayId)
    {
        if (empty($displayId)) {
            return null;
        }

        $order = $this->getExistingOrder($displayId);

        return ($order && $order->getStoreId()) ? $order->getStoreId() : null;
    }

    /**
     * Cancels an order while allowing its quote to be re-used, used for failed_payment webhook
     *
     * @param string $displayId
     * @param string $immutableQuoteId
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     */
    public function cancelFailedPaymentOrder($displayId, $immutableQuoteId)
    {
        $order = $this->getExistingOrder($displayId);
        if (!$order || $order->getState() == OrderModel::STATE_CANCELED) {
            return;
        }
        if ($order->getState() !== OrderModel::STATE_PENDING_PAYMENT) {
            throw new BoltException(
                __(
                    'Order Delete Error. Order is in invalid state. Order #: %1 State: %2 Immutable Quote ID: %3',
                    $displayId,
                    $order->getState(),
                    $immutableQuoteId
                ),
                null,
                CreateOrder::E_BOLT_GENERAL_ERROR
            );
        }
        $this->orderManagement->cancel($order->getId());
        /** reload order because {@see \Bolt\Boltpay\Helper\Cart::getOrderByIncrementId} */
        $order = $this->orderRepository->get($order->getId());

        $parentQuoteId = $order->getQuoteId();
        $parentQuote = $this->cartHelper->getQuoteById($parentQuoteId);
        $this->_eventManager->dispatch(
            'sales_model_service_quote_submit_failure',
            [
                'order' => $order,
                'quote' => $parentQuote
            ]
        );

        // reactivate session quote - the condiotion excludes PPC quotes
        if ($parentQuoteId != $immutableQuoteId) {
            $this->cartHelper->quoteResourceSave($parentQuote->setIsActive(true));
        }
        // reset PPC quote checkout type so it can be treated as active
        if ($parentQuote->getBoltCheckoutType() == CartHelper::BOLT_CHECKOUT_TYPE_PPC_COMPLETE) {
            $parentQuote->setBoltCheckoutType(CartHelper::BOLT_CHECKOUT_TYPE_PPC);
            $this->cartHelper->quoteResourceSave($parentQuote->setIsActive(false));
        }
        $this->eventsForThirdPartyModules->dispatchEvent("beforeFailedPaymentOrderSave", $order);

        $order->addData(['quote_id' => null]);
        $order->addCommentToStatusHistory(__('BOLTPAY INFO :: Order was canceled due to Processor rejection'));
        $this->orderRepository->save($order);
    }
}
