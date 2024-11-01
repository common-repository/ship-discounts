<?php
if (!defined('ABSPATH'))
    exit;

/** General */

$settings = array(
    "title"              => array(
        'title'       => esc_html__('Name', 'ship-discounts'),
        'type'        => 'text',
        'description' => esc_html__('Your customers will see the name of this shipping method during checkout.', 'ship-discounts'),
        'default'     => esc_html__('Ship Discounts', 'ship-discounts'),
        'desc_tip'    => true
    ),
    'cost'               => array(
        'title'             => esc_html__('Cost', 'ship-discounts'),
        'type'              => 'number',
        'placeholder'       => '',
        'description'       => esc_html__('Enter a base cost. Carrier and box rates (if applicable) will be added to this cost.', 'ship-discounts'),
        'default'           => '0',
        'desc_tip'          => true,
        'sanitize_callback' => array($this, 'sanitize_cost'),
    ),
    'shipment_value'     => array(
        'title'             => esc_html__('Shipment value', 'ship-discounts'),
        'type'              => 'number',
        'placeholder'       => '',
        'description'       => esc_html__('This value represents the maximum claimable value for a lost or damaged shipment. Default is 100 CAD which is included without any charge.', 'ship-discounts'),
        'default'           => get_option('sd_lar_settings_shipment_value', '100'),
        'desc_tip'          => true,
        'custom_attributes' => array("step" => 0.01, "min" => 0),
    ),
    'predefined_package' => array(
        'label'       => esc_html__('Use a predefined package for rate quoting?', 'ship-discounts'),
        'type'        => 'checkbox',
        'class'       => 'lar_predefined_package_checkbox',
        'description' => esc_html__('You can set one package\'s dimensions and weight and use it for rate quoting. If this option is disabled, the product\'s dimensions and weight will be used instead.', 'ship-discounts'),
        'desc_tip'    => true,
        'default'     => get_option('sd_lar_settings_predefined_package', false),
    ),
    'package_width'      => array(
        'title'             => esc_html__('Predefined package\'s width', 'ship-discounts'),
        'type'              => 'number',
        'class'             => 'lar_predefined_package_dimensions_modal',
        'description'       => esc_html__('Package\'s width in inches.', 'ship-discounts'),
        'default'           => get_option('sd_lar_settings_package_width', '10'),
        'desc_tip'          => true,
        'custom_attributes' => array("step" => 0.01, "min" => 0.1),
    ),
    'package_length'     => array(
        'title'             => esc_html__('Predefined package\'s length', 'ship-discounts'),
        'type'              => 'number',
        'class'             => 'lar_predefined_package_dimensions_modal',
        'description'       => esc_html__('Package\'s length in inches.', 'ship-discounts'),
        'default'           => get_option('sd_lar_settings_package_length', '10'),
        'desc_tip'          => true,
        'custom_attributes' => array("step" => 0.01, "min" => 0.1),
    ),
    'package_height'     => array(
        'title'             => esc_html__('Predefined package\'s height', 'ship-discounts'),
        'type'              => 'number',
        'class'             => 'lar_predefined_package_dimensions_modal',
        'description'       => esc_html__('Package\'s height in inches.', 'ship-discounts'),
        'default'           => get_option('sd_lar_settings_package_height', '12'),
        'desc_tip'          => true,
        'custom_attributes' => array("step" => 0.01, "min" => 0.1),
    ),
    'package_weight'     => array(
        'title'             => esc_html__('Predefined package\'s weight', 'ship-discounts'),
        'type'              => 'number',
        'class'             => 'lar_predefined_package_dimensions_modal',
        'description'       => esc_html__('Package\'s weight in pounds.', 'ship-discounts'),
        'default'           => get_option('sd_lar_settings_package_weight', '12'),
        'desc_tip'          => true,
        'custom_attributes' => array("step" => 0.01, "min" => 0.1),
    ),
);

/** Classes */

$shipping_classes = WC()->shipping()->get_shipping_classes();

if (!empty($shipping_classes)) {
    $settings['class_lists'] = array(
        'title'       => esc_html__('Allowed/Denied classes', 'ship-discounts'),
        'type'        => 'title',
        'default'     => '',
        /* translators: %s: WooCommerce shipping classes settings page */
        'description' => wp_kses_post(sprintf(__('Select which <a target="_blank" href="%s">product shipping classes</a> can or can not use this shipping method.', 'ship-discounts'), admin_url('admin.php?page=wc-settings&tab=shipping&section=classes'))),
    );

    $settings['class_list_type'] = array(
        'type'    => 'select',
        'class'   => 'lar_classes_list_type',
        'options' => array(
            0 => esc_html__('Allow all products', 'ship-discounts'),
            1 => esc_html__('Only allow these classes', 'ship-discounts'),
            2 => esc_html__('Only deny these classes', 'ship-discounts'),
        ),
        'default' => get_option('sd_lar_settings_class_list_type', 0),
    );

    $classes = [];
    foreach ($shipping_classes as $shipping_class) {
        if (isset($shipping_class->term_id))
            $classes[$shipping_class->term_id] = $shipping_class->name;
    }

    $settings['class_list_allow'] = array(
        'type'    => 'multiselect',
        'class'   => 'lar_classes_list lar_classes_list_allow lar_classes_list_hide_modal lar_classes_list_allow_hide_modal',
        'options' => array(esc_html__('Allowlist', 'ship-discounts') => $classes),
        'default' => get_option('sd_lar_settings_class_list_allow', []),
    );

    $settings['class_list_deny'] = array(
        'type'    => 'multiselect',
        'class'   => 'lar_classes_list lar_classes_list_deny lar_classes_list_hide_modal lar_classes_list_deny_hide_modal',
        'options' => array(esc_html__('Denylist', 'ship-discounts') => $classes),
        'default' => get_option('sd_lar_settings_class_list_deny', []),
    );
}

/** Carriers */

$settings['carriers'] = array(
    'title' => esc_html__('Carriers', 'ship-discounts'),
    'type'    => 'title',
    'description' => esc_html__('Select which carriers to display. You can also set a factor to apply on the rate for each of them. Example : A factor of 1.2 will multiply the carrier’s rate by 1.2 before being presented at checkout.', 'ship-discounts')
);

$settings['carriers_list'] = array(
    'type'    => 'carriers_list',
    'default' => get_option('sd_lar_settings_carriers_list', Ship_Discounts_Carriers::getCarriers()),
);

return $settings;