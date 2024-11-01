<?php
if (!defined('WP_UNINSTALL_PLUGIN'))
    die();

use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
global $wpdb;

// WP_OPTIONS
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'woocommerce\_sd_lar\_method\_%' OR option_name LIKE 'sd_lar\_%';");

// SHIPPING METHODS
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query("DELETE FROM {$wpdb->prefix}woocommerce_shipping_zone_methods WHERE method_id = 'sd_lar_method';");

// ORDERS META
try {
    $orders_meta_table = wc_get_container()->get(OrdersTableDataStore::class)->get_meta_table_name();
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $orders_meta_table is hardcoded.
    $wpdb->query("DELETE FROM $orders_meta_table WHERE meta_key LIKE '_sd_lar\_%' OR meta_key LIKE 'sd_lar\_%';");
} catch(\Throwable $e) {}

// PRODUCTS META
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key LIKE 'sd_lar\_%' OR meta_key LIKE '_sd_lar\_%';");

wp_cache_flush();