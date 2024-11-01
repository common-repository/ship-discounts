<?php
if (!defined('ABSPATH'))
    exit;

if (!function_exists('sd_lar_free_shipping_value_old_hook')) {
    function sd_lar_free_shipping_value_old_hook($value, $user, $items) {
        return apply_filters_deprecated('lar_free_shipping_value', [$value, $user, $items], '1.0.7', 'sd_lar_free_shipping_value');
    }
    add_filter('sd_lar_free_shipping_value', 'sd_lar_free_shipping_value_old_hook', 0, 3);
}

if (!function_exists('sd_lar_method_cost_value_old_hook')) {
    function sd_lar_method_cost_value_old_hook($value, $user, $items) {
	    return apply_filters_deprecated('lar_method_cost_value', [$value, $user, $items], '1.0.7', 'sd_lar_method_cost_value');
    }
    add_filter('sd_lar_method_cost_value', 'sd_lar_method_cost_value_old_hook', 0, 3);
}

if (!function_exists('sd_lar_carrier_display_cost_value_old_hook')) {
    function sd_lar_carrier_display_cost_value_old_hook($value, $cost, $carrier, $service, $user, $items) {
	    return apply_filters_deprecated('lar_carrier_display_cost_value', [$value, $cost, $carrier, $service, $user, $items], '1.0.7', 'sd_lar_carrier_display_cost_value');
    }
    add_filter('sd_lar_carrier_display_cost_value', 'sd_lar_carrier_display_cost_value_old_hook', 0, 6);
}

if (!function_exists('sd_lar_boxes_cost_value_old_hook')) {
    function sd_lar_boxes_cost_value_old_hook($value, $enabled, $user, $items) {
	    return apply_filters_deprecated('lar_boxes_cost_value', [$value, $enabled, $user, $items], '1.0.7', 'sd_lar_boxes_cost_value');
    }
    add_filter('sd_lar_boxes_cost_value', 'sd_lar_boxes_cost_value_old_hook', 0, 4);
}

if (!function_exists('sd_lar_delivery_date_value_old_hook')) {
    function sd_lar_delivery_date_value_old_hook($value, $carrier, $service, $user, $items) {
	    return apply_filters_deprecated('lar_delivery_date_value', [$value, $carrier, $service, $user, $items], '1.0.7', 'sd_lar_delivery_date_value');
    }
    add_filter('sd_lar_delivery_date_value', 'sd_lar_delivery_date_value_old_hook', 0, 5);
}