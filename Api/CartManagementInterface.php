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

namespace Bolt\Boltpay\Api;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception as WebapiException;

interface CartManagementInterface
{
    /**
     * Get masked id for specified quote ID
     *
     * @api
     *
     * @param string $cartId
     *
     * @return \Bolt\Boltpay\Api\Data\GetMaskedQuoteIDDataInterface
     *
     * @throws WebapiException
     */
    public function getMaskedId($cartId = '');

    /**
     * Set specific cart active
     *
     * @api
     *
     * @param mixed $cartId
     * @param mixed $isActive
     *
     * @return void
     *
     * @throws WebapiException
     */
    public function update($cartId = null, $isActive = null);

    /**
     * Get Cart Id from Masked Quote Id
     *
     * @api
     *
     * @param string $maskedQuoteId
     *
     * @return int
     *
     * @throws WebapiException
     */
    public function getCartIdByMaskedId($maskedQuoteId);
}
