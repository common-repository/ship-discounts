<?php
/**
 * This file updates older versions of the plugin (<1.0.7) to use the new prefix.
 */

if (!defined('ABSPATH'))
    exit;

if (get_option('lar_api_dev', 'does-not-exist') !== 'does-not-exist' ||
    get_option('lar_api_token_dev', 'does-not-exist') !== 'does-not-exist' ||
    get_option('lar_api_token_prod', 'does-not-exist') !== 'does-not-exist' ||
    get_option('lar_account_activated', 'does-not-exist') !== 'does-not-exist') {
    /** WP_OPTIONS **/
    $options = array(
        array(
            'old_name' => 'lar_account_activated',
            'new_name' => 'sd_lar_account_activated',
        ),
        array(
            'old_name' => 'lar_api_dev',
            'new_name' => 'sd_lar_api_dev',
        ),
        array(
            'old_name' => 'lar_api_token_dev',
            'new_name' => 'sd_lar_api_token_dev',
        ),
        array(
            'old_name' => 'lar_api_token_prod',
            'new_name' => 'sd_lar_api_token_prod',
        ),
        array(
            'old_name' => 'lar_api_key_dev',
            'new_name' => 'sd_lar_api_key_dev',
        ),
        array(
            'old_name' => 'lar_api_key_prod',
            'new_name' => 'sd_lar_api_key_prod',
        ),
        array(
            'old_name' => 'lar_api_url',
            'new_name' => 'sd_lar_api_url',
        ),
        array(
            'old_name' => 'lar_client_url',
            'new_name' => 'sd_lar_client_url',
        ),
        array(
            'old_name' => 'lar_settings_boxes',
            'new_name' => 'sd_lar_settings_boxes',
        ),
        array(
            'old_name' => 'lar_settings_carriers_list',
            'new_name' => 'sd_lar_settings_carriers_list',
        ),
        array(
            'old_name' => 'lar_settings_class_list_allow',
            'new_name' => 'sd_lar_settings_class_list_allow',
        ),
        array(
            'old_name' => 'lar_settings_class_list_deny',
            'new_name' => 'sd_lar_settings_class_list_deny',
        ),
        array(
            'old_name' => 'lar_settings_class_list_type',
            'new_name' => 'sd_lar_settings_class_list_type',
        ),
        array(
            'old_name' => 'lar_settings_free_shipping_value',
            'new_name' => 'sd_lar_settings_free_shipping_value',
        ),
        array(
            'old_name' => 'lar_settings_package_height',
            'new_name' => 'sd_lar_settings_package_height',
        ),
        array(
            'old_name' => 'lar_settings_package_length',
            'new_name' => 'sd_lar_settings_package_length',
        ),
        array(
            'old_name' => 'lar_settings_package_weight',
            'new_name' => 'sd_lar_settings_package_weight',
        ),
        array(
            'old_name' => 'lar_settings_package_width',
            'new_name' => 'sd_lar_settings_package_width',
        ),
        array(
            'old_name' => 'lar_settings_phone_number',
            'new_name' => 'sd_lar_settings_phone_number',
        ),
        array(
            'old_name' => 'lar_settings_predefined_package',
            'new_name' => 'sd_lar_settings_predefined_package',
        ),
        array(
            'old_name' => 'lar_settings_shipment_value',
            'new_name' => 'sd_lar_settings_shipment_value',
        ),
        array(
            'old_name' => 'lar_settings_signature',
            'new_name' => 'sd_lar_settings_signature',
        ),
        array(
            'old_name' => 'lar_settings_use_boxes',
            'new_name' => 'sd_lar_settings_use_boxes',
        ),
    );

    foreach ($options as $o) {
        update_option($o['new_name'], get_option($o['old_name'], null));
        delete_option($o['old_name']);
    }

    /** SHIPPING METHODS **/
    global $wpdb;

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $methods = $wpdb->get_results("SELECT instance_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id = 'lar_method';");

    if ($methods) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query("UPDATE {$wpdb->prefix}woocommerce_shipping_zone_methods SET method_id = CASE
   									WHEN method_id = 'lar_method' THEN 'sd_lar_method'
   									ELSE method_id
								END");

        foreach ($methods as $method) {
            update_option("woocommerce_sd_lar_method_{$method->instance_id}_settings", get_option("woocommerce_lar_method_{$method->instance_id}_settings", null));
            delete_option("woocommerce_lar_method_{$method->instance_id}_settings");
        }
    }

    /** ORDERS META **/
    $ordermeta = array(
        array(
            'old_name' => '_lar_boxes_cost',
            'new_name' => '_sd_lar_boxes_cost',
        ),
        array(
            'old_name' => '_lar_carrier_code',
            'new_name' => '_sd_lar_carrier_code',
        ),
        array(
            'old_name' => '_lar_label',
            'new_name' => '_sd_lar_label',
        ),
        array(
            'old_name' => '_lar_ncv',
            'new_name' => '_sd_lar_ncv',
        ),
        array(
            'old_name' => '_lar_order_number',
            'new_name' => '_sd_lar_order_number',
        ),
        array(
            'old_name' => '_lar_packages',
            'new_name' => '_sd_lar_packages',
        ),
        array(
            'old_name' => '_lar_service_code',
            'new_name' => '_sd_lar_service_code',
        ),
        array(
            'old_name' => '_lar_service_cost',
            'new_name' => '_sd_lar_service_cost',
        ),
        array(
            'old_name' => '_lar_service_display_cost',
            'new_name' => '_sd_lar_service_display_cost',
        ),
        array(
            'old_name' => '_lar_signature',
            'new_name' => '_sd_lar_signature',
        ),
        array(
            'old_name' => '_lar_tracking_nb',
            'new_name' => '_sd_lar_tracking_nb',
        ),
        array(
            'old_name' => '_lar_tracking_url',
            'new_name' => '_sd_lar_tracking_url',
        ),
        array(
            'old_name' => 'lar_carrier_data',
            'new_name' => 'sd_lar_carrier_data',
        ),
    );

    $orders = wc_get_orders(array('limit' => -1, 'post_type' => 'shop_order'));
    if ($orders) {
        foreach ($orders as $order) {
            foreach ($ordermeta as $o) {
                $value = null;

                if ($o['old_name'] === '_lar_service_cost' || $o['old_name'] === '_lar_service_display_cost')
                    $value = $order->get_meta($o['old_name']) !== null && $order->get_meta($o['old_name']) !== '' ? floatval($order->get_meta($o['old_name'])) : null;
                else if ($order->get_meta($o['old_name']))
                    $value = $order->get_meta($o['old_name']);

                if ($value || is_numeric($value))
                    $order->update_meta_data($o['new_name'], $value);

                $order->delete_meta_data($o['old_name']);
            }
            $order->save();
        }
    }

    /** PRODUCTS META **/
    $postmeta = array(
        array(
            'old_name' => '_lar_ncv',
            'new_name' => '_sd_lar_ncv',
        ),
    );

    $products = wc_get_products(array('limit' => -1));
    if ($products) {
        foreach ($products as $product) {
            foreach ($postmeta as $o) {
                if (get_post_meta($product->get_id(), $o['old_name'], true) || is_numeric(get_post_meta($product->get_id(), $o['old_name'], true)))
                    update_post_meta($product->get_id(), $o['new_name'], get_post_meta($product->get_id(), $o['old_name'], true));
                delete_post_meta($product->get_id(), $o['old_name']);
            }

            $variations = $product->get_children();
            if ($variations) {
                foreach ($variations as $variation) {
                    foreach ($postmeta as $o) {
                        if (get_post_meta($variation, $o['old_name'], true) || is_numeric(get_post_meta($variation, $o['old_name'], true)))
                            update_post_meta($variation, $o['new_name'], get_post_meta($variation, $o['old_name'], true));
                        delete_post_meta($variation, $o['old_name']);
                    }
                }
            }
        }
    }
}