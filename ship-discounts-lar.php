<?php
/*
Plugin Name: Ship Discounts
Description: Offer your customers shipping services with real-time quotes.
Version: 1.0.9
Requires Plugins: woocommerce
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: ship-discounts
Domain Path: /languages/
WC tested up to: 9.2.3

Ship Discounts is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Ship Discounts is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Ship Discounts. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

if (!defined('ABSPATH'))
    exit;

if (!defined('SD_LAR_VERSION'))
    define('SD_LAR_VERSION', '1.0.9');

if (!defined('SD_LAR_AUTOMATIC_ORDER'))
    define('SD_LAR_AUTOMATIC_ORDER', false);

if (!defined('SD_LAR_ADVANCED_SETTINGS'))
    define('SD_LAR_ADVANCED_SETTINGS', false);

if (!defined('SD_LAR_API_URL_PROD'))
    define('SD_LAR_API_URL_PROD', 'https://api.montrealdropship.com/v1/');

if (!defined('SD_LAR_CLIENT_URL_PROD'))
    define('SD_LAR_CLIENT_URL_PROD', 'https://client.montrealdropship.com/');

if (!defined('SD_LAR_API_URL_DEV'))
    define('SD_LAR_API_URL_DEV', 'https://apidev.montrealdropship.com/v1/');

if (!defined('SD_LAR_CLIENT_URL_DEV'))
    define('SD_LAR_CLIENT_URL_DEV', 'https://clienttest.montrealdropship.com/');

if (!class_exists('Ship_Discounts_LAR_Plugin')) {
    /**
     * Class of the Ship Discounts plugin.
     */
    class Ship_Discounts_LAR_Plugin {
        /**
         * @var bool|Ship_Discounts_LAR_Plugin Current instance of the class.
         */
        private static $instance = false;

        /**
         * API key parameter.
         */
        const API_PARAM = '?apiKey=';

        /**
         * URL to get the carriers' quotes.
         */
        const CARRIER_QUOTE_URL = 'carrier/quote';

        /**
         * URL to get an order's details.
         */
        const ORDER_URL = 'order';

        /**
         * URL to get an order's label.
         */
        const ORDER_LABEL_URL = 'order/documents';

        /**
         * Constructor.
         */
        private function __construct() {
            if (!function_exists('is_plugin_active'))
                include_once ABSPATH . 'wp-admin/includes/plugin.php';

            if (is_plugin_active('woocommerce/woocommerce.php')) {
                // Languages
                load_plugin_textdomain('ship-discounts', false, dirname(plugin_basename(__FILE__)) . '/languages/');
                add_filter('load_textdomain_mofile', array($this, 'sd_lar_load_textdomain'), 10, 2);

                // Plugin activation
                add_action('admin_menu', array($this, 'sd_lar_register_menu_page'), 999);

                // Token verification
                if ((get_option('sd_lar_api_dev', "") && !get_option('sd_lar_api_token_dev', "")) ||
                    (!get_option('sd_lar_api_dev', "") && !get_option('sd_lar_api_token_prod', "")))
                    update_option('sd_lar_account_activated', false);

                // Shipping method
                if (get_option('sd_lar_account_activated', false)) {
                    include_once(__DIR__ . "/includes/class-lar-carriers.php");
                    include_once(__DIR__ . "/includes/class-lar-boxes.php");
                    include_once(__DIR__ . "/includes/class-lar-packages.php");
                    add_action('plugins_loaded', function () {
                        include_once(__DIR__ . "/includes/wc-shipping-ship-discounts.php");
                    });
                    include_once(__DIR__ . "/includes/wc-settings-section.php");
                    include_once(__DIR__ . "/includes/wc-product-settings.php");
                    include_once(__DIR__ . "/includes/wc-cart-checkout.php");
                    include_once(__DIR__ . "/includes/order-meta-box.php");

                    // Email
                    add_filter('woocommerce_email_format_string', array($this, 'sd_lar_email_placeholders'), 20, 2);
                    add_filter('woocommerce_email_settings', array($this, 'sd_lar_add_placeholders_info'));

                    // Client styles and scripts
                    add_action('init', array($this, 'sd_lar_load_client_styles_scripts'), 11);

                    // API
                    include_once "api/api.php";
                }

                // Deprecated
                include_once(__DIR__ . "/deprecated.php");

                // Admin styles and scripts
                add_action('admin_init', array($this, 'sd_lar_load_admin_styles_scripts'));
            }
        }

        /**
         * Returns the current instance. If it does not exist, it is first created.
         * @return Ship_Discounts_LAR_Plugin Current instance.
         */
        public static function getInstance(): Ship_Discounts_LAR_Plugin {
            if (!self::$instance)
                self::$instance = new self;
            return self::$instance;
        }

        /**
         * Redirects to the installation page after activation.
         * @return void
         */
        public static function sd_lar_activation($plugin) {
            if (!function_exists('is_plugin_active'))
                include_once ABSPATH . 'wp-admin/includes/plugin.php';

            if ($plugin === plugin_basename(__FILE__) && is_plugin_active('woocommerce/woocommerce.php')) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Redirect url already escaped.
                exit(wp_safe_redirect(esc_url(admin_url('admin.php?page=lar-api'))));
            }
        }

        /**
         * Makes sure the plugin is updated.
         * @return void
         */
        public static function sd_lar_update() {
            if (is_plugin_active('woocommerce/woocommerce.php')) {
                // TODO: Éventuellement retirer après 1.0.7
                // Update for 1.0.7
                include_once(__DIR__ . '/update.php');
            }
        }

        /**
         * Loads the admin styles and scripts.
         * @return void
         */
        function sd_lar_load_admin_styles_scripts() {
            wp_enqueue_style('sd_lar_admin_style', plugins_url('css/admin.css', __FILE__), array(), SD_LAR_VERSION);

            // Scripts for the settings page
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (is_admin() && $_GET['page'] === "wc-settings" && $_GET['tab'] === "shipping") {
                wp_enqueue_script('lar-admin-script', plugins_url('js/admin.min.js', __FILE__), array('jquery', 'jquery-ui-sortable', 'wp-util'), SD_LAR_VERSION, ['in-footer' => false]);

                wp_localize_script('lar-admin-script', 'ajax_var', array(
                    'url'   => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('lar-ajax-nonce')
                ));
            }

            // Scripts for the API page
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (is_admin() && $_GET['page'] === "lar-api") {
                wp_enqueue_script('lar-admin-api-script', plugins_url('js/admin-api.min.js', __FILE__), array('jquery'), SD_LAR_VERSION, ['in-footer' => false]);
            }

            // Scripts for the meta box
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            if (is_admin() && $_GET['page'] === "wc-orders" && isset($_GET['action']) && $_GET['action'] === "edit") {
                wp_enqueue_script('lar-admin-order-script', plugins_url('js/admin-order.min.js', __FILE__), array('jquery', 'wp-util'), SD_LAR_VERSION, ['in-footer' => false]);

                wp_localize_script('lar-admin-order-script', 'ajax_var', array(
                    'url'   => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('lar-ajax-nonce')
                ));

                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $id = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : 0;
                $order = wc_get_order($id);

                $sku = true;
                if ($order) {
                    foreach ($order->get_items() as $item) {
                        $product = $item->get_product();
                        if ($product) {
                            $sku = $product->get_sku() ?: "";
                            if (!$sku) break;
                        }
                    }
                }

                wp_localize_script('lar-admin-order-script', 'wc_var', array(
                    'wc_order_id' => $id,
                    'sku'         => $sku,

                    'sku_error'             => esc_html__('The products must have a SKU.', 'ship-discounts'),
                    'carriers_error'        => esc_html__('Sorry, we could not fetch the carriers\' quotes. Please try again.', 'ship-discounts'),
                    'postal_error'          => esc_html__('To view our carriers\' quotes, please insert a valid postal code.', 'ship-discounts'),
                    'dimensions_error'      => esc_html__('The products must have valid dimensions and weight.', 'ship-discounts'),
                    /* translators: %s: Ship Discounts website */
                    'delete_error'          => wp_kses_post(sprintf(__('The new order was not created because the current order could not be cancelled.<br><a target=\'_blank\' href=\'%s\'>You can try to delete it manually.</a>', 'ship-discounts'), esc_url(get_option('sd_lar_client_url', SD_LAR_CLIENT_URL_PROD)))),
                    'phone_shipfrom_error'  => esc_html__('The order was not created because the shop phone number is invalid. Please note that extensions will not be used.', 'ship-discounts'),
                    'phone_deliverTo_error' => esc_html__('The order was not created because the shipping phone number is invalid. Please note that extensions will not be used.', 'ship-discounts'),
                    'phone_soldTo_error'    => esc_html__('The order was not created because the billing phone number is invalid. Please note that extensions will not be used.', 'ship-discounts'),
                    'missing_error'         => esc_html__('The order was not created because one or more mandatory fields are missing. You can start by checking the customer\'s shipping address.', 'ship-discounts'),
                    'no_packages_error'     => esc_html__('The order was not created because there a no packages.', 'ship-discounts'),
                    'packages_error'        => esc_html__('The order was not created because the packages dimensions are invalid.', 'ship-discounts'),
                    'order_error'           => esc_html__('The order could not be created. Please try again.', 'ship-discounts'),
                    'cancel_error'          => esc_html__('These orders could not be cancelled. Please try again.', 'ship-discounts')
                ));
            }
        }

        /**
         * Loads the client styles and scripts.
         * @return void
         */
        function sd_lar_load_client_styles_scripts() {
            wp_enqueue_style('sd_lar_client_style', plugins_url('css/style.css', __FILE__), array(), SD_LAR_VERSION);

            // TODO : Load seulement sur ces pages ?
            // Scripts for cart and checkout
            if (!is_admin()) {
                wp_enqueue_script('lar-cart-checkout-script', plugins_url('js/cart-checkout.min.js', __FILE__), array('jquery', 'wp-util'), SD_LAR_VERSION, ['in-footer' => true]);

                wp_localize_script('lar-cart-checkout-script', 'ajax_var', array(
                    'url'   => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('lar-ajax-nonce')
                ));

                wp_localize_script('lar-cart-checkout-script', 'wc_var', array(
                    'cart_count'    => isset(WC()->cart) ? intval(WC()->cart->get_cart_contents_count()) : 0,
                    'trigger_check' => isset(WC()->session) && WC()->session->get('chosen_lar_carrier') ? 'true' : 'false'
                ));
            }
        }

        /**
         * Loads the right language from languages directory.
         * @param string $mofile Language file path.
         * @param string $domain Text domain.
         * @return string Language file path.
         */
        function sd_lar_load_textdomain($mofile, $domain) {
            if ('ship-discounts' === $domain)
                return plugin_dir_path(__FILE__) . 'languages/ship-discounts-' . get_locale() . '.mo';

            return $mofile;
        }

        /**
         * Registers the Ship Discounts API submenu.
         * @return void
         */
        function sd_lar_register_menu_page() {
            add_submenu_page('woocommerce', esc_html__('Ship Discounts API', 'ship-discounts'), esc_html__('Ship Discounts API', 'ship-discounts'), 'manage_woocommerce', 'lar-api', array($this, 'sd_lar_admin_api_page'), 999);
        }

        /**
         * Displays the Ship Discounts API page.
         * @return void
         */
        function sd_lar_admin_api_page() {
            include("page-settings.php");
        }

        /**
         * Adds custom placeholders to WooCommerce email subject and body.
         * @param string $string
         * @param object $email
         * @return string
         */
        function sd_lar_email_placeholders($string, $email) {
            $order = $email->object;
            $tracking = '';
            $carrier_code = '';
            $service_code = '';

            if ($order && is_a($order, 'WC_Order')) {
                if ($order->get_meta('_sd_lar_tracking_nb'))
                    $tracking = $order->get_meta('_sd_lar_tracking_nb');
                if ($order->get_meta('_sd_lar_tracking_url')) {
                    if (!$tracking) $tracking = esc_html__('Track my order', 'ship-discounts');
                    $tracking = '<a href="' . $order->get_meta('_sd_lar_tracking_url') . '" target="_blank">' . $tracking . '</a>';
                }

                $carrier_code = $order->get_meta('_sd_lar_carrier_code') ?: '';
                $service_code = $order->get_meta('_sd_lar_service_code') ?: '';
            }

            $new_placeholders = array(
                '{tracking}'     => $tracking,
                '{carrier_code}' => $carrier_code,
                '{service_code}' => $service_code,
            );

            return str_replace(array_keys($new_placeholders), array_values($new_placeholders), $string);
        }

        /**
         * Specifies the available email placeholders.
         * @param $settings array Email settings.
         * @return array Email settings with the available email placeholders.
         */
        function sd_lar_add_placeholders_info($settings) {
            $key = array_search('email_notification_settings-description', array_column($settings, 'id')) + 1;

            $desc = esc_html__("Since the plugin Ship Discounts is activated, you have access to the following placeholders: ", 'ship-discounts');
            $desc .= '<br><b>{tracking}</b> => ' . esc_html__("Displays the tracking code with the URL", 'ship-discounts');
            $desc .= '<br><b>{carrier_code}</b> => ' . esc_html__("Displays the carrier code", 'ship-discounts');
            $desc .= '<br><b>{service_code}</b> => ' . esc_html__("Displays the service code", 'ship-discounts');

            $custom_setting[] = array(
                'title' => esc_html__("Ship Discounts placeholders", 'ship-discounts'),
                'desc'  => $desc,
                'id'    => 'sd_lar_placeholders',
                'type'  => 'title',
            );
            return array_merge(array_slice($settings, 0, $key), $custom_setting, array_slice($settings, $key, count($settings) - 1));
        }

        /**
         * Converts to pounds.
         * @param $unit string Unit to convert from.
         * @param $value float|int|string Value to convert.
         * @return float|int|string Value in pounds.
         */
        public static function convertToLbs($unit, $value) {
            if (!is_numeric($value)) return 0;

            switch ($unit) {
                case 'kg':
                    return $value * 2.20462262185;
                case 'g':
                    return $value * 0.0022046226;
                case 'oz':
                    return $value * 0.0625;
                default:
                    return $value;
            }
        }

        /**
         * Converts to inches.
         * @param $unit string Unit to convert from.
         * @param $value float|int|string Value to convert.
         * @return float|int|string Value in inches.
         */
        public static function convertToIn($unit, $value) {
            if (!is_numeric($value)) return 0;

            switch ($unit) {
                case 'm':
                    return $value * 39.37007874;
                case 'cm':
                    return $value * 0.3937007874;
                case 'mm':
                    return $value * 0.03937007874;
                case 'yd':
                    return $value * 36;
                default:
                    return $value;
            }
        }

        /**
         * Checks if an array key exists in a multidimensional array.
         * @param $array array Array.
         * @param $key mixed Key.
         * @return bool If the key exists.
         */
        public static function multiArrayKeyExists($array, $key) {
            if (array_key_exists($key, $array))
                return true;

            foreach ($array as $a) {
                if (is_array($a)) {
                    if (self::multiArrayKeyExists($a, $key))
                        return true;
                }
            }

            return false;
        }

        /**
         * Checks if a product is missing its weight, dimensions and/or SKU.
         * @param $product mixed Product.
         * @return array|null Array of missing data or null.
         */
        public static function sd_lar_check_product_missing($product) {
            $weight = $product->get_weight() ? Ship_Discounts_LAR_Plugin::convertToLbs(get_option('woocommerce_weight_unit'), $product->get_weight()) : false;
            if ($weight) $weight = $weight >= 0.1 && $weight <= 150;

            $height = $product->get_height() ? Ship_Discounts_LAR_Plugin::convertToIn(get_option('woocommerce_dimension_unit'), $product->get_height()) : false;
            if ($height) $height = $height >= 0.1 && $height <= 144;

            $width = $product->get_width() ? Ship_Discounts_LAR_Plugin::convertToIn(get_option('woocommerce_dimension_unit'), $product->get_width()) : false;
            if ($width) $width = $width >= 0.1 && $width <= 144;

            $length = $product->get_length() ? Ship_Discounts_LAR_Plugin::convertToIn(get_option('woocommerce_dimension_unit'), $product->get_length()) : false;
            if ($length) $length = $length >= 0.1 && $length <= 144;

            $sku = $product->get_sku() ?: false;

            if (!$weight || !$height || !$width || !$length || !$sku) {
                $missing = "";

                if (!$weight) $missing .= esc_html__("weight", 'ship-discounts') . ', ';
                if (!$height) $missing .= esc_html__("height", 'ship-discounts') . ', ';
                if (!$width) $missing .= esc_html__("width", 'ship-discounts') . ', ';
                if (!$length) $missing .= esc_html__("length", 'ship-discounts') . ', ';
                if (!$sku) $missing .= esc_html__("SKU", 'ship-discounts') . ', ';
                $missing = substr($missing, 0, strlen($missing) - 2);

                return array(
                    "id"      => $product->get_id(),
                    "parent"  => $product->get_parent_id(),
                    "name"    => $product->get_name(),
                    "missing" => $missing,
                );
            }

            return null;
        }

        /**
         * Create an array of WC_Order_Item_Product based on items in cart.
         * @param array $cart_items Items in cart.
         * @return array Array of WC_Order_Item_Product.
         */
        public static function sd_lar_cart_items_to_item_products($cart_items) {
            $products = [];

            if ($cart_items) {
                foreach ($cart_items as $cart_item) {
                    $item = new WC_Order_Item_Product();
                    $item->set_props(array(
                        'quantity'     => $cart_item['quantity'],
                        'variation'    => $cart_item['variation'],
                        'subtotal'     => $cart_item['line_subtotal'],
                        'total'        => $cart_item['line_total'],
                        'subtotal_tax' => $cart_item['line_subtotal_tax'],
                        'total_tax'    => $cart_item['line_tax'],
                        'taxes'        => $cart_item['line_tax_data'],
                    ));

                    $product = $cart_item['data'];
                    if ($product) {
                        $item->set_props(array(
                            'name'         => $product->get_name(),
                            'tax_class'    => $product->get_tax_class(),
                            'product_id'   => $product->is_type('variation') ? $product->get_parent_id() : $product->get_id(),
                            'variation_id' => $product->is_type('variation') ? $product->get_id() : 0,
                        ));
                    }
                    $item->set_backorder_meta();

                    $products[] = $item;
                }
            }

            return $products;
        }
    }

    add_action('activated_plugin', array('Ship_Discounts_LAR_Plugin', 'sd_lar_activation'));
    add_action('init', array('Ship_Discounts_LAR_Plugin', 'sd_lar_update'));

    add_action('before_woocommerce_init', function () {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil'))
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, false);

        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class))
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    });

    $Ship_Discounts_LAR_Plugin = Ship_Discounts_LAR_Plugin::getInstance();
}