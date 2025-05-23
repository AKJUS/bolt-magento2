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

namespace Bolt\Boltpay\Block;

class Info extends \Magento\Payment\Block\Info
{
    protected $_template = 'Bolt_Boltpay::info/default.phtml';

    /**
     * Render as PDF
     * @return string
     */
    public function toPdf()
    {
        $this->setTemplate('Bolt_Boltpay::info/pdf/default.phtml');
        return $this->toHtml();
    }

    /**
     * @param null|\Magento\Framework\DataObject|array $transport
     * @return \Magento\Framework\DataObject|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $info = $this->getInfo();
        $data = [];

        if ($ccType = $info->getCcType()) {
            $data[(string)__('Credit Card Type')] = strtoupper($ccType);
        }

        if ($ccLast4 = $info->getCcLast4()) {
            $data[(string)__('Credit Card Number')] = sprintf('xxxx-%s', $ccLast4);
        }

        if ($this->getArea() == \Magento\Framework\App\Area::AREA_ADMINHTML) {
            if ($credovaPublicId = $info->getAdditionalInformation('credova_public_id')) {
                $data[(string)__('Credova Public Id')]  = $credovaPublicId;
            }

            if ($credovaApplicationId = $info->getAdditionalInformation('credova_application_id')) {
                $data[(string)__('Credova Application Id')]  = $credovaApplicationId;
            }

            if ($cvvResponse = $info->getAdditionalInformation('cvv_response')) {
                $data[(string)__('CVV Response')]  = $cvvResponse;
            }

            if ($avsResponse = $info->getAdditionalInformation('avs_response')) {
                $data[(string)__('AVS Response')]  = $avsResponse;
            }
        }

        if ($data) {
            $transport->setData(array_merge($transport->getData(), $data));
        }

        return $transport;
    }

    public function displayPaymentMethodTitle()
    {
        $info = $this->getInfo();

        if ($info->getCcTransId()) {
            // api flow, title rendered on server side
            $title = $info->getAdditionalData();
            if (!$title) {
                return $this->getMethod()->getConfigData('title', $info->getOrder()->getStoreId());
            }
            return $title;
        }
        $token = $info->getAdditionalData();
        $boltProcessor = $info->getAdditionalInformation('processor');
        //this check must be done first as applepay is not a processor. If done with the rest of the alternative
        //processors after the vantiv check then the display would be wrong.
        if (!empty($token) && $token == "applepay") {
            $paymentTitle = 'Bolt-' . ucfirst($token);
        } elseif (empty($boltProcessor) || $boltProcessor == \Bolt\Boltpay\Helper\Order::TP_VANTIV) {
            $paymentTitle = $this->getMethod()->getConfigData('title', $info->getOrder()->getStoreId());
        } else {
            $paymentTitle = array_key_exists($boltProcessor, \Bolt\Boltpay\Helper\Order::TP_METHOD_DISPLAY)
                ? 'Bolt-' . \Bolt\Boltpay\Helper\Order::TP_METHOD_DISPLAY[ $boltProcessor ]
                : 'Bolt-' . ucfirst($boltProcessor);
        }

        return $paymentTitle;
    }
}
