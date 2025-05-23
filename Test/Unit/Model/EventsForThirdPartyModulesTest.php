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

namespace Bolt\Boltpay\Test\Unit\Model;

use Bolt\Boltpay\Test\Unit\BoltTestCase;
use Bolt\Boltpay\Model\EventsForThirdPartyModules;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class ThirdPartyModuleFactoryTest
 * @package Bolt\Boltpay\Test\Unit\Model
 */
class EventsForThirdPartyModuleTest extends BoltTestCase
{

    private function configCheckOneConst($eventsListeners)
    {
        foreach ($eventsListeners as $eventName => $eventListeners) {
            foreach ($eventListeners["listeners"] as $listener) {
                static::assertArrayHasKey('module', $listener);
                $has3pClasses = isset($listener["checkClasses"])
                                ? (is_array($listener["checkClasses"]) && count($listener["checkClasses"])>=1)
                                : (isset($listener["sendClasses"]) ? (is_array($listener["sendClasses"]) && count($listener["sendClasses"])>=1) : true);
                static::assertTrue($has3pClasses);
                $boltClass = Bootstrap::getObjectManager()->create($listener["boltClass"]);
                static::assertTrue(method_exists($boltClass, $eventName));
            }
        }
    }

    /**
     * @test
     */
    public function configTest()
    {
        
        $this->configCheckOneConst(EventsForThirdPartyModules::eventListeners);
        $this->configCheckOneConst(EventsForThirdPartyModules::filterListeners);
    }

    /**
     * @test
     */
    public function dispatchEventTest()
    {
        
        $eventsForThirdPartyModulesMock = Bootstrap::getObjectManager()->get(EventsForThirdPartyModulesMock::class);
        $listenerMock = Bootstrap::getObjectManager()->get(ListenerMock::class);
        
        $eventsForThirdPartyModulesMock->dispatchEvent("classDoesNotExist");
        static::assertFalse($listenerMock->methodCalled);
        
        $eventsForThirdPartyModulesMock->dispatchEvent("moduleDoesNotEnabled");
        static::assertFalse($listenerMock->methodCalled);
        
        $eventsForThirdPartyModulesMock->dispatchEvent("shouldCall");
        static::assertTrue($listenerMock->methodCalled);
    }

    /**
     * @test
     */
    public function runFilterTest()
    {
        
        $eventsForThirdPartyModulesMock = Bootstrap::getObjectManager()->get(EventsForThirdPartyModulesMock::class);
        $listenerMock = Bootstrap::getObjectManager()->get(ListenerMock::class);

        $result = $eventsForThirdPartyModulesMock->runFilter("runFilter", null);
        static::assertTrue($result);
    }
}
