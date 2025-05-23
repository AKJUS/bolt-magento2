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
 * @copyright  Copyright (c) 2024 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\Boltpay\Test\Unit\Model\Api\Data;

use Bolt\Boltpay\Test\Unit\BoltTestCase;
use Bolt\Boltpay\Model\Api\Data\ShippingOption;
use Bolt\Boltpay\Model\Api\Data\ShipToStoreOption;
use Bolt\Boltpay\Api\Data\ShippingDataInterface;
use Bolt\Boltpay\Api\Data\ShipToStoreOptionInterface;
use Bolt\Boltpay\Model\Api\Data\ShippingData;

/**
 * Class ShippingDataTest
 * @package Bolt\Boltpay\Test\Unit\Model\Api\Data
 * @coversDefaultClass \Bolt\Boltpay\Model\Api\Data\ShippingData
 */
class ShippingDataTest extends BoltTestCase
{
    /**
     * @var ShippingDataInterface
     */
    private $shippingData;

    /**
     * @var ShippingOption[]
     */
    private $shippingOptions;
    
    /**
     * @var ShipToStoreOption[]
     */
    private $shipToStoreOptions = [];

    protected function setUpInternal()
    {
        $this->shippingOptions = [new ShippingOption];
        $this->shipToStoreOptions = [new ShipToStoreOption];
        $this->shippingData = new ShippingData;
        $this->shippingData->setShippingOptions($this->shippingOptions);
        $this->shippingData->setShipToStoreOptions($this->shipToStoreOptions);
    }

    /**
     * @test
     * that getShippingOptions would return shipping options
     * @covers ::getShippingOptions
     */
    public function getShippingOptions()
    {
        $this->assertEquals($this->shippingOptions, $this->shippingData->getShippingOptions());
    }

    /**
     * @test
     * that setShippingOptions would set instance of shipping option
     * @covers ::setShippingOptions
     */
    public function setShippingOptions()
    {
        $result = $this->shippingData->setShippingOptions($this->shippingOptions);
        $this->assertInstanceOf(ShippingData::class, $result);
    }
    
    /**
     * @test
     * that getShipToStoreOptions would return ship to store options
     * @covers ::getShipToStoreOptions
     */
    public function getShipToStoreOptions()
    {
        $this->assertEquals($this->shipToStoreOptions, $this->shippingData->getShipToStoreOptions());
    }

    /**
     * @test
     * that setShipToStoreOptions would set instances of ship to store options
     * @covers ::setShipToStoreOptions
     */
    public function setShipToStoreOptions()
    {
        $result = $this->shippingData->setShipToStoreOptions($this->shipToStoreOptions);
        $this->assertInstanceOf(ShippingData::class, $result);
    }

    /**
     * @test
     * that jsonSerialize result would include shipping options
     * @covers ::jsonSerialize
     */
    public function jsonSerialize()
    {
        $result = $this->shippingData->jsonSerialize();
        $this->assertEquals([
            'shipping_options' => $this->shippingOptions,
            'ship_to_store_options' => $this->shipToStoreOptions
        ], $result);
    }
}
