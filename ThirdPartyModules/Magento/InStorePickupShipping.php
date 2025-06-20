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
 * @copyright  Copyright (c) 2017-2023 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\Boltpay\ThirdPartyModules\Magento;

use Bolt\Boltpay\Helper\Bugsnag;
use Bolt\Boltpay\Api\Data\StoreAddressInterfaceFactory;
use Bolt\Boltpay\Api\Data\ShipToStoreOptionInterfaceFactory;
use Bolt\Boltpay\Api\Data\ShippingOptionInterfaceFactory;
use Bolt\Boltpay\Helper\Shared\CurrencyUtils;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\ShippingMethodExtensionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\InventoryInStorePickupShippingApi\Model\Carrier\InStorePickup;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;

class InStorePickupShipping
{
    /**
     * @var Bugsnag Bugsnag helper instance
     */
    private $bugsnagHelper;

    /**
     * @var StoreAddressInterfaceFactory
     */
    protected $storeAddressFactory;

    /**
     * @var ShipToStoreOptionInterfaceFactory
     */
    protected $shipToStoreOptionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface
     */
    protected $searchRequestBuilder;

    /**
     * @var Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface
     */
    protected $getPickupLocations;

    /**
     * @var Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoInterfaceFactory
     */
    protected $productInfo;

    /**
     * @var Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionFactory
     */
    protected $searchRequestExtension;

    /**
     * @var Magento\InventoryInStorePickup\Model\SearchRequest\Area\GetDistanceToSources
     */
    protected $getDistanceToSources;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var \Magento\Catalog\Api\Data\ShippingMethodExtensionFactory
     */
    private $extensionFactory;

    /**
     * @var ShippingOptionInterfaceFactory
     */
    private $shippingOptionFactory;

    /**
     * @var Json
     */
    private $json;

    private const SEARCH_RADIUS = 'carriers/instore/search_radius';

    private const CARRIER_CODE = 'instore';

    private const METHOD_CODE  = 'pickup';

    /**
     * @param Bugsnag     $bugsnagHelper Bugsnag helper instance
     * @param Discount    $discountHelper
     * @param Cart        $cartHelper
     * @param Decider     $featureSwitches
     * @param CartRepositoryInterface        $quoteRepository
     * @param ShippingMethodExtensionFactory $extensionFactory
     * @param ShippingOptionInterfaceFactory $shippingOptionFactory
     * @param Json|null                      $json
     */
    public function __construct(
        Bugsnag  $bugsnagHelper,
        StoreAddressInterfaceFactory $storeAddressFactory,
        ShipToStoreOptionInterfaceFactory $shipToStoreOptionFactory,
        ScopeConfigInterface $config,
        CartRepositoryInterface $quoteRepository,
        ShippingMethodExtensionFactory $extensionFactory,
        ShippingOptionInterfaceFactory $shippingOptionFactory,
        ?Json $json = null
    ) {
        $this->bugsnagHelper = $bugsnagHelper;
        $this->storeAddressFactory = $storeAddressFactory;
        $this->shipToStoreOptionFactory = $shipToStoreOptionFactory;
        $this->config = $config;
        $this->quoteRepository          = $quoteRepository;
        $this->extensionFactory         = $extensionFactory;
        $this->shippingOptionFactory    = $shippingOptionFactory;
        $this->json                     = $json ?: ObjectManager::getInstance()->get(Json::class);
    }

    /**
     * @param array $result
     * @param Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface $searchRequestBuilder
     * @param Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface     $getPickupLocations
     * @param Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoInterfaceFactory $productInfo
     * @param Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionFactory $searchRequestExtension
     * @param Magento\InventoryInStorePickup\Model\SearchRequest\Area\GetDistanceToSources $getDistanceToSources
     * @param Magento\Quote\Model\Quote $quote
     * @param array $shippingOptions
     * @param array $addressData
     * @return array
     */
    public function getShipToStoreOptions(
        $result,
        $searchRequestBuilder,
        $getPickupLocations,
        $productInfo,
        $searchRequestExtension,
        $getDistanceToSources,
        $quote,
        $shippingOptions,
        $addressData
    ) {
        try {
            if (empty($shippingOptions)) {
                return $result;
            }

            $tmpShippingOptions = [];
            $inStorePickupCost = 0;
            $hasInStorePickup = false;
            foreach ($shippingOptions as $shippingOption) {
                if ($shippingOption->getReference() == InStorePickup::DELIVERY_METHOD) {
                    $hasInStorePickup = true;
                    $inStorePickupCost = $shippingOption->getCost();
                } else {
                    $tmpShippingOptions[] = $shippingOption;
                }
            }

            if (!$hasInStorePickup) {
                return $result;
            }

            $this->searchRequestBuilder = $searchRequestBuilder;
            $this->getPickupLocations = $getPickupLocations;
            $this->productInfo = $productInfo;
            $this->searchRequestExtension = $searchRequestExtension;
            $this->getDistanceToSources = $getDistanceToSources;

            $productsInfo = [];
            $items = $quote->getAllVisibleItems();
            foreach ($items as $item) {
                $itemSku = trim($item->getSku());
                $productsInfo[] = $this->productInfo->create(['sku' => $itemSku]);
            }
            $extensionAttributes = $this->searchRequestExtension->create();
            $extensionAttributes->setProductsInfo($productsInfo);
            $searchRadius = (float)$this->config->getValue(
                self::SEARCH_RADIUS,
                ScopeInterface::SCOPE_WEBSITE
            );
            $searchTerm = $addressData['postal_code'] . ':' . $addressData['country_code'];
            $searchRequest = $this->searchRequestBuilder->setScopeCode($quote->getStore()->getWebsite()->getCode())
                        ->setScopeType(SalesChannelInterface::TYPE_WEBSITE)
                        ->setAreaRadius($searchRadius)
                        ->setAreaSearchTerm($searchTerm)
                        ->setSearchRequestExtension($extensionAttributes)
                        ->setPageSize(50)
                        ->create();
            $searchResult = $this->getPickupLocations->execute($searchRequest);
            $distanceToSources = $this->getDistanceToSources->execute($searchRequest->getArea());
            $shipToStoreOptions = [];
            if ($searchResult->getTotalCount() !== 0) {
                $items = $searchResult->getItems();
                foreach ($items as $item) {
                    $storeAddress = $this->storeAddressFactory->create();
                    $storeAddress->setStreetAddress1($item->getStreet());
                    $storeAddress->setStreetAddress2('');
                    $storeAddress->setLocality($item->getCity());
                    $storeAddress->setRegion($item->getRegion());
                    $storeAddress->setPostalCode($item->getPostcode());
                    $storeAddress->setCountryCode($item->getCountryId());

                    $shipToStoreOption = $this->shipToStoreOptionFactory->create();
                    $pickupLocationCode = $item->getPickupLocationCode();

                    $shipToStoreOption->setReference(InStorePickup::DELIVERY_METHOD . '_' . $pickupLocationCode);
                    $shipToStoreOption->setCost($inStorePickupCost);
                    $shipToStoreOption->setStoreName($item->getName());
                    $shipToStoreOption->setAddress($storeAddress);
                    $shipToStoreOption->setDistance($distanceToSources[$pickupLocationCode]);
                    $shipToStoreOption->setDistanceUnit('km');

                    $shipToStoreOptions[] = $shipToStoreOption;
                }
            }

            $result = [$shipToStoreOptions, $tmpShippingOptions];
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        } finally {
            return $result;
        }
    }

    /**
     * @param Magento\InventoryInStorePickupQuote\Model\Address\SetAddressPickupLocation $setAddressPickupLocation
     * @param Magento\Checkout\Api\Data\TotalsInformationInterface $addressInformation
     * @param Magento\Quote\Model\Quote $quote
     * @param array                     $shipping_option
     * @param array                     $ship_to_store_option
     * @param array                     $addressData
     */
    public function setExtraAddressInformation(
        $setAddressPickupLocation,
        $addressInformation,
        $quote,
        $shipping_option,
        $ship_to_store_option,
        $addressData
    ) {
        try {
            $carrierCode = $addressInformation->getShippingCarrierCode();
            $methodCode = $addressInformation->getShippingMethodCode();
            if ($carrierCode . '_' . $methodCode == InStorePickup::DELIVERY_METHOD) {
                $shippingAddress = $quote->getShippingAddress();
                $pickupLocation = substr_replace(
                    $ship_to_store_option['reference'],
                    '',
                    0,
                    strlen(InStorePickup::DELIVERY_METHOD . '_')
                );
                $setAddressPickupLocation->execute($shippingAddress, $pickupLocation);
            }
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }
    }

    /**
     * @param array                     $result
     * @param Magento\Quote\Model\Quote $quote
     * @param array                     $ship_to_store_option
     * @param array                     $addressData
     * @return array
     */
    public function getShipToStoreCarrierMethodCodes(
        $result,
        $quote,
        $ship_to_store_option,
        $addressData
    ) {
        $referenceCodes = explode('_', $ship_to_store_option['reference']);
        if ($this->checkIfMagentoInStorePickupByCode($referenceCodes)) {
            return [$referenceCodes[0], $referenceCodes[1]];
        }

        return $result;
    }

    /**
     * @param Magento\InventoryInStorePickupQuote\Model\Address\SetAddressPickupLocation $setAddressPickupLocation
     * @param Magento\Quote\Model\Quote $quote
     * @param \stdClass                 $transaction
     */
    public function setInStoreShippingMethodForPrepareQuote(
        $setAddressPickupLocation,
        $quote,
        $transaction
    ) {
        try {
            $shipment = $transaction->order->cart->in_store_shipments[0]->shipment;
            $referenceCodes = explode('_', $shipment->reference);
            if ($this->checkIfMagentoInStorePickupByCode($referenceCodes)) {
                $shippingAddress = $quote->getShippingAddress();
                $shippingAddress->setCollectShippingRates(true);
                $pickupLocation = substr_replace(
                    $shipment->reference,
                    '',
                    0,
                    strlen(InStorePickup::DELIVERY_METHOD . '_')
                );
                $setAddressPickupLocation->execute($shippingAddress, $pickupLocation);
                $shippingAddress->setShippingMethod(InStorePickup::DELIVERY_METHOD)->save();
            }
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }
    }

    /**
     * @param Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress $addressConverter
     * @param Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface $getPickupLocation
     * @param Magento\Quote\Model\Quote $quote
     * @param \stdClass                 $transaction
     */
    public function setInStoreShippingAddressForPrepareQuote(
        $addressConverter,
        $getPickupLocation,
        $quote,
        $transaction
    ) {
        try {
            $referenceCodes = explode('_', $transaction->order->cart->in_store_shipments[0]->shipment->reference);
            if (!$this->checkIfMagentoInStorePickupByCode($referenceCodes)) {
                return;
            }
            $shippingAddress = $quote->getShippingAddress();
            if (isset($transaction->order->cart->in_store_shipments[0]->shipment)) {
                $shipment = $transaction->order->cart->in_store_shipments[0]->shipment;
                $shippingAddress->setData('firstname', $transaction->order->cart->in_store_shipments[0]->store_name);
                $shippingAddress->setData('lastname', 'Store');
                $shippingAddress->save();
                $pickupLocationCode = substr_replace(
                    $shipment->reference,
                    '',
                    0,
                    strlen(InStorePickup::DELIVERY_METHOD . '_')
                );
            } else {
                // For payment only
                $pickupLocationCode = $shippingAddress->getExtensionAttributes()->getPickupLocationCode();
            }
            if (!empty($pickupLocationCode)) {
                $pickupLocation = $getPickupLocation->execute(
                    $pickupLocationCode,
                    SalesChannelInterface::TYPE_WEBSITE,
                    $quote->getStore()->getWebsite()->getCode()
                );
                $extraData = ['telephone'=>$pickupLocation->getPhone()];
                $shippingAddress = $addressConverter->convert($pickupLocation, $shippingAddress, $extraData);
                $quote->setShippingAddress($shippingAddress)->save();
            }
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }
    }

    /**
     * @param Magento\InventoryInStorePickupQuote\Model\ToQuoteAddress $addressConverter
     * @param Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface $getPickupLocation
     * @param Magento\Quote\Model\Quote $quote
     * @param \stdClass                 $transaction
     * @return array
     */
    public function isInStorePickupShipping(
        $result,
        $quote,
        $transaction
    ) {
        if (isset($transaction->order->cart->in_store_shipments[0]->shipment->shipping_address)) {
            $referenceCodes = explode('_', $transaction->order->cart->in_store_shipments[0]->shipment->reference);
            if ($this->checkIfMagentoInStorePickupByCode($referenceCodes)) {
                $address = $transaction->order->cart->in_store_shipments[0]->shipment->shipping_address ?? null;
                return $address;
            }
        } elseif (isset($transaction->order->cart->shipments[0]->shipping_address)) {
            $address = $transaction->order->cart->shipments[0]->shipping_address ?? null;
            $referenceShipmentMethod = $transaction->order->cart->shipments[0]->reference ?? null;
            if ($address && InStorePickup::DELIVERY_METHOD === $referenceShipmentMethod) {
                return $address;
            }
        }

        return $result;
    }

    /**
     * @param array $referenceCodes
     * @return bool
     */
    private function checkIfMagentoInStorePickupByCode($referenceCodes)
    {
        if (count($referenceCodes) > 2 &&
            $referenceCodes[0] . '_' . $referenceCodes[1] == InStorePickup::DELIVERY_METHOD) {
            return true;
        }

        return false;
    }

    /**
     * @param ShippingMethodInterface[] $result
     * @param Magento\InventoryInStorePickupApi\Model\SearchRequestBuilderInterface $searchRequestBuilder
     * @param Magento\InventoryInStorePickupApi\Api\GetPickupLocationsInterface     $getPickupLocations
     * @param Magento\InventoryInStorePickupApi\Api\Data\SearchRequest\ProductInfoInterfaceFactory $productInfo
     * @param Magento\InventoryInStorePickupApi\Api\Data\SearchRequestExtensionFactory $searchRequestExtension
     * @param Magento\InventoryInStorePickup\Model\SearchRequest\Area\GetDistanceToSources $getDistanceToSources
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     *
     * @return ShippingMethodInterface[] An array of shipping methods.
     */
    public function afterEstimateByExtendedAddress(
        $result,
        $searchRequestBuilder,
        $getPickupLocations,
        $productInfo,
        $searchRequestExtension,
        $getDistanceToSources,
        $cartId,
        $address
    ) {
        try {
            $tmpResult = [];
            foreach ($result as $shippingMethod) {
                if ($shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode() == InStorePickup::DELIVERY_METHOD) {
                    $shippingOptions = [];
                    $service = $shippingMethod->getCarrierTitle() . ' - ' . $shippingMethod->getMethodTitle();
                    $method  = $shippingMethod->getCarrierCode() . '_' . $shippingMethod->getMethodCode();
                    $majorAmount = $shippingMethod->getAmount();
                    $quote = $this->quoteRepository->getActive($cartId);
                    $currencyCode = $quote->getQuoteCurrencyCode();
                    $cost = CurrencyUtils::toMinor($majorAmount, $currencyCode);
                    $shippingOptions[] = $this->shippingOptionFactory
                                            ->create()
                                            ->setService($service)
                                            ->setCost($cost)
                                            ->setReference($method);
                    $shippingAddress = $quote->getShippingAddress();
                    if ($shippingAddress &&
                        ($country_code = $shippingAddress->getCountryId()) &&
                        ($postal_code  = $shippingAddress->getPostcode())
                    ) {
                        $addressData = [
                            'country_code' => $country_code,
                            'postal_code'  => $postal_code,
                            'region'       => $shippingAddress->getRegion(),
                            'locality'     => $shippingAddress->getCity(),
                            'street_address1' => $shippingAddress->getStreetLine(1),
                            'street_address2' => $shippingAddress->getStreetLine(2),
                        ];
                        list($shipToStoreOptions, $shippingOptions) = $this->getShipToStoreOptions(
                            [[],$shippingOptions],
                            $searchRequestBuilder,
                            $getPickupLocations,
                            $productInfo,
                            $searchRequestExtension,
                            $getDistanceToSources,
                            $quote,
                            $shippingOptions,
                            $addressData
                        );
                        if (!empty($shipToStoreOptions)) {
                            $extensibleAttribute =  ($shippingMethod->getExtensionAttributes())
                                ? $shippingMethod->getExtensionAttributes()
                                : $this->extensionFactory->create();
                            $locations = $this->json->serialize($shipToStoreOptions);
                            $extensibleAttribute->setBoltShipToStoreOptions($locations);
                            $shippingMethod->setExtensionAttributes($extensibleAttribute);
                        }
                    }
                }
                $tmpResult[] = $shippingMethod;
            }
            $result = $tmpResult;
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }

        return $result;
    }

    /**
     * @param Magento\InventoryInStorePickupQuote\Model\Address\SetAddressPickupLocation $setAddressPickupLocation
     * @param Magento\InventoryInStorePickupApi\Model\GetPickupLocationInterface $getPickupLocation
     * @param int $cartId
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     */
    public function beforeSaveAddressInformation(
        $setAddressPickupLocation,
        $getPickupLocation,
        $cartId,
        $addressInformation
    ) {
        try {
            $quote = $this->quoteRepository->getActive($cartId);
            if ($quote->isVirtual() || !$quote->getItemsCount()) {
                return;
            }
            $carrierCode = $addressInformation->getShippingCarrierCode();
            if ($carrierCode != self::CARRIER_CODE) {
                return;
            }
            $methodCode = $addressInformation->getShippingMethodCode();
            $mixCodes = explode('_', $methodCode);
            if (count($mixCodes) != 2 || $mixCodes[0] != self::METHOD_CODE) {
                return;
            }
            $pickupLocationCode = $mixCodes[1];
            $pickupLocation = $getPickupLocation->execute(
                $pickupLocationCode,
                SalesChannelInterface::TYPE_WEBSITE,
                $quote->getStore()->getWebsite()->getCode()
            );
            if ($pickupLocation) {
                $shippingAddress = $addressInformation->getShippingAddress();
                $shippingAddress->setFirstname($pickupLocation->getName());
                $shippingAddress->setLastname($pickupLocationCode);
                if ($pickupLocation->getPhone()) {
                    $shippingAddress->setTelephone($pickupLocation->getPhone());
                }
                $addressInformation->setShippingMethodCode(self::METHOD_CODE);
                $setAddressPickupLocation->execute($shippingAddress, $pickupLocationCode);
            }
        } catch (\Exception $e) {
            $this->bugsnagHelper->notifyException($e);
        }
    }
}
