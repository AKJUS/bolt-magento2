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

interface ExternalCustomerEntityRepositoryInterface
{
    /**
     * @param string $externalID
     *
     * @return \Bolt\Boltpay\Api\Data\ExternalCustomerEntityInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByExternalID($externalID);

    /**
     * @param string $externalID
     * @param int    $customerID
     *
     * @return \Bolt\Boltpay\Api\Data\ExternalCustomerEntityInterface
     */
    public function upsert($externalID, $customerID);
}
