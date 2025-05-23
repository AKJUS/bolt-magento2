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

use Magento\Checkout\Model\Session as CheckoutSession;
use Bolt\Boltpay\Helper\Cart as CartHelper;
use Bolt\Boltpay\Helper\Config as ConfigHelper;

class ClearQuote
{
    /**
     * @var CartHelper
     */
    private $cartHelper;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var \Magento\Quote\Model\Quote|null
     */
    private $quoteToRestore;

    /**
     * @param CartHelper $cartHelper
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        CartHelper $cartHelper,
        ConfigHelper $configHelper
    ) {
        $this->cartHelper = $cartHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * @param CheckoutSession $subject
     * @return null Return null because method clearQuote have no arguments
     * @throws \Exception
     */
    public function beforeClearQuote(CheckoutSession $subject)
    {
        if (!$this->configHelper->getProductPageCheckoutFlag()) {
            return null;
        }
        // We don't want to clear quote
        // in Product page checkout (PPC) flow
        $this->quoteToRestore = null;
        $currentQuoteId = $subject->getQuote()->getId();
        $orderQuoteId = $subject->getLastSuccessQuoteId();
        if (!$currentQuoteId || !$orderQuoteId || $currentQuoteId == $orderQuoteId) {
            // In PPC checkout quote should be different then quote tied to order just created
            return null;
        }

        // Although check above is enough, double check that we are in Bolt PPC process
        $quote = $this->cartHelper->getQuoteById($orderQuoteId);
        if (!$quote || $quote->getBoltParentQuoteId() != $orderQuoteId) {
            // BoltParentQuoteId should be set (sign of Bolt)
            // and should be the same as quoteID (sign of PPC)
            return null;
        }

        $this->quoteToRestore = $subject->getQuote();

        return null;
    }

    /**
     * @param CheckoutSession $subject
     * @return CheckoutSession
     * @throws \Exception
     */
    public function afterClearQuote(CheckoutSession $subject)
    {
        if ($this->configHelper->getProductPageCheckoutFlag() && $this->quoteToRestore) {
            $subject->replaceQuote($this->quoteToRestore);
            return $subject;
        }

        if (version_compare($this->configHelper->getStoreVersion(), '2.2.0', '<')) {
            // Workaround for known magento issue - https://github.com/magento/magento2/issues/12504
            $subject->setLoadInactive(false);
            $subject->replaceQuote($subject->getQuote()->save());
        }

        return $subject;
    }
}
