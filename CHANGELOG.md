# Changelog
## [v1.0.4] 2018-06-19
## [v1.0.5] 2018-08-21
## [v1.0.6] 2018-08-23
## [v1.0.7] 2018-09-07
## [v1.0.8] 2018-09-16
## [v1.0.9] 2018-09-19
## [v1.0.10] 2018-09-23
## [v1.0.11] 2018-10-01
## [v1.0.12] 2018-10-10
## [v1.1.0] 2018-10-11
## [v1.1.1] 2018-10-23
## [v1.1.2] 2018-10-30
## [v1.1.3] 2018-11-27
## [v1.1.4] 2018-12-04
## [v1.1.5](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.5) 2018-12-11
 - Use circleCI instead of TravisCI
 - Prevent order ceation API call with an empty cart
 - Complete order stays in payment review state on a long hook delay fix
 - Invalid capture amount failed hook fix
## [v1.1.6](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.6) 2018-12-13
 - Force approve/reject failed hook fix
## [v1.1.7](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.7) 2018-12-21
 - Amasty Gift Card support
 - No status after unhold fix
## [v1.1.8](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.8) 2019-01-09
 - Check if order payment method is 'boltpay'
 - Add currency_code field to cart currency data
 - Dispatch sales_quote_save_after event for active (parent) quotes only
 - Fixed consistency for Amasty Gift Card module
## [v1.1.9](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.9) 2019-01-24
 - Allow empty emails in shipping_and_tax API
 - Add feature to optionally not inject JS on non-checkout pages
 - Sent store order notifications to email collected from Bolt checkout
 - Create order from parent quote
 - Do not cache empty shipping options array
## [v1.1.10](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.10) 2019-02-11
 - Add support for item properties
 - Tax mismatch adjustment
 - Unirgy_Giftcert plugin support
 - Remove active quote restriction on order creation (backend order fix)
 - Reserve Order ID for the child quote, defer setting parent quote order ID until just before quote to order submission
## [v1.1.11](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.11) 2019-03-07
 - Backoffice hook no pending fix
 - Checkout initialization fix
 - Restrict plugin availability in regards to client IP address (white list)
 - Shipping and tax cart validation update - support for multiple items with the same SKU
 - Email field mandatory for back-office orders
 - Prevent setting empty hint prefill field
 - Cart data currency error fix
 - Back office order creation fix
 - Create invoice for zero amount order
 - Exclude empty address fields from save when creating the order
 - Store Credit on Shopping Cart support
 - Update populating the checkout address from hints prefill
## [v1.1.12](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.12) 2019-04-01
 - Allow Admin to update order manually
 - Back-office create order check for shipping method
 - Multi-store support
 - One step checkout support / disable bolt on payment only checkout pages
 - Add config for merchant to specify EmailEnter callback
 - Update ajax order creation error message
## [v1.1.12.1](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.12.1) 2019-04-10
 - Fix to support multi-stores with no default api key
## [v1.1.13](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.13) 2019-04-26
 - Various bug fixes
## [v1.1.14](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.14) 2019-05-28
 - Various bug fixes
## [v1.1.15](https://github.com/BoltApp/bolt-magento2/releases/tag/1.1.15) 2019-06-12
 - Fixes for multi-store backend support.
 - Option to toggle emulated session in api calls
## [v2.0.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.0.0) 2019-07-02
 - Introducing pre-authorized order creation
## [v2.0.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.0.1) 2019-09-06
 - Added generic ERP support
 - Removed Autocapture from settings
## [v2.0.2](https://github.com/BoltApp/bolt-magento2/releases/tag/2.0.2) 2019-09-12
 - Support for Paypal
## [v2.0.3](https://github.com/BoltApp/bolt-magento2/releases/tag/2.0.3) 2019-10-28
 - Testing and logging fixes
 - Beta merchant metrics feature
 - Various bug fixes
## [v2.1.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.1.0) 2019-11-21
 - Paypal payment support
 - [Beta] Feature switches
   - graphQL client for Bolt server communication
 - BSS store credit support
 - Improved checkout metricing
 - Various bug fixes
## [v2.2.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.2.0) 2020-02-05
 - [Beta] Simple Product Page Checkout
 - Staged Rollout
 - Some M2 2.3.4 compat. fixes
 - Multicurrency improvements
 - Various bug fixes
## [v2.3.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.3.0) 2020-02-20
 - Custom checkboxes
 - Re-order feature for logged-in customers
 - Product page checkout improvements
 - Various bug fixes
## [v2.4.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.4.0) 2020-03-11
 - Bug fixes
 - Added JS event for when hints are set
## [v2.4.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.4.1) 2020-03-18
 - Fix Bolt checkout not opening on IE
## [v2.5.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.5.0) 2020-03-27
 - Add support for boltPrimaryActionColor
 - Moved some CSS to M2 config page
 - Custom options support for simple products in product page checkout
 - Webhook log cleaner cron
 - Improved api result caching
 - Improved debug webhook data collection
 - Bug fixes
## [v2.6.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.6.0) 2020-05-04
 - Debug webhook now fully available
 - In-store pickup feature
 - Pay-by-link added
 - Unit tests and code cleanup
 - Admin page reoganization
 - Development quality of life fixes
 - Support for shipping discounts
 - Add Bolt account button for order management
 - Added Amasty store credit support
## [v2.7.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.7.0) 2020-05-12
 - Add catalog price rule support for backoffice
 - Unit tests
 - Bug fixes
## [v2.8.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.8.0) 2020-05-28
 - Splitting shipping and tax endpoints
 - Add always-present Bolt checkout button
 - Added custom url validation
 - Bug fixes
 - Unit tests
## [v2.8.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.8.1) 2020-06-11
 - Fix PPC javascript error in Magento 2 version 2.3.5
 - Fix unknown RevertGiftCardAccountBalance error
## [v2.9.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.9.0) 2020-06-17
 - Fix display of APM/Paypal transactions within Magento 2 dashboard
 - Always-present checkout button improvements
 - Update to method to save Bolt cart in to be more robust
 - Added support for tracking non-Bolt order shipments.
 - Code maintainability refactoring
 - Bug fixes
## [v2.10.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.10.0) 2020-07-20
 - Fixes for latency regressions introduced in 2.9.0
 - Refactoring to optimize number of calls made on page loading
 - Customization branch restructuring
 - Bug fixes
 ## [v2.11.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.11.0) 2020-07-29
 - Improve support for bolt order management (beta)
 - Bug fixes
 ## [v2.12.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.12.0) 2020-08-12
 - Add support for Magento Commerce 2.4
 - Improve support for bolt order management (beta)
 - Add support for plugin Amasty giftcard 2.0.0
 - Support for gift wrapping info
 - Bug fixes
 ## [v2.13.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.13.0) 2020-08-19
 - Improved back-end components related to checkout experience.
 - Support for the Mageplaza plugin's shipping restriction
 ## [v2.14.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.14.0) 2020-09-15
 - Added: Shoppers can now add multiple discounts and remove discounts in Bolt Checkout (Magento discounts only).
 - Improvement: The `display_id` now displays just the `order_id` value in the merchant dashboard and user emails.
 ## [v2.15.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.15.0) 2020-10-05
 - Improvement: Product Page Checkout now supports cart item grouping (itemGroup).
 - Improvement: Bolt now clears the cached shipping and tax information when the shipping method is changed.
 - Fixed: Resolved compatibility issues with MageWorld Reward Points Pro
 ## [v2.16.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.16.0) 2020-10-19
 - Fixed: Resolved issue where shoppers were unable to apply Aheadworks Store Credit to their cart.
 - Fixed: Resolved issues with Amasty Gift Cards being applied to orders placed in the back-office and storefront.  
 - Added: The M2 Plugin now supports product addons (removing and adding suggested items to checkout).
 ## [v2.17.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.17.0) 2020-11-03
 - Fixed: Resolved issue where discounts applied to an order placed from the M2 Admin Console did not apply in Bolt Checkout Modal.
 - Improvement: Refunds for Paypal transactions now support an `in-progress` status for situations where the merchant does not yet have sufficient funds.
 - Improvement: Made general improvements related to Mirasvit rewards points usage such as tax calculations and shipping discounts.
 - Added: Merchants can now selectively configure Product Page Checkout to display only for specific products that have the `bolt_ppc` attribute.

## [v2.18.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.18.0) 2021-01-06
- Added: Merchants can now recognize Apple Pay orders from their Payment Information as `Bolt-Applepay` in the Magento Admin console.
- Improvement: We added an optional feature switch that updates orders with failed payment hooks to a `canceled` status instead of deleting them. This can be useful for merchants that use ERP systems. For activation, reach out to your customer success manager.   
- Improvement: Now merchants can see the cart type and last four digits when reviewing orders from all processors.
- Improvement: Discounts got a small refresh in the way their information is displayed.
- Improvement: We did some refactoring for our payment-only checkout flow.
- Fixed: There was a very unlikely (but still possible) chance that changes to Mirsavit credit applied to the cart did not update, so we made sure it will update every time.
## [v2.18.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.18.1) 2021-01-21
- Improvement: The order grid in the Magento admin console now prioritizes displaying credit card details over payment processor information.
- Fixed: Resolved issue where the order grid in the Magento admin console would freeze when no order records matched the user's defined filtering criteria.
## [v2.19.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.19.0) 2021-02-10
- The M2 plugin now supports the default **Edit Order** functionality in Magento Admin. This enables merchants to edit orders from the Magento Admin Console.
## [v2.20.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.20.0) 2021-03-08
- Product addons are now supported for M2.
- The Universal API is now supported for M2.
- Custom fields (dropdowns, checkboxes) have been refactored for better performance in the future.
- Resolved issue with tax calculations where fixed discounts on the whole cart caused the final calculation to throw the error `cart tax mismatched`.
- Resolved issue where the mini cart occasionally displayed items after a shopper has checked out and is on the order success page.
## [v2.21.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.21.0) 2021-04-08
- This plugin now supports Zonos custom shipping.
- This plugin now supports Mageside's Custom Shipping Price module.
- We’ve optimized the way Bolt checkout handles store credit and rewards points.
- We’ve improved how shopping sessions with cart persistence are handled when this Magento feature is enabled. 
- Resolved issue where shoppers were unable to apply free shipping coupons during checkout.
- Resolved issue where shoppers were unable to purchase digital products where selecting a product option was required (for example, an ebook where selecting “Special Edition” or “Standard” is required).
## [v2.21.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.21.1) 2021-05-04
- Include account.bolt.com in M2 Content Security Policy allow-list
- Fix a bug with order associations for Bolt SSO
## [v2.21.2](https://github.com/BoltApp/bolt-magento2/releases/tag/2.21.2) 2021-05-10
- Fix bug with backoffice orders
- Fix a regression with handling custom fields
## [v2.22.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.22.0) 2021-06-25
- Native Magento BOPIS now supported
- Adds support for Magecomp Extrafee
- Adds product fetching endpoint for future integrations
- Various minor bugfixes
## [v2.23.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.23.0) 2021-09-02
- Updates to simplify SSO Commerce enablement
- Adds support for Magento version 2.4.3
- Adds support for Route Shipping
## [v2.24.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.24.0) 2021-10-20
- Updates to order tracking logic to handle product identification for configurable products
- Updates to M2's product info endpoint to return catalog rule pricing
- Related Bolt `cart.create` endpoint bug fix preventing carts with a `qty` of `1`
## [v2.24.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.24.1) 2021-11-02
- Fixed issue where a coupon granting free shipping would block the checkout flow.
## [v2.25.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.25.0) 2022-02-03
- Starting from this release, the Magento plugin supports two completely different types of integration: legacy and API driven.
- (LEGACY) Shoppers will no longer switch between Magento Store accounts when [Bolt SSO](https://help.bolt.com/developers/guides/checkout-guides/managed-checkout/adobe-commerce-setup-guide/adobe-sso) is enabled on a store.
- (LEGACY) Generally, the way Bolt collects discount information from Magento has been refactored and improved for efficiency.
- Resolved an issue where shoppers were unable to place an order when applying a fixed-amount discount towards their cart total due to how the discount impacted shipping calculations. 
- Resolved an issue where discounts applied to a cart displayed a generic nondescript `DISCOUNT` tag when missing an associated description. This caused confusion when multiple discounts were applied. Now, discounts applied to a cart display their discount name as the tag. (e.g., `BOGO2022`)
- Resolved an issue where refund (credit) grand totals were mismatched when compared to order grand totals.
## [v2.25.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.25.1) 2022-02-16
- (LEGACY) Resolved an issue where automated discounts with empty discount descriptions were showing as the name (set in discount name field) that merchants had set for the discount rule. Now, discount descriptions will show as the word "Discount", regardless of what the rule name was set as. 
- (API) Added a new scope to Bolt's M2 Plugin API to enable use of Magento's `Magento\Sales\Model\Order` endpoint to set status during webhook handling.  
- (API) Implemented integration to use Magento's GET invoice endpoint to support all invoice endpoints([`Magento_Sales::sales_invoice`](https://github.com/magento/magento2/commit/4b0eeb6a6d933c92416cd6eca48d720d48508d61)).
## [v2.25.2](https://github.com/BoltApp/bolt-magento2/releases/tag/2.25.2) 2022-05-25
- Users no longer need to add a `Bolt SSO JS block` to their Magento 2 theme when installing SSO Commerce. CSS and JavaScript are now injected programmatically.
- Fixed issue that caused Bolt button not to load in the mini-cart and the checkout modal to timeout.
- Users can now check out with a fixed amount shipping discount.
- Fixed issue in order view that prevented users from filtering by Bolt Payments.
- Merchants will now see fully refunded orders that have shipped with `Processing` status instead of `Closed`.
## [v2.26.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.26.0) 2022-07-11
- Bolt now ingests Magento product catalog to ensure product availability within Bolt checkout. Inventory syncs between the two platforms for the following events:
    - Updating product inventory in Magento
    - Setting products to "Out of Stock"
    - Completed/shipped orders
    - Refunded orders
    - Add product
    - Delete a product
- Administrators can now place backoffice guest orders via the Magento Admin Dashboard.
- Plugin is now fully compatible with [PHP 8.1](https://www.php.net/releases/8.1/en.php).
- Resolved an issue where incorrect amounts were displayed at checkout when their currency was chosen.
- Fixed issue that caused a dependency to fail to load when Bolt minicart was disabled.
- Resolved issue where customers logged in via Single-Sign-On received incorrect quote for shipping costs at checkout.
- Fixed issue with Free Shipping discount not applying to orders.
- Resolved issue where customers were intermittently not redirected to order confirmation page at checkout.
- Resolved issue where customers could not add discounts created with [Tiered Coupons](https://marketplace.magento.com/mexbs-module-tieredcoupon.html) plugin to orders.
- Fixed issue where customers' loyalty points created via the [Reward Points Subscription by Aheadworks](https://marketplace.magento.com/aheadworks-module-reward-points-subscription.html) plugin were not added to the users' accounts.
- Resolved issue with Gift Cards via [Gift Card by Aheadworks](https://marketplace.magento.com/aheadworks-module-giftcard.html) plugin not applying to orders.
- Fixed issue where users could not filter via `Bolt-Visa` payment method in order grid view.
## [v2.26.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.26.1) 2022-08-22
- Plugin now compatible with Magento version 2.4.5 & PHP 8.1.
- [Amasty Extra Fee](https://amasty.com/magento-extra-fee.html) plugin now supported.
- Fixes intermittent issue with using multiple currencies at checkout.
- Discounts for bundle products now supported on Magento versions < 2.4.4.
## [v2.26.2](https://github.com/BoltApp/bolt-magento2/releases/tag/2.26.2) 2022-11-03
- [Route Shipping insurance](https://help.bolt.com/merchants/references/integrations/route/) is now supported for M2.
- Fixed issue with [Amasty Rewards Points](https://amasty.com/reward-points-for-magento-2.html) that caused points to appear at checkout after being removed.
- Fixed issue that allowed discount codes to be applied after checkout reload in some rare instances.
## [v2.26.3](https://github.com/BoltApp/bolt-magento2/releases/tag/2.26.3) 2022-11-21
- Amazon Pay transactions are now displayed in the dashboard.
- Fixes issue preventing international shoppers from checking out without a state/province.
## [v2.26.4](https://github.com/BoltApp/bolt-magento2/releases/tag/2.26.4) 2022-12-15
## The New
- Fixes intermittent issue where some logged-in users could not check out if Route was enabled.
- Merchants can now filter orders by specific Bolt payment method e.g., `bolt-visa`.
- Fixes intermittent issue where data may not send to third-party integrations.
## [v2.26.5](https://github.com/BoltApp/bolt-magento2/releases/tag/2.26.5) 2023-02-06
## Bug Fixes
- Fixes issue where shoppers could not add items to cart using Safari after logging in using the Bolt Shopper Assistant.
- Fixes intermittent issue where checkout with Apple Pay was interrupted when used in combination with certain payment providers.
## [v2.27.0](https://github.com/BoltApp/bolt-magento2/releases/tag/2.27.0) 2023-03-13
- Introduced Fetch Cart, a new alternative way for Bolt to create the cart on the Bolt side rather than building it on the plugin side by utilizing the Magento Native API. This feature is disabled by default.
- Fixed a catalog ingestion feature affecting a specific subset of products.
## [v2.27.1](https://github.com/BoltApp/bolt-magento2/releases/tag/2.27.1) 2023-04-06
- Plugin is now compatible with Magento version 2.4.6.
## [v2.27.2](https://github.com/BoltApp/bolt-magento2/releases/tag/2.27.2) 2023-07-25
- New: 
  - Merchants can now create their own style variants of the Bolt Button for their Product Page checkout button.
  - Support added for the Mageworx Donations suite. 
  - Support added for the Amasty Affiliate Plugin.
- Additional:
  - Fixes a bug in the M2 Admin Portal that impacted the visibility of the sales order void button after order void in the M2. 
  - Fixes a bug in the M2 Admin Portal that prevented merchants from using different Bolt Divisions at the M2 Store level. Previously, this was only possible at the website level.
## [v2.27.3](https://github.com/BoltApp/bolt-magento2/releases/tag/2.27.3) 2023-10-12
- New:
  - Support added for Extend Warranty.
- Additional:
  - Fixes a bug when customer coupon code usage limit was not restored after failed payment.
  - Fixes a bug when Bolt mini cart button would sometimes not get rendered in Fetch Cart flow under some conditions such as slow internet connection (race condition issue).
## [v2.27.4](https://github.com/BoltApp/bolt-magento2/releases/tag/2.27.4) 2023-11-02
- New:
  - Support added for Multicurrency stores.
- Additional:
  - Fixes a bug when inventory may decreased twice under some conditions.
    Changelog

## [v2.27.5](https://github.com/BoltApp/bolt-magento2/releases/tag/2.27.5) 2024-10-16
- New:
    - Added support for Magento versions 2.4.6-p5 and 2.4.7. Specifically, whitelisted Bolt inline script and style for CSP, ensuring compatibility with the latest Magento security patch.
- Additional:
    - Fixed an issue where Magento back-office orders would fail due to incomplete form validation. Now, Bolt checkout popup will not open unless all required fields are filled in.
    - Fixed an issue with saving credit card functionality in M2 API. The bug impacted the correct saving of customer payment details.
    - Fixed an issue where Bolt Fetch Cart flow continued even when Bolt was disabled in the Magento admin.
    - Fixed an issue where Bolt Product Page Checkout would fail when a customer’s information was missing, ensuring smoother operations for guest shoppers.
    - Fixed compatibility issues with legacy PHP systems causing Bolt plugin errors after PHP and Bolt module updates.
    - Fixed a bug where customer payment details were not shown in the frontend for certain orders.
    - Stopped unnecessary execution of Bolt cron jobs when the M2 API approach is enabled.
    - Fixed an issue where Amasty Promotion items were not updated correctly in the cart.
    - Fixed an issue where Amasty Gift Card discounts were not applied to orders in Bolt legacy mode.
    - Fixed an issue where logged-in customers’ first and last names were not autofilled in the Bolt modal, improving the checkout experience.
    - Fixed an issue where session quotes were not properly deactivated, causing multiple orders for the same cart.
    - Fixed a bug in the order grid where payment method types like ‘Bolt-Visa’ were not shown correctly.
    - Fixed an issue where multiple store credit reversals occurred due to failed payment authorizations.
    - Resolved a race condition causing the Bolt Product Page button to occasionally fail to render.

## [v2.27.7](https://github.com/BoltApp/bolt-magento2/releases/tag/2.27.6) 2025-03-05
- New:
    - Added support for Magento versions 2.4.6-p9 and 2.4.7-p4, ensuring compatibility with the latest Magento security patch. Specifically, the ACL list for the Bolt API has been updated.
- Additional:
    - Fixed an issue where disabling address pre-filling for logged-in customers was not working as expected.
    - Removed Bolt inline script usage on the frontend, which could cause bugs with certain environment configurations.
    - Fixed Bolt merchant dashboard information when BO orders were placed using customers' saved cards.
    - Fixed redirects after SSO login. Now they lead to the previously forbidden actions, such as adding items to the wishlist.
    - Fixed the void action not working from the admin panel in API mode.
    - Fixed the discount label issue where, in some cases, it displayed categories instead of coupon codes.
    - Fixed missing out-of-stock error messages for certain custom inventory implementations.
    - Fixed capture webhooks for specific cases in legacy mode when an order had already been partially invoiced offline.
