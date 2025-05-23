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
 *
 * @copyright  Copyright (c) 2017-2024 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\Boltpay\Helper;

use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\CartFactory;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\CartItemFactory;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\Config as AutomatedTestingConfig;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\ConfigFactory;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\PriceProperty;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\PricePropertyFactory;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\StoreItem;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\StoreItemFactory;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\Address;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\AddressFactory;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\Order;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\OrderFactory;
use Bolt\Boltpay\Model\Api\Data\AutomatedTesting\OrderItemFactory;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface as ProductRepository;
use Magento\Catalog\Model\Product;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface as QuoteRepository;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface as OrderRepository;
use Magento\Sales\Model\Order as ModelOrder;
use Magento\Sales\Model\Order\Address as ModelOrderAddress;
/**
 * Helper for automated testing
 */
class AutomatedTesting extends AbstractHelper
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var StoreItemFactory
     */
    private $storeItemFactory;

    /**
     * @var CartFactory
     */
    private $cartFactory;

    /**
     * @var CartItemFactory
     */
    private $cartItemFactory;

    /**
     * @var PricePropertyFactory
     */
    private $pricePropertyFactory;

    /**
     * @var AddressFactory
     */
    private $addressFactory;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var OrderItemFactory
     */
    private $orderItemFactory;

    /**
     * @var ShippingMethodConverter
     */
    private $shippingMethodConverter;

    /**
     * @var Bugsnag
     */
    private $bugsnag;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var QuoteFactory
     */
    private $quoteFactory;

    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @param Context                 $context
     * @param ProductRepository       $productRepository
     * @param SearchCriteriaBuilder   $searchCriteriaBuilder
     * @param SortOrderBuilder        $sortOrderBuilder
     * @param ConfigFactory           $configFactory
     * @param StoreItemFactory        $storeItemFactory
     * @param CartFactory             $cartFactory
     * @param CartItemFactory         $cartItemFactory
     * @param PricePropertyFactory    $pricePropertyFactory
     * @param AddressFactory          $addressFactory
     * @param OrderFactory            $orderFactory
     * @param OrderItemFactory        $orderItemFactory
     * @param ShippingMethodConverter $shippingMethodConverter
     * @param Bugsnag                 $bugsnag
     * @param StoreManagerInterface   $storeManager
     * @param QuoteFactory            $quoteFactory
     * @param CartManagementInterface $quoteManagement
     * @param QuoteRepository         $quoteRepository
     * @param StockRegistryInterface  $stockRegistry
     * @param OrderRepository         $orderRepository
     */
    public function __construct(
        Context $context,
        ProductRepository $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        ConfigFactory $configFactory,
        StoreItemFactory $storeItemFactory,
        CartFactory $cartFactory,
        CartItemFactory $cartItemFactory,
        PricePropertyFactory $pricePropertyFactory,
        AddressFactory $addressFactory,
        OrderFactory $orderFactory,
        OrderRepository $orderRepository,
        OrderItemFactory $orderItemFactory,
        ShippingMethodConverter $shippingMethodConverter,
        Bugsnag $bugsnag,
        StoreManagerInterface $storeManager,
        QuoteFactory $quoteFactory,
        CartManagementInterface $quoteManagement,
        QuoteRepository $quoteRepository,
        StockRegistryInterface $stockRegistry
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->configFactory = $configFactory;
        $this->storeItemFactory = $storeItemFactory;
        $this->cartFactory = $cartFactory;
        $this->cartItemFactory = $cartItemFactory;
        $this->pricePropertyFactory = $pricePropertyFactory;
        $this->addressFactory = $addressFactory;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->orderItemFactory = $orderItemFactory;
        $this->shippingMethodConverter = $shippingMethodConverter;
        $this->bugsnag = $bugsnag;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->quoteManagement = $quoteManagement;
        $this->quoteRepository = $quoteRepository;
        $this->stockRegistry = $stockRegistry;
    }

    /**
     * Retrieve an Order from the repository
     *
     * @return Order|null
     */
    protected function getPastOrder()
    {
        $sortOrder = $this->sortOrderBuilder
            ->setField('entity_id')
            ->setDirection('DESC')
            ->create();

        $nonZeroDiscountTaxSearchCriteria = $this->searchCriteriaBuilder
            ->addFilter('discount_amount', 0, 'lt')
            ->addFilter('tax_amount', 0, 'gt')
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->setSortOrders([$sortOrder])
            ->create();

        $defaultSearchCritieria = $this->searchCriteriaBuilder
            ->setPageSize(1)
            ->setCurrentPage(1)
            ->setSortOrders([$sortOrder])
            ->create();

        $ordersFound = $this->orderRepository
            ->getList($nonZeroDiscountTaxSearchCriteria)
            ->getItems();

        if (count($ordersFound) === 0) {
            $ordersFound = $this->orderRepository
                ->getList($defaultSearchCritieria)
                ->getItems();
        }

        return count($ordersFound) > 0 ? reset($ordersFound) : null;
    }

    /**
     * Generate and return automated testing config
     *
     * @return AutomatedTestingConfig|string
     */
    public function getAutomatedTestingConfig()
    {
        try {
            $simpleProduct = $this->getProduct(\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE);
            if ($simpleProduct === null) {
                return 'no simple products found';
            }

            $virtualProduct = $this->getProduct(\Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL);
            $saleProduct = $this->getProduct('sale');
            $simpleStoreItem = $this->convertToStoreItem($simpleProduct, 'simple');
            $virtualStoreItem = $this->convertToStoreItem($virtualProduct, 'virtual');
            $saleStoreItem = $this->convertToStoreItem($saleProduct, 'sale');
            $storeItems = [];
            $storeItems[] = $simpleStoreItem;
            if ($virtualStoreItem !== null) {
                $storeItems[] = $virtualStoreItem;
            }
            if ($saleStoreItem !== null) {
                $storeItems[] = $saleStoreItem;
            }

            $quote = $this->createQuoteWithItem($simpleProduct);
            $shippingMethods = $this->getShippingMethods($quote);
            if (empty($shippingMethods)) {
                return 'no shipping methods found';
            }

            $quote->collectTotals();
            $this->quoteRepository->save($quote);
            $simpleCartItem = $this->cartItemFactory->create()
                ->setName($simpleStoreItem->getName())
                ->setPrice($simpleStoreItem->getPrice())
                ->setQuantity(1);
            $tax = $this->pricePropertyFactory->create()
                ->setName("tax")
                ->setPrice($this->formatPrice($quote->getShippingAddress()->getTaxAmount()));
            $cart = $this->cartFactory->create()
                ->setItems([$simpleCartItem])
                ->setShipping(reset($shippingMethods))
                ->setExpectedShippingMethods($shippingMethods)
                ->setTax($tax)
                ->setSubTotal($this->formatPrice($quote->getSubtotal()));
            $this->quoteRepository->delete($quote);

            return $this->configFactory->create()
                ->setStoreItems($storeItems)
                ->setCart($cart)
                ->setPastOrder($this->convertToOrder($this->getPastOrder()));
        } catch (Exception $e) {
            $this->bugsnag->notifyException($e);
            return $e->getMessage();
        }
    }

    /**
     * Return a product with the specified type
     *
     * @param string $type
     *
     * @return Product|null
     */
    protected function getProduct($type)
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilder
            ->addFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addFilter(
                'visibility',
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_NOT_VISIBLE,
                'neq'
            );
        if ($type === 'sale') {
            $searchCriteriaBuilder = $searchCriteriaBuilder->addFilter(
                'type_id',
                [\Magento\Catalog\Model\Product\Type::TYPE_SIMPLE, \Magento\Catalog\Model\Product\Type::TYPE_VIRTUAL],
                'in'
            );
        } else {
            $searchCriteriaBuilder = $searchCriteriaBuilder->addFilter('type_id', $type);
        }
        $searchCriteria = $searchCriteriaBuilder->create();

        $products = $this->productRepository->getList($searchCriteria)->getItems();
        foreach ($products as $product) {
            if ($this->stockRegistry->getStockItem($product->getId())->getIsInStock() && (
                $type !== 'sale' ||
                $product->getFinalPrice() < $product->getPriceInfo()->getPrice('regular_price')->getValue()
            )) {
                return $product;
            }
        }

        return null;
    }

    /**
     * Convert $product to a StoreItem
     *
     * @param Product|null $product
     * @param string       $type
     *
     * @return StoreItem|null
     */
    protected function convertToStoreItem($product, $type)
    {
        if ($product === null) {
            return null;
        }
        return $this->storeItemFactory->create()
            ->setItemUrl($product->getProductUrl())
            ->setName(trim($product->getName()))
            ->setPrice($this->formatPrice($product->getFinalPrice()))
            ->setType($type);
    }

    /**
     * Convert $address to an Address
     *
     * @param ModelOrderAddress|null $address
     *
     * @return Address|null
     */
    protected function convertToAddress($address)
    {
        if ($address === null) {
            return null;
        }
        return $this->addressFactory->create()
            ->setFirstName($address->getFirstName())
            ->setLastName($address->getLastName())
            ->setStreet(implode(" ", $address->getStreet()))
            ->setCity($address->getCity())
            ->setRegion($address->getRegion())
            ->setPostalCode($address->getPostCode())
            ->setTelephone($address->getTelephone())
            ->setCountry($address->getCountryId());
    }

    /**
     * Convert $order to an Order
     *
     * @param ModelOrder|null $order
     *
     * @return Order|null
     */
    protected function convertToOrder($order)
    {
        if ($order === null) {
            return null;
        }
        $orderItems = [];
        foreach($order->getAllVisibleItems() as $item) {
            $orderItems[] = $this->orderItemFactory->create()
                ->setProductName($item->getName())
                ->setProductSku($item->getSku())
                ->setProductUrl($item->getProduct()->getProductUrl())
                ->setPrice($this->formatPrice($item->getPrice()))
                ->setQuantityOrdered($item->getQtyOrdered())
                ->setSubtotal($this->formatPrice($item->getRowTotal()))
                ->setTaxAmount($this->formatPrice($item->getTaxAmount()))
                ->setTaxPercent($item->getTaxPercent())
                ->setDiscountAmount($this->formatPrice($item->getDiscountAmount()))
                ->setTotal($this->formatPrice(
                    $item->getRowTotal() +
                    $item->getTaxAmount() -
                    $item->getDiscountAmount()
                ));
        }

        return $this->orderFactory->create()
            ->setBillingAddress($this->convertToAddress($order->getBillingAddress()))
            ->setShippingAddress($this->convertToAddress($order->getShippingAddress()))
            ->setShippingMethod($order->getShippingDescription())
            ->setShippingAmount($this->formatPrice($order->getShippingAmount(), true))
            ->setOrderItems($orderItems)
            ->setSubtotal($this->formatPrice($order->getSubtotal()))
            ->setTax($this->formatPrice($order->getTaxAmount()))
            ->setDiscount($this->formatPrice($order->getDiscountAmount()))
            ->setGrandTotal($this->formatPrice($order->getGrandTotal()));
    }

    /**
     * Create a quote containing $product and add the shipping address used by integration tests
     *
     * @param Product $product
     *
     * @return Quote
     */
    protected function createQuoteWithItem($product)
    {
        $quoteId = $this->quoteManagement->createEmptyCart();
        $quote = $this->quoteFactory->create()->load($quoteId);
        $quote->setStoreId($this->storeManager->getStore()->getId());
        $quote->addProduct($product, 1);
        $quote->getShippingAddress()->addData([
            'street'     => '1235 Howard St Ste D',
            'city'       => 'San Francisco',
            'country_id' => 'US',
            'region'     => 'CA',
            'postcode'   => '94103'
        ]);
        $this->quoteRepository->save($quote);
        return $quote;
    }

    /**
     * Return the shipping methods for $quote
     *
     * @param Quote $quote
     *
     * @return PriceProperty[]
     */
    protected function getShippingMethods($quote)
    {
        $address = $quote->getShippingAddress();

        $flattenedRates = [];
        foreach ($address->getGroupedAllShippingRates() as $carrierRates) {
            foreach ($carrierRates as $rate) {
                $flattenedRates[] = $this->shippingMethodConverter->modelToDataObject($rate, 'USD');
            }
        }

        if (empty($flattenedRates)) {
            return [];
        }

        $firstShippingMethod = reset($flattenedRates);
        $address->setCollectShippingRates(true)
            ->setShippingMethod($firstShippingMethod->getCarrierCode() . '_' . $firstShippingMethod->getMethodCode())
            ->save();

        $shippingMethods = [];
        foreach ($flattenedRates as $rate) {
            $shippingMethodName = $rate->getCarrierTitle() . ' - ' . $rate->getMethodTitle();
            $shippingMethodPrice = $this->formatPrice($rate->getAmount(), true);
            $shippingMethods[] = $this->pricePropertyFactory->create()
                ->setName($shippingMethodName)
                ->setPrice($shippingMethodPrice);
        }

        return $shippingMethods;
    }

    /**
     * Format price
     *
     * @param float $price
     * @param bool  $isShipping
     *
     * @return string
     */
    private function formatPrice($price, $isShipping = false)
    {
        return $price === 0.0 && $isShipping ? 'Free' : '$' . number_format($price, 2, '.', '');
    }
}
