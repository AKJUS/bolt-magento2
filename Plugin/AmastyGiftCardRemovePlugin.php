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

namespace Bolt\Boltpay\Plugin;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Bolt\Boltpay\Model\EventsForThirdPartyModules;

/**
 * Class AmastyGiftCardRemovePlugin
 * Ensure Gift Card is removed from the parent quote.
 * The default method removes the first found row which might corespond to some previusly created immutable quote.
 */
class AmastyGiftCardRemovePlugin
{
    
    /** @var EventsForThirdPartyModules  */
    private $eventsForThirdPartyModules;

    /**
     * AmastyGiftCardRemovePlugin constructor.
     * @param EventsForThirdPartyModules   $eventsForThirdPartyModules
     */
    public function __construct(
        EventsForThirdPartyModules $eventsForThirdPartyModules
    ) {
        $this->eventsForThirdPartyModules = $eventsForThirdPartyModules;
    }

    /**
     * Remove Amasty Gift Card from the (parent) quote
     *
     * @param Action $subject the observed object
     * @param ResponseInterface|ResultInterface|void $result the result of the observed method call
     * @return ResponseInterface|ResultInterface|void
     */
    public function afterExecute(Action $subject, $result)
    {
        // Get the code id from the original request
        $codeId = $subject->getRequest()->getParam('code_id');

        // Access protected checkoutSession property with a Closure proxy
        $getQuoteProxy = function () use ($subject) {
            return $subject->checkoutSession->create()->getQuote();
        };
        $quote = $getQuoteProxy->call($subject);

        // Remove Amasty Gift Cart from the parent (session) quote
        $this->eventsForThirdPartyModules->dispatchEvent("removeAmastyGiftCard", $codeId, $quote);

        // Access protected updateTotalsInQuote method with a Closure proxy
        $updateTotalsInQuoteProxy = function () use ($subject, $quote) {
            $subject->updateTotalsInQuote($quote);
        };
        $updateTotalsInQuoteProxy->call($subject);

        return $result;
    }
}
