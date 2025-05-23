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
 * @copyright  Copyright (c) 2017-2023 Bolt Financial, Inc (https://www.bolt.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Bolt\Boltpay\Helper\FeatureSwitch;

/**
 * All feature switches are defined here. By default all new switches should be
 * false and not rolled out. These values are only fallbacks for when something
 * isn't defined on the Bolt side. Anything values coming from Bolt take precedence.
 *
 * Class Definitions
 * @package Bolt\Boltpay\Helper\FeatureSwitch
 */
class Definitions
{
    const NAME_KEY = 'name';
    const VAL_KEY = 'value';
    const DEFAULT_VAL_KEY = 'default_value';
    const ROLLOUT_KEY = 'rollout_percentage';

    /**
     * This switch is a sample of how to set up a feature switch.
     * Every feature switch added here should have a corresponding helper
     * in Decider.php
     */
    const M2_SAMPLE_SWITCH_NAME = 'M2_SAMPLE_SWITCH';

    /**
     * Whether bolt is enabled. This will be used for slow rollout / controlling bolt
     * from the backend.
     */
    const M2_BOLT_ENABLED = 'M2_BOLT_ENABLED';

    /**
     * Enable including mismatch amount into tax when adjusting price mismatch
     */
    const M2_INCLUDE_MISMATCH_AMOUNT_INTO_TAX_WHEN_ADJUSTING_PRICE_MISMATCH = 'M2_INCLUDE_MISMATCH_AMOUNT_INTO_TAX_WHEN_ADJUSTING_PRICE_MISMATCH';

    /**
     * Enable logging of missing quote failed hooks
     *
     */
    const M2_LOG_MISSING_QUOTE_FAILED_HOOKS = 'M2_LOG_MISSING_QUOTE_FAILED_HOOKS';

    /**
     * Enable creating credit memo for webhook
     *
     */
    const M2_CREATING_CREDITMEMO_FROM_WEB_HOOK_ENABLED = 'M2_CREATING_CREDITMEMO_FROM_WEB_HOOK_ENABLED';

    /**
     * Enable feature/admin reorder for logged in customer
     */
    const M2_BOLT_ADMIN_REORDER_FOR_LOGGED_IN_CUSTOMER = 'M2_BOLT_ADMIN_REORDER_FOR_LOGGED_IN_CUSTOMER';

    /**
     * Enable tracking shipment
     */
    const M2_TRACK_SHIPMENT = 'M2_TRACK_SHIPMENT';

    /**
     * Enable order updates
     */
    const M2_ORDER_UPDATE = 'M2_ORDER_UPDATE';

    /**
     * Enable non-Bolt order tracking
     */
    const M2_TRACK_NON_BOLT = 'M2_TRACK_NON_BOLT';

    /**
     * Enable Order Management (account button)
     */
    const M2_ORDER_MANAGEMENT = 'M2_ORDER_MANAGEMENT';

    /**
     * Enable Pay-by-link feature
     */
    const M2_PAY_BY_LINK = 'M2_PAY_BY_LINK';

    /**
     * Enable ignore hook for invoice creation feature
     */
    const M2_IGNORE_HOOK_FOR_INVOICE_CREATION = 'M2_IGNORE_HOOK_FOR_INVOICE_CREATION';

    /**
     * Enable ignore hook for credit memo creation feature
     */
    const M2_IGNORE_HOOK_FOR_CREDIT_MEMO_CREATION = 'M2_IGNORE_HOOK_FOR_CREDIT_MEMO_CREATION';

    /**
     * Enable merchant metrics
     */
    const M2_MERCHANT_METRICS = 'M2_MERCHANT_METRICS';

    /**
     * Enable Bolt Button V2
     */
    const M2_INSTANT_BOLT_CHECKOUT_BUTTON = 'M2_INSTANT_BOLT_CHECKOUT_BUTTON';

    /**
     * Save hints data for PPC in magento sections
     */
    const M2_SAVE_HINTS_IN_SECTIONS = 'M2_SAVE_HINTS_IN_SECTIONS';

    /**
     * Enable always present checkout button
     */
    const M2_ALWAYS_PRESENT_CHECKOUT = 'M2_ALWAYS_PRESENT_CHECKOUT';

    /**
     * Disable pre-fill address from Bolt for the logged-in customer
     */
    const M2_IF_SHOULD_DISABLE_PREFILL_ADDRESS_FROM_BOLT_FOR_LOGGED_IN_CUSTOMER = 'M2_IF_SHOULD_DISABLE_PREFILL_ADDRESS_FROM_BOLT_FOR_LOGGED_IN_CUSTOMER';

    /**
     * Disable redirect customers to the cart page after they log in
     */
    const M2_IF_SHOULD_DISABLE_REDIRECT_CUSTOMER_TO_CART_PAGE_AFTER_THEY_LOG_IN = 'M2_IF_SHOULD_DISABLE_REDIRECT_CUSTOMER_TO_CART_PAGE_AFTER_THEY_LOG_IN';

    /**
     * Handle virtual cart as physical. Workarond for known issue with taxable virtual products.
     */
    const M2_HANDLE_VIRTUAL_PRODUCTS_AS_PHYSICAL = 'M2_HANDLE_VIRTUAL_PRODUCTS_AS_PHYSICAL';

    /**
     * Handle virtual cart as physical. Workarond for known issue with taxable virtual products.
     */
    const M2_INCLUDE_USER_GROUP_ID_INTO_CART = 'M2_INCLUDE_USER_GROUP_ID_INTO_CART';

    /**
     * Disable Bolt for customer group
     */
    const M2_ALLOW_DISABLING_BOLT_FOR_CUSTOMER_GROUP = 'M2_ALLOW_DISABLING_BOLT_FOR_CUSTOMER_GROUP';

    /**
     * Capture email to Listrak
     */
    const M2_CAPTURE_EMAIL_TO_LISTRAK_ENABLED = 'M2_CAPTURE_EMAIL_TO_LISTRAK_ENABLED';

    /**
     * Enable shipping prefetch option
     */
    const M2_PREFETCH_SHIPPING = 'M2_PREFETCH_SHIPPING';

    /**
     * Cancel failed payment orders instead of deleting them
     */
    const M2_CANCEL_FAILED_PAYMENT_ORDERS_INSTEAD_OF_DELETING = 'M2_CANCEL_FAILED_PAYMENT_ORDERS_INSTEAD_OF_DELETING';

    /**
     * Store encrypted session id in cart metadata
     */
    const M2_ADD_SESSION_ID_TO_CART_METADATA = 'M2_ADD_SESSION_ID_TO_CART_METADATA';

    /**
     * Enable Bolt SSO
     */
    const M2_ENABLE_BOLT_SSO = 'M2_ENABLE_BOLT_SSO';

    /**
     * Support Customizable Options
     */
    const M2_CUSTOMIZABLE_OPTIONS_SUPPORT = 'M2_CUSTOMIZABLE_OPTIONS_SUPPORT';

    /**
     * Enable connect.js on cart page or product page (if PPC enabled) only
     */
    const M2_LOAD_CONNECT_JS_ON_SPECIFIC_PAGE = 'M2_LOAD_CONNECT_JS_ON_SPECIFIC_PAGE';

    /**
     * Remove track.js from home page of the site
     */
    const M2_DISABLE_TRACK_ON_HOME_PAGE = 'M2_DISABLE_TRACK_ON_HOME_PAGE';

    /**
     * Include track.js only on pages where we have connect.js
     */
    const M2_DISABLE_TRACK_ON_NON_BOLT_PAGES = 'M2_DISABLE_TRACK_ON_NON_BOLT_PAGES';

    /**
     * Remove openreplay.js everywhere (catalog, product, cart and checkout pages)
     */
    const M2_DISABLE_OPENREPLAY = 'M2_DISABLE_OPENREPLAY';

    /**
     * Enable always return error if there is any exception when running filter.
     */
    const M2_RETURN_ERR_WHEN_RUN_FILTER = 'M2_RETURN_ERR_WHEN_RUN_FILTER';

    /**
     * Display order comment block in admin
     */
    const M2_SHOW_ORDER_COMMENT_IN_ADMIN = 'M2_SHOW_ORDER_COMMENT_IN_ADMIN';

    /**
     * Prevent bolt cart creation for quotes with error
     */
    const M2_PREVENT_BOLT_CART_FOR_QUOTES_WITH_ERROR = 'M2_PREVENT_BOLT_CART_FOR_QUOTES_WITH_ERROR';

    /**
     * Save customer credit card
     */
    const M2_SAVE_CUSTOMER_CREDIT_CARD = 'M2_SAVE_CUSTOMER_CREDIT_CARD';

    /**
     * Set order payment info data on success page
     */
    const M2_SET_ORDER_PAYMENT_INFO_DATA_ON_SUCCESS_PAGE = 'M2_SET_ORDER_PAYMENT_INFO_DATA_ON_SUCCESS_PAGE';

    /**
     * Set customer name to order for guests
     */
    const M2_SET_CUSTOMER_NAME_TO_ORDER_FOR_GUESTS = 'M2_SET_CUSTOMER_NAME_TO_ORDER_FOR_GUESTS';

    /**
     * Ignore total validation when the credit hook is sent to Magento
     */
    const M2_IGNORE_TOTAL_VALIDATION_WHEN_CREDIT_HOOK_IS_SENT_TO_MAGENTO = 'M2_IGNORE_TOTAL_VALIDATION_WHEN_CREDIT_HOOK_IS_SENT_TO_MAGENTO';

    /**
     * Prevent Bolt IPs from being saved as customer IP on quote
     */
    const M2_PREVENT_SETTING_BOLT_IPS_AS_CUSTOMER_IP_ON_QUOTE = 'M2_PREVENT_SETTING_BOLT_IPS_AS_CUSTOMER_IP_ON_QUOTE';

    /**
     * Disallow the order in processing/complete/closed status won't be updated back to pending_review/new
     */
    const M2_DISALLOW_ORDER_STATUS_OVERRIDE = 'M2_DISALLOW_ORDER_STATUS_OVERRIDE';

    /**
     * Enable product endpoint
     */
    const M2_ENABLE_PRODUCT_ENDPOINT = 'M2_ENABLE_PRODUCT_ENDPOINT';

    /**
     * Enable API driven integration
     */
    const M2_ENABLE_API_DRIVEN_INTEGRAION = 'M2_ENABLE_API_DRIVEN_INTEGRAION';

    /**
     * Recalculate quote total for API driven integration
     */
    const M2_RECALCULATE_TOTAL_FOR_API_DRIVEN_INTEGRAION = 'M2_RECALCULATE_TOTAL_FOR_API_DRIVEN_INTEGRAION';

    /**
     * Force to collect discounts details via plugin methods
     */
    const M2_COLLECT_DISCOUNT_BY_PLUGIN = 'M2_COLLECT_DISCOUNT_BY_PLUGIN';

    /**
     * Prevent customers from editing their account and address data when SSO is enabled
     */
    const M2_PREVENT_SSO_CUSTOMERS_FROM_EDITING_ACCOUNT_INFORMATION = 'M2_PREVENT_SSO_CUSTOMERS_FROM_EDITING_ACCOUNT_INFORMATION';

    /**
     * Allow custom CDN URL for production environment
     */
    const M2_ALLOW_CUSTOM_CDN_URL_FOR_PRODUCTION = 'M2_ALLOW_CUSTOM_CDN_URL_FOR_PRODUCTION';

    /**
     * Use rule name as coupon description if rule description is empty
     */
    const M2_USE_RULE_NAME_IF_DESCRIPTION_EMPTY = 'M2_USE_RULE_NAME_IF_DESCRIPTION_EMPTY';

    /**
     * Automatically initialize Bolt custom SSO buttons
     */
    const M2_INITIALIZE_BOLT_CUSTOM_SSO_BUTTONS = 'M2_INITIALIZE_BOLT_CUSTOM_SSO_BUTTONS';

    /**
     * Enable catalog ingestion
     */
    const M2_CATALOG_INGESTION = 'M2_CATALOG_INGESTION';

    /**
     * Enable store configuration webhook
     */
    const M2_STORE_CONFIGURATION_WEBHOOK = 'M2_STORE_CONFIGURATION_WEBHOOK';

    /**
     * Enable fetching data from magento setup_module table instead of using core methods
     */
    const M2_ENABLE_MODULE_RETRIEVER_FROM_SETUP_MODULE_TABLE = 'M2_ENABLE_MODULE_RETRIEVER_FROM_SETUP_MODULE_TABLE';

    /**
     * Enable shopper assistant
     */
    const M2_ENABLE_SHOPPER_ASSISTANT = 'M2_ENABLE_SHOPPER_ASSISTANT';

    /**
     * Skip cart discount total mismatch
     */
    const M2_SKIP_CART_DISCOUNT_TOTAL_MISMATCH = 'M2_SKIP_CART_DISCOUNT_TOTAL_MISMATCH';

    /**
     * Disable catalog ingestion instant update process
     */
    const M2_CATALOG_INGESTION_DISABLE_INSTANCE_PIPELINE = 'M2_CATALOG_INGESTION_DISABLE_INSTANCE_PIPELINE';

    /**
     * Enable M2 fetch cart via api
     */
    const M2_FETCH_CART_VIA_API = 'M2_FETCH_CART_VIA_API';

    /**
     * Enable M2 pre-fetch cart via api
     */
    const M2_PRE_FETCH_CART_VIA_API = 'M2_PRE_FETCH_CART_VIA_API';

    /**
     * Enable disabling ajax add to cart success event time out
     */
    const M2_AJAX_ADD_TO_CART_SUCCESS_DISABLE_TIME_OUT = 'M2_AJAX_ADD_TO_CART_SUCCESS_DISABLE_TIME_OUT';

    /**
     * Bolt session params flow enabling
     */
    const M2_BOLT_SESSION_PARAMS = 'M2_BOLT_SESSION_PARAMS';

    /**
     * Enable do not postpone order place call
     */
    const M2_DO_NOT_POSTPONE_ORDER_PLACE_CALL = 'M2_DO_NOT_POSTPONE_ORDER_PLACE_CALL';

    /**
     * Disable MSRP price on bolt modal
     */
    const M2_DISABLE_MSRP_PRICE = 'M2_DISABLE_MSRP_PRICE';

    const DEFAULT_SWITCH_VALUES = [
        self::M2_SAMPLE_SWITCH_NAME => [
            self::NAME_KEY        => self::M2_SAMPLE_SWITCH_NAME,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_BOLT_ENABLED => [
            self::NAME_KEY        => self::M2_BOLT_ENABLED,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_INCLUDE_MISMATCH_AMOUNT_INTO_TAX_WHEN_ADJUSTING_PRICE_MISMATCH => [
            self::NAME_KEY        => self::M2_INCLUDE_MISMATCH_AMOUNT_INTO_TAX_WHEN_ADJUSTING_PRICE_MISMATCH,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_SAVE_CUSTOMER_CREDIT_CARD => [
            self::NAME_KEY        => self::M2_SAVE_CUSTOMER_CREDIT_CARD,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_LOG_MISSING_QUOTE_FAILED_HOOKS => [
            self::NAME_KEY        => self::M2_LOG_MISSING_QUOTE_FAILED_HOOKS,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_BOLT_ADMIN_REORDER_FOR_LOGGED_IN_CUSTOMER => [
            self::NAME_KEY        => self::M2_BOLT_ADMIN_REORDER_FOR_LOGGED_IN_CUSTOMER,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_CREATING_CREDITMEMO_FROM_WEB_HOOK_ENABLED => [
            self::NAME_KEY        => self::M2_CREATING_CREDITMEMO_FROM_WEB_HOOK_ENABLED,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_IGNORE_TOTAL_VALIDATION_WHEN_CREDIT_HOOK_IS_SENT_TO_MAGENTO => [
            self::NAME_KEY        => self::M2_IGNORE_TOTAL_VALIDATION_WHEN_CREDIT_HOOK_IS_SENT_TO_MAGENTO,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_TRACK_SHIPMENT => [
            self::NAME_KEY        => self::M2_TRACK_SHIPMENT,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_ORDER_UPDATE => [
            self::NAME_KEY        => self::M2_ORDER_UPDATE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_TRACK_NON_BOLT => [
            self::NAME_KEY        => self::M2_TRACK_NON_BOLT,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_ORDER_MANAGEMENT => [
            self::NAME_KEY        => self::M2_ORDER_MANAGEMENT,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_PAY_BY_LINK => [
            self::NAME_KEY        => self::M2_PAY_BY_LINK,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_IGNORE_HOOK_FOR_CREDIT_MEMO_CREATION => [
            self::NAME_KEY        => self::M2_IGNORE_HOOK_FOR_CREDIT_MEMO_CREATION,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_IGNORE_HOOK_FOR_INVOICE_CREATION => [
            self::NAME_KEY        => self::M2_IGNORE_HOOK_FOR_INVOICE_CREATION,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_MERCHANT_METRICS => [
            self::NAME_KEY        => self::M2_MERCHANT_METRICS,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_INSTANT_BOLT_CHECKOUT_BUTTON => [
            self::NAME_KEY        => self::M2_INSTANT_BOLT_CHECKOUT_BUTTON,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_SAVE_HINTS_IN_SECTIONS => [
            self::NAME_KEY        => self::M2_SAVE_HINTS_IN_SECTIONS,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_ALWAYS_PRESENT_CHECKOUT => [
            self::NAME_KEY        => self::M2_ALWAYS_PRESENT_CHECKOUT,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_IF_SHOULD_DISABLE_PREFILL_ADDRESS_FROM_BOLT_FOR_LOGGED_IN_CUSTOMER => [
            self::NAME_KEY        => self::M2_IF_SHOULD_DISABLE_PREFILL_ADDRESS_FROM_BOLT_FOR_LOGGED_IN_CUSTOMER,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_HANDLE_VIRTUAL_PRODUCTS_AS_PHYSICAL => [
            self::NAME_KEY        => self::M2_HANDLE_VIRTUAL_PRODUCTS_AS_PHYSICAL,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_INCLUDE_USER_GROUP_ID_INTO_CART => [
            self::NAME_KEY        => self::M2_INCLUDE_USER_GROUP_ID_INTO_CART,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_CAPTURE_EMAIL_TO_LISTRAK_ENABLED => [
            self::NAME_KEY        => self::M2_CAPTURE_EMAIL_TO_LISTRAK_ENABLED,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_PREFETCH_SHIPPING => [
            self::NAME_KEY        => self::M2_PREFETCH_SHIPPING,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_IF_SHOULD_DISABLE_REDIRECT_CUSTOMER_TO_CART_PAGE_AFTER_THEY_LOG_IN => [
            self::NAME_KEY        => self::M2_IF_SHOULD_DISABLE_REDIRECT_CUSTOMER_TO_CART_PAGE_AFTER_THEY_LOG_IN,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_CANCEL_FAILED_PAYMENT_ORDERS_INSTEAD_OF_DELETING => [
            self::NAME_KEY        => self::M2_CANCEL_FAILED_PAYMENT_ORDERS_INSTEAD_OF_DELETING,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_ADD_SESSION_ID_TO_CART_METADATA => [
            self::NAME_KEY        => self::M2_ADD_SESSION_ID_TO_CART_METADATA,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_ENABLE_BOLT_SSO => [
            self::NAME_KEY        => self::M2_ENABLE_BOLT_SSO,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_CUSTOMIZABLE_OPTIONS_SUPPORT => [
            self::NAME_KEY        => self::M2_CUSTOMIZABLE_OPTIONS_SUPPORT,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_LOAD_CONNECT_JS_ON_SPECIFIC_PAGE => [
            self::NAME_KEY        => self::M2_LOAD_CONNECT_JS_ON_SPECIFIC_PAGE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_DISABLE_TRACK_ON_HOME_PAGE => [
            self::NAME_KEY        => self::M2_DISABLE_TRACK_ON_HOME_PAGE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_DISABLE_TRACK_ON_NON_BOLT_PAGES => [
            self::NAME_KEY        => self::M2_DISABLE_TRACK_ON_NON_BOLT_PAGES,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_DISABLE_OPENREPLAY => [
            self::NAME_KEY        => self::M2_DISABLE_OPENREPLAY,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_RETURN_ERR_WHEN_RUN_FILTER => [
            self::NAME_KEY        => self::M2_RETURN_ERR_WHEN_RUN_FILTER,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_SHOW_ORDER_COMMENT_IN_ADMIN => [
            self::NAME_KEY        => self::M2_SHOW_ORDER_COMMENT_IN_ADMIN,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_PREVENT_BOLT_CART_FOR_QUOTES_WITH_ERROR => [
            self::NAME_KEY        => self::M2_PREVENT_BOLT_CART_FOR_QUOTES_WITH_ERROR,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_SET_ORDER_PAYMENT_INFO_DATA_ON_SUCCESS_PAGE => [
            self::NAME_KEY        => self::M2_SET_ORDER_PAYMENT_INFO_DATA_ON_SUCCESS_PAGE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_SET_CUSTOMER_NAME_TO_ORDER_FOR_GUESTS => [
            self::NAME_KEY        => self::M2_SET_CUSTOMER_NAME_TO_ORDER_FOR_GUESTS,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_PREVENT_SETTING_BOLT_IPS_AS_CUSTOMER_IP_ON_QUOTE => [
            self::NAME_KEY        => self::M2_PREVENT_SETTING_BOLT_IPS_AS_CUSTOMER_IP_ON_QUOTE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_ENABLE_PRODUCT_ENDPOINT => [
            self::NAME_KEY        => self::M2_ENABLE_PRODUCT_ENDPOINT,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_DISALLOW_ORDER_STATUS_OVERRIDE => [
            self::NAME_KEY        => self::M2_DISALLOW_ORDER_STATUS_OVERRIDE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_ENABLE_API_DRIVEN_INTEGRAION => [
            self::NAME_KEY        => self::M2_ENABLE_API_DRIVEN_INTEGRAION,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_RECALCULATE_TOTAL_FOR_API_DRIVEN_INTEGRAION => [
            self::NAME_KEY        => self::M2_RECALCULATE_TOTAL_FOR_API_DRIVEN_INTEGRAION,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_COLLECT_DISCOUNT_BY_PLUGIN => [
            self::NAME_KEY        => self::M2_COLLECT_DISCOUNT_BY_PLUGIN,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_PREVENT_SSO_CUSTOMERS_FROM_EDITING_ACCOUNT_INFORMATION => [
            self::NAME_KEY        => self::M2_PREVENT_SSO_CUSTOMERS_FROM_EDITING_ACCOUNT_INFORMATION,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100,
        ],
        self::M2_ALLOW_CUSTOM_CDN_URL_FOR_PRODUCTION => [
            self::NAME_KEY        => self::M2_ALLOW_CUSTOM_CDN_URL_FOR_PRODUCTION,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_USE_RULE_NAME_IF_DESCRIPTION_EMPTY => [
            self::NAME_KEY        => self::M2_USE_RULE_NAME_IF_DESCRIPTION_EMPTY,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_INITIALIZE_BOLT_CUSTOM_SSO_BUTTONS => [
            self::NAME_KEY        => self::M2_INITIALIZE_BOLT_CUSTOM_SSO_BUTTONS,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_CATALOG_INGESTION => [
            self::NAME_KEY        => self::M2_CATALOG_INGESTION,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_CATALOG_INGESTION_DISABLE_INSTANCE_PIPELINE => [
            self::NAME_KEY        => self::M2_CATALOG_INGESTION_DISABLE_INSTANCE_PIPELINE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_STORE_CONFIGURATION_WEBHOOK => [
            self::NAME_KEY        => self::M2_STORE_CONFIGURATION_WEBHOOK,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_ENABLE_SHOPPER_ASSISTANT => [
            self::NAME_KEY        => self::M2_ENABLE_SHOPPER_ASSISTANT,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_SKIP_CART_DISCOUNT_TOTAL_MISMATCH => [
            self::NAME_KEY        => self::M2_SKIP_CART_DISCOUNT_TOTAL_MISMATCH,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_ENABLE_MODULE_RETRIEVER_FROM_SETUP_MODULE_TABLE => [
            self::NAME_KEY        => self::M2_ENABLE_MODULE_RETRIEVER_FROM_SETUP_MODULE_TABLE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_FETCH_CART_VIA_API => [
            self::NAME_KEY        => self::M2_FETCH_CART_VIA_API,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_PRE_FETCH_CART_VIA_API => [
            self::NAME_KEY        => self::M2_PRE_FETCH_CART_VIA_API,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => true,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_AJAX_ADD_TO_CART_SUCCESS_DISABLE_TIME_OUT => [
            self::NAME_KEY        => self::M2_AJAX_ADD_TO_CART_SUCCESS_DISABLE_TIME_OUT,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_ALLOW_DISABLING_BOLT_FOR_CUSTOMER_GROUP => [
            self::NAME_KEY        => self::M2_ALLOW_DISABLING_BOLT_FOR_CUSTOMER_GROUP,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_BOLT_SESSION_PARAMS => [
            self::NAME_KEY        => self::M2_BOLT_SESSION_PARAMS,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => true,
            self::ROLLOUT_KEY     => 100
        ],
        self::M2_DO_NOT_POSTPONE_ORDER_PLACE_CALL => [
            self::NAME_KEY        => self::M2_DO_NOT_POSTPONE_ORDER_PLACE_CALL,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
        self::M2_DISABLE_MSRP_PRICE => [
            self::NAME_KEY        => self::M2_DISABLE_MSRP_PRICE,
            self::VAL_KEY         => true,
            self::DEFAULT_VAL_KEY => false,
            self::ROLLOUT_KEY     => 0
        ],
    ];
}
