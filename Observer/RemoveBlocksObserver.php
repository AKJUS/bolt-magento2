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
 *
 * @copyright  Copyright (c) 2017-2024 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\Boltpay\Observer;

use Bolt\Boltpay\Helper\Config as ConfigHelper;
use Bolt\Boltpay\Helper\FeatureSwitch\Decider;
use Bolt\Boltpay\Helper\Log as LogHelper;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class RemoveBlocksObserver implements ObserverInterface
{
    /**
     * Additional GET url param
     * Which acts as a flag during the observer call to manipulate the layout structure.
     */
    private const URL_SHOW_PARAM = 'show';

    /**
     * Native mode for show GET param
     * Using for rendering m2 native customer login/register blocks instead Bolt SSO
     */
    private const URL_SHOW_PARAM_NATIVE = 'native';

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var LogHelper
     */
    protected $logHelper;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Decider
     */
    private $featureSwitches;

    /**
     * @param ConfigHelper $configHelper
     * @param LogHelper $logHelper
     * @param RequestInterface $request
     * @param Decider|null $featureSwitches
     */
    public function __construct(
        ConfigHelper $configHelper,
        LogHelper $logHelper,
        RequestInterface $request,
        ?Decider $featureSwitches = null
    ) {
        $this->configHelper = $configHelper;
        $this->logHelper = $logHelper;
        $this->request = $request;
        $this->featureSwitches = $featureSwitches ?? ObjectManager::getInstance()->get(Decider::class);
    }

    /**
     * @param Observer $observer
     *
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        $fullActionName = $observer->getData('full_action_name');
        $layout = $observer->getLayout();
        $BoltSSOPages = [ConfigHelper::LOGIN_PAGE_ACTION, ConfigHelper::CREATE_ACCOUNT_PAGE_ACTION];
        $showParam = $this->request->getParam(self::URL_SHOW_PARAM);
        if (in_array($fullActionName, $BoltSSOPages)) {
            if ($this->configHelper->isBoltSSOEnabled() && $showParam !== self::URL_SHOW_PARAM_NATIVE) {
                // Remove native block on login page
                $layout->unsetElement('customer_form_login');
                $layout->unsetElement('customer.new');
                // Remove native block on register page
                $layout->unsetElement('customer_form_register');
            } else {
                // Remove Bolt SSO elements
                $layout->unsetElement('bolt_sso_login');
                $layout->unsetElement('bolt_sso_register');
            }
        }

        if ((strpos($fullActionName, 'customer_account') === 0)
            && $this->featureSwitches->isPreventSSOCustomersFromEditingAccountInformation()
            && $this->configHelper->isBoltSSOEnabled()
            && $this->featureSwitches->isBoltSSOEnabled()) {
            $layout->unsetElement('customer-account-navigation-address-link');
            $layout->unsetElement('customer-account-navigation-account-edit-link');
        }
    }
}
