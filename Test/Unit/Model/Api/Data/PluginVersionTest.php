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

namespace Bolt\Boltpay\Test\Unit\Model\Api;

use Bolt\Boltpay\Test\Unit\BoltTestCase;
use Bolt\Boltpay\Model\Api\Data\PluginVersion;

/**
 * Class PluginVersionTest
 * @package Bolt\Boltpay\Test\Unit\Model\Api
 */
class PluginVersionTest extends BoltTestCase
{
    /**
     * @var PluginVersion
     */
    protected $pluginVersion;

    protected function setUpInternal()
    {
        $this->pluginVersion = new PluginVersion();
    }

    /**
     * @test
     */
    public function setAndGetVersion()
    {
        $this->pluginVersion->setVersion('version');
        $this->assertEquals('version', $this->pluginVersion->getVersion());
    }

    /**
     * @test
     */
    public function setAndGetName()
    {
        $this->pluginVersion->setName('name');
        $this->assertEquals('name', $this->pluginVersion->getName());
    }

    /**
     * @test
     */
    public function jsonSerialize()
    {
        $this->pluginVersion->setName('test');
        $this->pluginVersion->setVersion(true);
        $result = $this->pluginVersion->jsonSerialize();
        $this->assertEquals([
            'name' => 'test',
            'version' => true
        ], $result);
    }
}
