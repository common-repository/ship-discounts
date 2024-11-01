<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Ship_Discounts_Product')) {
    /**
     * Class managing the settings of the products.
     */
    class Ship_Discounts_Product {
        /**
         * @var bool|Ship_Discounts_Settings Current instance of the class.
         */
        private static $instance = false;

        /**
         * Constructor.
         */
        private function __construct() {
            add_action('woocommerce_product_options_shipping', array($this, 'sd_lar_add_shipping_options'));
            add_action('woocommerce_variation_options_dimensions', array($this, 'sd_lar_add_shipping_options_variations'), 10, 3);
            add_action('woocommerce_process_product_meta', array($this, 'sd_lar_save_shipping_options'));
            add_action('woocommerce_save_product_variation', array($this, 'sd_lar_save_shipping_options_variations'), 10, 2);
        }

        /**
         * Returns the current instance. If it does not exist, it is first created.
         * @return Ship_Discounts_Product Current instance.
         */
        public static function getInstance(): Ship_Discounts_Product {
            if (!self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        /**
         * Adds custom shipping option to products.
         * @return void
         */
        function sd_lar_add_shipping_options() {
            global $post;

            woocommerce_wp_checkbox( array(
                'id'          => '_sd_lar_ncv_s',
                'label'       => esc_html__('Non-conveyable', 'ship-discounts'),
                'desc_tip'    => 'true',
                'description' => esc_html__('Whether or not this product is non-conveyable.', 'ship-discounts'),
                'value'       => get_post_meta($post->ID, '_sd_lar_ncv', true),
            ) );
        }

        /**
         * Adds custom shipping option to product variations.
         * @return void
         */
        function sd_lar_add_shipping_options_variations($loop, $variation_data, $variation) {
            global $post;

            echo '<p class="form-row form-row-full options"></p>';

            woocommerce_wp_checkbox( array(
                'id'          => '_sd_lar_ncv[' . $loop . ']',
                'label'       => esc_html__('Non-conveyable', 'ship-discounts'),
                'desc_tip'    => 'true',
                'description' => esc_html__('Whether or not this product is non-conveyable.', 'ship-discounts'),
                'value'       => get_post_meta($variation->ID, '_sd_lar_ncv', true),
                'default'     => get_post_meta($post->ID, '_sd_lar_ncv', true),
            ) );
        }

        /**
         * Saves the custom shipping options of products.
         * @param $post_id string|int ID of the product.
         * @return void
         */
        function sd_lar_save_shipping_options($post_id) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $data = wc_clean($_POST['_sd_lar_ncv_s']) ? 'yes' : '';
            update_post_meta($post_id, '_sd_lar_ncv', $data);
        }

        /**
         * Saves the custom shipping options of product variations.
         * @param $variation_id string|int ID of the product variation.
         * @return void
         */
        function sd_lar_save_shipping_options_variations($variation_id, $loop) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing
            $data = wc_clean($_POST['_sd_lar_ncv'][$loop]) ? 'yes' : '';
            update_post_meta($variation_id, '_sd_lar_ncv', $data);
        }
    }

    $Ship_Discounts_Product = Ship_Discounts_Product::getInstance();
}