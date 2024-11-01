<?php

if (!defined('ABSPATH'))
    exit;

if (class_exists('WC_Shipping_Method') && !class_exists('WC_Shipping_Ship_Discounts')) {
    /**
     * Class of the Ship Discounts shipping method.
     */
    class WC_Shipping_Ship_Discounts extends WC_Shipping_Method {
        /**
         * @var string Cost passed to [fee] shortcode.
         */
        protected $fee_cost = '';

        /**
         * @var string Shipping method cost.
         */
        public $cost;

        /**
         * @var string Shipment value.
         */
        public $shipment_value;

        /**
         * @var bool If predefined package's dimensions and weight must be used.
         */
        public $predefined_package;

        /**
         * @var string Predefined package's width.
         */
        public $package_width;

        /**
         * @var string Predefined package's length.
         */
        public $package_length;

        /**
         * @var string Predefined package's height.
         */
        public $package_height;

        /**
         * @var string Predefined package's weight.
         */
        public $package_weight;

        /**
         * @var int How the shipping method deals with classes.
         */
        public $class_list_type;

        /**
         * @var array Allowed classes.
         */
        public $class_list_allow;

        /**
         * @var array Denied classes.
         */
        public $class_list_deny;

        /**
         * @var array Enabled carriers.
         */
        public $carriers_list;

        /**
         * Constructor.
         * @param int $instance_id Shipping method instance ID.
         */
        public function __construct($instance_id = 0) {
            $this->id = 'sd_lar_method';
            $this->instance_id = absint($instance_id);
            $this->method_title = esc_html__('Ship Discounts', 'ship-discounts');
            $this->method_description = esc_html__('Offer your customers shipping services with real-time quotes.', 'ship-discounts');
            $this->supports = array(
                'shipping-zones',
                'instance-settings',
                'instance-settings-modal',
            );

            add_action('woocommerce_generate_carriers_list_html', array($this, 'sd_lar_generate_carriers_list_html'), 10, 4);
            $this->init();
            add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        }

        /**
         * Init user set variables.
         */
        public function init() {
            $this->instance_form_fields = include __DIR__ . '/settings-shipping-method.php';

            $this->title = $this->get_option('title');
            $this->cost = $this->get_option('cost');
            $this->shipment_value = $this->get_option('shipment_value', 100);
            $this->predefined_package = $this->get_option('predefined_package', false);
            $this->package_width = $this->get_option('package_width', 10);
            $this->package_length = $this->get_option('package_length', 10);
            $this->package_height = $this->get_option('package_height', 12);
            $this->package_weight = $this->get_option('package_weight', 12);
            $this->class_list_type = $this->get_option('class_list_type', 0);
            $this->class_list_allow = $this->get_option('class_list_allow', []);
            $this->class_list_deny = $this->get_option('class_list_deny', []);
            $this->carriers_list = $this->get_option('carriers_list', []);
        }

        /**
         * Get a field's posted and validated value.
         * @param string $key Field key.
         * @param array $field Field array.
         * @param array $post_data Posted data.
         * @return string
         */
        public function get_field_value($key, $field, $post_data = array()) {
            $type = $this->get_field_type($field);
            $field_key = $this->get_field_key($key);
            $value = !empty($post_data) && isset($post_data[$field_key]) ? $post_data[$field_key] : null;

            // Create the carriers array
            if ($key == 'carriers_list') {
                $carriers = [];
                foreach ($post_data as $k => $v) {
                    if (strpos($k, $field_key) !== false) {
                        $string_key = preg_replace('/' . $field_key . '_/', '', $k, 1);
                        $keys = explode('_', $string_key);
                        $carriers[$keys[0]]['services'][$keys[1]][$keys[2]] = $v;
                    }
                }
                $value = $carriers;
            }

            if (isset($field['sanitize_callback']) && is_callable($field['sanitize_callback'])) {
                return call_user_func($field['sanitize_callback'], $value);
            }

            // Look for a validate_FIELDID_field method for special handling.
            if (is_callable(array($this, 'validate_' . $key . '_field'))) {
                return $this->{'validate_' . $key . '_field'}($key, $value);
            }

            // Look for a validate_FIELDTYPE_field method.
            if (is_callable(array($this, 'validate_' . $type . '_field'))) {
                return $this->{'validate_' . $type . '_field'}($key, $value);
            }

            // Fallback to text.
            return $this->validate_text_field($key, $value);
        }

        /**
         * Validate Carriers List Field.
         * @param string $key Field key.
         * @param string $value Posted Value.
         * @return array
         */
        public function validate_carriers_list_field($key, $value) {
            if (is_array($value)) {
                $carriers = Ship_Discounts_Carriers::getCarriers();
                foreach ($carriers as $code => $data) {
                    foreach ($data['services'] as $serv_code => $service) {
                        $value[$code]['services'][$serv_code]['name'] = wc_clean($value[$code]['services'][$serv_code]['name']);
                        $value[$code]['services'][$serv_code]['enabled'] = (bool)$value[$code]['services'][$serv_code]['enabled'];

                        $factor = wc_clean($value[$code]['services'][$serv_code]['factor']);
                        $value[$code]['services'][$serv_code]['factor'] = is_numeric($factor) ? $factor : null;

                        $carriers[$code]['services'][$serv_code] = $value[$code]['services'][$serv_code];
                    }
                }
                return $carriers;
            }
            return [];
        }

        /**
         * Evaluate a cost from a sum/string.
         * @param string $sum Sum of shipping.
         * @param array $args Args, must contain `cost` and `qty` keys. Having `array()` as default is for back compat reasons.
         * @return string Cost.
         */
        protected function evaluate_cost($sum, $args = array()) {
            // Add warning for subclasses.
            if (!is_array($args) || !array_key_exists('qty', $args) || !array_key_exists('cost', $args)) {
                wc_doing_it_wrong(__FUNCTION__, '$args must contain `cost` and `qty` keys.', '4.0.1');
            }

            include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';

            // Allow 3rd parties to process shipping cost arguments.
            $args = apply_filters('woocommerce_evaluate_shipping_cost_args', $args, $sum, $this);
            $locale = localeconv();
            $decimals = array(wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',');
            $this->fee_cost = $args['cost'];

            // Expand shortcodes.
            add_shortcode('sd_lar_fee', array($this, 'fee'));

            $sum = do_shortcode(
                str_replace(
                    array(
                        '[qty]',
                        '[cost]',
                    ),
                    array(
                        $args['qty'],
                        $args['cost'],
                    ),
                    $sum
                )
            );

            remove_shortcode('sd_lar_fee', array($this, 'fee'));

            // Remove whitespace from string.
            $sum = preg_replace('/\s+/', '', $sum);

            // Remove locale from string.
            $sum = str_replace($decimals, '.', $sum);

            // Trim invalid start/end characters.
            $sum = rtrim(ltrim($sum, "\t\n\r\0\x0B+*/"), "\t\n\r\0\x0B+-*/");

            // Do the math.
            return $sum ? WC_Eval_Math::evaluate($sum) : 0;
        }

        /**
         * Work out fee (shortcode).
         * @param array $atts Attributes.
         * @return string Fee.
         */
        public function fee($atts) {
            $atts = shortcode_atts(
                array(
                    'percent' => '',
                    'min_fee' => '',
                    'max_fee' => '',
                ),
                $atts,
                'sd_lar_fee'
            );

            $calculated_fee = 0;

            if ($atts['percent']) {
                $calculated_fee = $this->fee_cost * (floatval($atts['percent']) / 100);
            }

            if ($atts['min_fee'] && $calculated_fee < $atts['min_fee']) {
                $calculated_fee = $atts['min_fee'];
            }

            if ($atts['max_fee'] && $calculated_fee > $atts['max_fee']) {
                $calculated_fee = $atts['max_fee'];
            }

            return $calculated_fee;
        }

        /**
         * Calculate the shipping costs.
         * @param array $package Package of items from cart.
         */
        public function calculate_shipping($package = array()) {
            $rate = array(
                'id'      => $this->get_rate_id(),
                'label'   => $this->title,
                'cost'    => 0,
                'package' => $package,
            );

            // Calculate the costs.
            $has_costs = false; // True when a cost is set. False if all costs are blank strings.
            $cost = $this->get_option('cost');

            if ('' !== $cost) {
                $has_costs = true;
                $rate['cost'] = $this->evaluate_cost(
                    $cost,
                    array(
                        'qty'  => $this->get_package_item_qty($package),
                        'cost' => $package['contents_cost'],
                    )
                );
            }

            // Add shipping class costs.
            $shipping_classes = WC()->shipping()->get_shipping_classes();

            if (!empty($shipping_classes)) {
                $found_shipping_classes = $this->find_shipping_classes($package);
                $highest_class_cost = 0;

                foreach ($found_shipping_classes as $shipping_class => $products) {
                    // Also handles BW compatibility when slugs were used instead of ids.
                    $shipping_class_term = get_term_by('slug', $shipping_class, 'product_shipping_class');
                    $class_cost_string = $shipping_class_term && $shipping_class_term->term_id ? $this->get_option('class_cost_' . $shipping_class_term->term_id, $this->get_option('class_cost_' . $shipping_class, '')) : $this->get_option('no_class_cost', '');

                    if ('' === $class_cost_string) {
                        continue;
                    }

                    $has_costs = true;
                    $class_cost = $this->evaluate_cost(
                        $class_cost_string,
                        array(
                            'qty'  => array_sum(wp_list_pluck($products, 'quantity')),
                            'cost' => array_sum(wp_list_pluck($products, 'line_total')),
                        )
                    );

                    $highest_class_cost = max($class_cost, $highest_class_cost);
                }

                $rate['cost'] += $highest_class_cost;
            }

            if ($has_costs) {
                $this->add_rate($rate);
            }

            do_action('woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate);
        }

        /**
         * Get items in package.
         * @param array $package Package of items from cart.
         * @return int Total quantity.
         */
        public function get_package_item_qty($package) {
            $total_quantity = 0;
            foreach ($package['contents'] as $item_id => $values) {
                if ($values['quantity'] > 0 && $values['data']->needs_shipping()) {
                    $total_quantity += $values['quantity'];
                }
            }
            return $total_quantity;
        }

        /**
         * Finds and returns shipping classes and the products with said class.
         * @param mixed $package Package of items from cart.
         * @return array Found shipping classes and the products with said class.
         */
        public function find_shipping_classes($package) {
            $found_shipping_classes = array();

            foreach ($package['contents'] as $item_id => $values) {
                if ($values['data']->needs_shipping()) {
                    $found_class = $values['data']->get_shipping_class();

                    if (!isset($found_shipping_classes[$found_class])) {
                        $found_shipping_classes[$found_class] = array();
                    }

                    $found_shipping_classes[$found_class][$item_id] = $values;
                }
            }

            return $found_shipping_classes;
        }

        /**
         * Sanitize the cost field.
         * @param string $value Unsanitized value.
         * @return string Sanitized value.
         * @throws Exception Last error triggered.
         */
        public function sanitize_cost($value) {
            $value = is_null($value) ? '' : $value;
            $value = wp_kses_post(trim(wp_unslash($value)));
            $value = str_replace(array(get_woocommerce_currency_symbol(), html_entity_decode(get_woocommerce_currency_symbol())), '', $value);
            // Thrown an error on the front end if the evaluate_cost will fail.
            $dummy_cost = $this->evaluate_cost(
                $value,
                array(
                    'cost' => 1,
                    'qty'  => 1,
                )
            );
            if (false === $dummy_cost) {
                // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                throw new Exception(WC_Eval_Math::$last_error);
            }
            return $value;
        }

        /**
         * Generates Carriers List HTML
         * @param string $field_html The markup of the field being generated (initiated as an empty string).
         * @param string $key The key of the field.
         * @param array $data The attributes of the field as an associative array.
         * @param object $wc_settings The current WC_Settings_API object.
         * @return string HTML
         */
        function sd_lar_generate_carriers_list_html($field_html, $key, $data, $wc_settings) {
            ob_start();

            $field_key = $this->get_field_key($key);
            $defaults = array(
                'title'             => '',
                'disabled'          => false,
                'class'             => '',
                'css'               => '',
                'desc_tip'          => false,
                'description'       => '',
                'custom_attributes' => array(),
            );

            $data = wp_parse_args($data, $defaults);
            $value = $this->get_option($key);
            ?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field_key); ?>"><?php echo esc_html($data['title']); ?>
                        <?php
                        echo wp_kses_post($this->get_tooltip_html($data)); // WPCS: XSS ok. ?>
                    </label>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php echo esc_html($data['title']); ?></span>
                        </legend>

                        <table class="lar_carriers_table lar_carriers_table_modal widefat <?php echo esc_attr($data['class']); ?>">
                            <thead>
                            <tr>
                                <th><?php echo esc_html__('Carrier.Service Code', 'ship-discounts') ?></th>
                                <th><?php echo esc_html__('Name', 'ship-discounts') ?></th>
                                <th><?php echo esc_html__('Enabled', 'ship-discounts') ?></th>
                                <th><?php echo esc_html__('Factor to apply', 'ship-discounts') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            foreach ($value as $code => $d) {
                                foreach ($d['services'] as $serv_code => $service) {
                                    echo '<tr>';
                                    ?>
                                    <td class="lar_carriers_service_code">
                                        <div class="view"><?php echo esc_html($code . '.' . $serv_code) ?></div>
                                    </td>
                                    <td>
                                        <div class="view">
                                            <input type="text" value="<?php echo esc_attr($service['name']) ?>"
                                                   name="<?php echo esc_attr($field_key); ?>_<?php echo esc_attr($code) ?>_<?php echo esc_attr($serv_code) ?>_name">
                                        </div>
                                    </td>
                                    <td class="lar_carriers_center">
                                        <div class="view">
                                            <input type="hidden" value=""
                                                   name="<?php echo esc_attr($field_key); ?>_<?php echo esc_attr($code) ?>_<?php echo esc_attr($serv_code) ?>_enabled">
                                            <input type="checkbox"
                                                   value="1" <?php echo $service['enabled'] ? ' checked ' : '' ?>
                                                   name="<?php echo esc_attr($field_key); ?>_<?php echo esc_attr($code) ?>_<?php echo esc_attr($serv_code) ?>_enabled">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="view">
                                            <input type="number" value="<?php echo esc_attr($service['factor']) ?>" step="0.01" min="0"
                                                   placeholder="N/A"
                                                   name="<?php echo esc_attr($field_key); ?>_<?php echo esc_attr($code) ?>_<?php echo esc_attr($serv_code) ?>_factor">
                                        </div>
                                    </td>
                                    <?php
                                    echo '</tr>';
                                }
                            }
                            ?>
                            </tbody>
                        </table>

                        <?php
                        echo wp_kses_post($this->get_description_html($data)); // WPCS: XSS ok. ?>
                    </fieldset>
                </td>
            </tr>

            <?php
            return ob_get_clean();
        }
    }

    /**
     * Registers the Ship Discounts shipping method.
     * @param $methods mixed Shipping methods.
     * @return mixed Shipping methods.
     */
    function sd_lar_register_method($methods) {
        $methods['sd_lar_method'] = 'WC_Shipping_Ship_Discounts';
        return $methods;
    }

    add_filter('woocommerce_shipping_methods', 'sd_lar_register_method');
}