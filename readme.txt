=== Ship Discounts ===
Contributors: horizoncumulus
Tags: ecommerce, quotes, shipping, carriers, woocommerce plugin
Requires at least: 6.4
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.0.9
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Offer your customers shipping services with real-time quotes.

== Description ==

The Ship Discounts plugin is a [WooCommerce](https://woo.com/woocommerce/) add-on that allows you to offer your customers shipping services with real-time quotes. The plugin is used to manage a shipping method that lets visitors, when ordering products, see the prices of different carriers in real time and choose one. The administrator has control over several options (for example, selecting which carriers to display, box formats, etc.).

This module must be used with the WooCommerce plugin. Therefore, WooCommerce must be installed first.

== Third-party Services ==

The plugin is connected to the [Ship Discounts](https://www.shipdiscounts.ca/) platform (see [privacy policy](https://www.shipdiscounts.ca/privacy-policy/)). To use its services, the plugin must send and receive information via the Ship Discounts API. You will need an account.

When your store is activated, its domain name and an authentication token are sent to the API.

When obtaining carrier rates, the number of packages, their weight and dimensions, whether a signature is required, whether there is a non-conveyable item, the shipment value, the customer’s postal code, and the store’s postal code are sent. A non-modifiable statement that the package contains no dangerous goods is also sent.

When an order is created, the name, full address, and telephone number of the customer and the store are sent. The items in the order, the number of packages, their weight and dimensions, the shipment value, whether a signature is required, whether there is a non-conveyable item, the displayed cost of delivery, the actual cost of delivery, the chosen carrier code, and the chosen service code are also sent.

When deleting an order, obtaining order details, or obtaining the label, the Ship Discounts order number is sent.

The Ship Discounts platform can also communicate with your WordPress site using your authentication token. Please note that it can view and modify the details of all WooCommerce orders.

The order information it can see includes the order number, status, costs, customer name, customer phone number, customer email, customer address, details of items purchased, details of the chosen carrier and delivery service, notes, creation date, update date, and closure date.

The order information it can modify includes the order number linked to the Ship Discounts platform, the carrier and its service, the displayed cost of delivery and the actual cost, the number of packages and their weights and dimensions, the tracking number and link, the label, the cost of boxes, the value of the shipment, whether a signature is required, and whether there is a non-conveyable item. Please note that these changes do not affect the actual cost of the order; the customer’s invoice will not change.

== Installation ==

This plugin requires you to have the WooCommerce plugin already installed and activated.
* Download the Ship Discounts plugin.
* Install the plugin on your WordPress site.
* Enter your API key that you will have obtained via your Ship Discounts account.

== Changelog ==

= 1.0.9 - 2024-10-04 =
* More accurate phone error messages.
* Changed the default name of Purolator Express service for "Express".
* Added additional verification for the tracking code and URL.
* Fixed an error with array_key_exists() in the cart and the checkout.
* Fixed an error with SKU in the meta box.
* Added a "requestOrigin" parameter to all API calls to Ship Discounts.

= 1.0.8.1 - 2024-09-04 =
* Updated readme.txt.

= 1.0.8 - 2024-08-27 =
* Tested up to 6.6.
* WooCommerce tested up to 9.2.3.
* The API now returns the order shipping method.
* You can now add notes that will be printed on the label (maximum 30 characters).
* Fixed fatal error that occurred when the object of a WooCommerce email was not a WC_Order.

= 1.0.7 - 2024-07-16 =
* You can now use WordPress to update this plugin (you will need to reactivate it after the first update).
* The folder name is now "ship-discounts" (was "ship-discounts-lar").
* Tested up to 6.5.
* Added a WooCommerce dependency.
* Removed Javascript libraries already in WordPress core.
* Moved scripts into .js files.
* The text domain is now "ship-discounts" (was "ship-discounts-lar").
* Increased security.
* Fixed package weight calculation: product weight is now added to package weight.
* Fixed orders that could be sent to Ship Discounts with invalid dimensions and/or weight.
* Updated option and function names to be less generic (from "lar" to "sd_lar").
* Deprecated filters "lar_free_shipping_value", "lar_method_cost_value", "lar_carrier_display_cost_value", "lar_carrier_display_cost_value" and "lar_carrier_display_cost_value". Instead, please use "sd_lar_free_shipping_value", "sd_lar_method_cost_value", "sd_lar_carrier_display_cost_value", "sd_lar_boxes_cost_value" and "sd_lar_delivery_date_value".

= 1.0.6.2 - 2024-06-05 =
* The "Fulfill" API request no longer modifies the order total.

= 1.0.6.1 - 2024-06-03 =
* SQL query replaced by wc_get_orders() to retrieve orders via the API.
* Added compatibility with WooCommerce "High-performance order storage" mode.

= 1.0.6 - 2024-05-31 =
* More robust check at checkout to validate if a carrier has been chosen.
* The list of invalid products is now always displayed, because even if predefined packages are used, the UGS code could still be problematic.
* Only products that can be shipped now appear in the list of invalid products.
* General improvements to follow WordPress best practices.
* The text domain is now "ship-discounts-lar" (was "lar").

= 1.0.5.1 - 2024-05-14 =
* Fixed order submission not working when using another shipping method.

= 1.0.5 - 2024-05-13 =
* Added REST API to enable Ship Discounts to interact with the store (you will need to reconnect your store).
* Added WooCommerce debug logs for calls to the Ship Discounts API.
* Added Woocommerce Blocks incompatibility notice.
* Added `lar_delivery_date_value` filter to modify the displayed delivery date.
* Added CSS classes to cart and checkout pages.
* Added option to hide the signature checkbox.
* The delivery date now uses the format defined by WordPress.
* All orders can now use Ship Discounts' features.
* Increased security.
* Fixed carrier rates when sending to Ship Discounts; costs were sometimes incorrect depending on the currency used.
* More accurate error messages when getting carrier rates.

= 1.0.4 - 2024-04-26 =
* The API key and the store must now be registered with Ship Discounts via the "Ship Discounts API Activation" page in order to use the plugin.
* Updated `lar_free_shipping_value` filter to add cart/order items as a parameter.
* Added `lar_boxes_cost_value` filter to modify boxes cost.
* Added `lar_carrier_display_cost_value` filter to modify the carriers' display cost.
* Added the `lar_method_cost_value` filter to modify the base cost of the shipping method.
* Fixed weight calculation that rounded up to 1 lb if the value was smaller.
* Fixed calculation of carrier rates when sending an order to Ship Discounts; now based solely on package weights and dimensions entered by the administrator in the table.
* Fixed code that generated warnings.
* Fixed translation.

= 1.0.3.1 - 2024-04-19 =
* Added `lar_free_shipping_value` filter, allowing to change the minimum order amount for free shipping.

= 1.0.3 - 2024-04-19 =
* Fixed meta box not displaying depending on WooCommerce order data storage settings.
* Fixed shipment value where conversion to number did not always work, preventing carrier rates from being received.
* Fixed the cost of the shipping method where conversion to number did not always work, thus calculating an incorrect shipping cost when sending to Ship Discounts.
* Fixed displayed cost when sending to Ship Discounts, which showed the base cost if the displayed cost was 0.
* Carrier and box rates are now considered shipping costs.
* Added an option for free delivery above a certain threshold.
* The minimum value for weights and dimensions is now 0.1. Smaller and/or lighter products can still be ordered if the store uses predefined packages and/or boxes.

= 1.0.2 - 2024-04-17 =
* Increased timeout for API calls to 30 seconds.
* Variations are now used to check product validity.
* Variations can now have their own "non-conveyable" value.
* Shop phone number extension is removed when orders are sent to Ship Discounts.
* Improved validity check of products in cart.
* Fixed total cart price, which was sometimes incorrect if there was an error with the carriers.

= 1.0.1 - 2024-04-08 =
* Fixed the activation and deletion.
* Fixed unit conversions.
* Fixed some displays.

= 1.0.0 - 2024-04-02 =
* Plugin ready to be tested in production.

= 0.0.1 - 2024-01-31 =
* Plugin created.