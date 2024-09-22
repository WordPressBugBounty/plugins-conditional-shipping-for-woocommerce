=== Conditional Shipping for WooCommerce ===
Contributors: wooelements
Tags: woocommerce shipping, conditional shipping
Requires at least: 4.6
Tested up to: 6.6
Requires PHP: 7.0
Stable tag: 3.3.0
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Restrict WooCommerce shipping methods based on conditions. Works with your existing shipping methods and zones.

== Description ==
Conditional Shipping for WooCommerce allows you to restrict shipping methods based on conditions. For example, you can disable free shipping for orders weighing over 30 kg or create a special shipping method for large products.

The plugin works with your existing shipping methods and zones. You can restrict flat rate, free shipping, pickup or any other shipping method created with shipping zones.

= Example =

You have two flat rate shipping methods, Freight and Economy. Orders weighing under 30 kg are shipped with Economy shipping. Orders over 30 kg have to be shipped with Freight.

With Conditional Shipping you can set maximum weight (30 kg) for Economy and minimum weight for Freight (30 kg). The customer sees only the right shipping on the checkout.

= Features =

* Restrict WooCommerce shipping methods based on conditions
* Works with existing shipping methods
* [Support for dynamic shipping rates](https://wptrio.com/guide/control-live-shipping-rates-e-g-usps-or-fedex/) such as USPS and DHL
* [Debug mode](https://wptrio.com/guide/troubleshoot-with-debug-mode/) for easy troubleshooting

= Available Conditions =

* Products
* Total Weight
* Total Length
* Total Height
* Total Width
* Total Volume
* Order Subtotal

= Pro Features =

* All free features
* Set shipping costs conditionally. For example, increase shipping cost 20 % for large items.
* Override shipping method titles conditionally
* Display shipping notices based on conditions
* Set custom "no shipping methods available" message based on conditions
* More conditions
    * Product measurement conditions (for example highest allowed height for a product in the cart is 10 cm)
    * Shipping class conditions
    * Category conditions
    * Coupon conditions
    * Number of items in the cart condition
    * Customer logged in / out condition
    * Customer user role condition

[Upgrade to Pro](https://wptrio.com/products/conditional-shipping/)

= Support Policy =

If you need any help with the plugin, please create a new post on the [WordPress plugin support forum](https://wordpress.org/support/plugin/conditional-shipping-for-woocommerce/). It will be checked regularly but please note that response cannot be guaranteed to all issues. Priority email support is available for the Pro version.

= Other Useful Plugins =

Make sure to check out other useful plugins from the author.

* [Conditional Payments for WooCommerce](https://wordpress.org/plugins/conditional-payments-for-woocommerce)
* [Stock Sync for WooCommerce](https://wordpress.org/plugins/stock-sync-for-woocommerce/)
* [WooCommerce Product Sync Pro](https://wptrio.com/products/woocommerce-product-sync-pro/)

== Installation ==
Conditional Shipping is installed just like any other WordPress plugin.

1. Download the plugin zip file
2. Go to Plugins in the WordPress admin panel
3. Click Add new and Upload plugin
4. Choose the downloaded zip file and upload it
5. Activate the plugin

Once the plugin is activated, you can create rulesets at **WooCommerce > Settings > Shipping > Conditions**. Each ruleset comprises of conditions and actions which are run if conditions pass.

There is a debug mode which is really helpful to see how rulesets are working. You can activate it at **WooCommerce > Settings > Shipping > Conditions > Debug mode**. Once the mode is activated, you should be able to see **Conditional Shipping Debug** in the checkout which shows which conditions passed and actions were taken.

If you have dynamic / live shipping rates such as USPS, you will need to use **Match by name** option for selecting dynamic shipping methods. Please see [here](https://wptrio.com/guide/control-live-shipping-rates-e-g-usps-or-fedex/) for more information.

That should be all. Any questions / issues / bug reports feel free to create a post on [WordPress.org support forum](https://wordpress.org/support/plugin/conditional-shipping-for-woocommerce/).

== Changelog ==

= 3.3.0 =

* Added **Duplicate ruleset** feature
* Added **equals** operator for numerical conditions
* Improved user interface performance

= 3.2.0 =

* Support for WooCommerce block-based checkout

= 3.1.3 =

* Declared compatibility with WordPress 6.4.x

= 3.1.2 =

* Added multicurrency support for *_Price Based on Country for WooCommerce_*

= 3.1.1 =

* Changed plugin text domain to **conditional-shipping-for-woocommerce** to allow WordPress.org translations
* Declared WooCommerce 8.x and WordPress 6.3 compatibilities 

= 3.1.0 =

* Added **Match by name** option for selecting shipping methods based on their name
* Declared compatibility with High-Performance Order Storage (HPOS)
* Added option for hiding Pro features

= 3.0.0 =

* Rulesets can now be ordered by drag-and-drop. Rulesets are evaluated from top to bottom
* Improved user interface

= 2.4.1 =

* Fixed bug which crashed the checkout if WooCommerce Multilingual & Multicurrency by WPML was activated but multicurrency functionality was not enabled

= 2.4.0 =

* Improved support for WPML
* Added *_All shipping methods_* selector for controlling all shipping methods without selecting them individually
* Added support for the following multi-currency plugins: *_Aelia Currency Switcher for WooCommerce_*, *_FOX - Currency Switcher Professional for WooCommerce_* and *_WooCommerce Multilingual & Multicurrency (by WPML)_*
* Minor bug fixes

= 2.3.2 =

* Minor security fix

= 2.3.1 =

* Fixed *_Undefined index: price_mode_* error message

= 2.3.0 =

* Added AND / OR selection for conditions
* Improved debug mode. It now shows active shipping zone and shipping methods before and after filtering
* Improved compatibility with other plugins
* Updated WooCommerce compatibility info

= 2.2.3 =

* Removed "No products in the order" debug message

= 2.2.1 & 2.2.2 =

* Added missing debug and css files

= 2.2.0 =

* Added debug mode (*WooCommerce > Settings > Shipping > Conditions > Debug mode*)
* Added "Disable all" setting for disabling all rulesets at once (*WooCommerce > Settings > Shipping > Conditions > Disable all*)

= 2.1.2 =

* Minor fixes and improvements
* Updated WooCommerce compatibility info up to 5.2.x

= 2.1.1 =

* Fixed bug with Products condition which prevented it to work with a lot of product variations

= 2.1.0 =

* Added Health Check to catch common issues with rulesets
* Added AJAX toggle for ruleset state (enabled / disabled)
* Excluded taxes from the subtotal condition if the store displays subtotal excluding tax (_WooCommerce > Settings > Tax > Display prices during cart and checkout_). *Please note!* Ensure rulesets are working correctly after updating if you have subtotal conditions.

= 2.0.4 =

* Fixed missing frontend JS file

= 2.0.3 =

* Improved PHP 7.3 compatibility
* WooCommerce 4.1.x compatibility check
* Other minor fixes and improvements

= 2.0.2 =

* Improved product search

= 2.0.1 =

* Added "Subtotal includes coupons" option
* Added functionality for enabling / disabling rulesets

= 2.0.0 =

* Moved conditions from individual shipping methods to separate settings page (WooCommerce > Settings > Shipping > Conditions). This change will allow more advanced functionality in upcoming versions. Important! Check that conditions are working correctly after updating.

= 1.1.2 =

* Updated WordPress and WooCommerce compatibility info

= 1.1.1 =

* Improved compatibility with 3rd party shipping method plugins

= 1.1.0 =

* Improved admin user interface

= 1.0.10 =

* Fixed compatibility issue with WooCommerce 3.4.x
* Fixed compatibility issue with WooCommerce Services

= 1.0.9 =

* Improved compatibility with some 3rd party shipping modules where settings were not saving.

= 1.0.8 =

* Improved compatibility with WooCommerce

= 1.0.7 =

* Improved compatibility with multi-site environments.

= 1.0.6 =

* Added compatibility for WooCommerce Distance Rate Shipping plugin.

= 1.0.5 =

* Improved compatibility with 3rd party plugins.

= 1.0.4 =

* Fixed bug which prevented saving the conditions in some cases.

= 1.0.3 =

* Added product variations to the product filters
* Fixed compability with the WooCommerce USPS plugin

= 1.0.2 =
* Added minimum total volume filter

= 1.0.1 =
* Added product filters (require, exclude, exclusive)

= 1.0.0 =
* Initial version
