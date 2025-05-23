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

namespace Bolt\Boltpay\Block;

/**
 * Block is used for js bolt checkout initialization with
 * 'M2_ENABLE_API_DRIVEN_CART_INTEGRATION' feature switcher enabled parameter
 */
class MinifiedJsCartApiDriven extends Js
{
    /**
     * @inheritDoc
     */
    protected function _toHtml()
    {
        try {
            if ($this->featureSwitches->isEnabledFetchCartViaApi() &&
                !$this->shouldDisableBoltCheckout() &&
                !$this->isBoltDisabledOnCurrentPage() &&
                !(!$this->isOnPageFromWhiteList() && !$this->isMinicartEnabled())
            ) {
                return $this->minifyJs(parent::_toHtml());
            }
        } catch (\Exception $e) {
            return '';
        }
        return '';
    }
}
