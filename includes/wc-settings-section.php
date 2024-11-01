<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('Ship_Discounts_Settings')) {
    /**
    * Class managing the settings of the Ship Discounts plugin.
    */
    class Ship_Discounts_Settings {
        /**
        * @var bool|Ship_Discounts_Settings Current instance of the class.
        */
        private static $instance = false;
        /**
        * @var array General settings.
        */
        private $settings_general = [];

        /**
        * Constructor.
        */
        private function __construct() {
            add_filter('woocommerce_general_settings', array($this, 'sd_lar_add_phone_number'));
            add_filter('woocommerce_get_sections_shipping', array($this, 'sd_lar_add_settings_section'));
            add_filter('woocommerce_settings_shipping', array($this, 'sd_lar_set_settings_section'), 10, 2);
            add_action('woocommerce_settings_save_shipping', array($this, 'sd_lar_save_settings_section'));
        }

        /**
        * Adds the shop phone number as a setting.
        * @param $settings array General settings.
        * @return array General settings with the phone number.
        */
        function sd_lar_add_phone_number($settings) {
            $key              = array_search('woocommerce_store_postcode', array_column($settings, 'id')) + 1;
            $custom_setting[] = array(
                'title'    => esc_html__('Phone Number', 'ship-discounts'),
                'desc'     => esc_html__("Your shop phone number.", 'ship-discounts'),
                'id'       => 'sd_lar_settings_phone_number',
                'default'  => '',
                'type'     => 'text',
                'desc_tip' => true,
            );
            return array_merge(array_slice($settings, 0, $key), $custom_setting, array_slice($settings, $key));
        }

        /**
         * Return the general settings.
         * @return array General settings.
         */
        private function sd_lar_get_general_settings() {
            if (!$this->settings_general) {
                $this->settings_general = array(
                    array(
                        'name' => esc_html__('Ship Discounts', 'ship-discounts'),
                        'type' => 'title',
                        'desc' => esc_html__('Offer your customers shipping services with realtime quoting.', 'ship-discounts'),
                        'id'   => 'sd_lar_general_settings'
                    ),
                    array(
                        'name'              => esc_html__('Shipment value', 'ship-discounts') . ' (' . get_woocommerce_currency_symbol() . ')',
                        'type'              => 'number',
                        'desc'              => esc_html__('This value represents the maximum claimable value for a lost or damaged shipment. Default is 100 CAD which is included without any charge.', 'ship-discounts'),
                        'default'           => '100',
                        'desc_tip'          => true,
                        'custom_attributes' => array("step" => 0.01, "min" => 0),
                        'id'                => 'sd_lar_settings_shipment_value'
                    ),
                    array(
                        'name'              => esc_html__('Free shipping on orders over', 'ship-discounts') . '...',
                        'type'              => 'number',
                        'desc'              => esc_html__('Shipping will be free for orders of this value and above. The cheapest carrier will be automatically selected for you. Leave blank to disable the option.', 'ship-discounts'),
                        'default'           => '',
                        'desc_tip'          => true,
                        'custom_attributes' => array("step" => 0.01, "min" => 0),
                        'id'                => 'sd_lar_settings_free_shipping_value'
                    ),
                    array(
                        'name'              => esc_html__('Show the signature option?', 'ship-discounts'),
                        'type'              => 'checkbox',
                        'desc'              => esc_html__('Allow customer to select if they need a signature.', 'ship-discounts'),
                        'default'           => 'yes',
                        'desc_tip'          => true,
                        'id'                => 'sd_lar_settings_signature'
                    ),
                    array(
                        'name'              => esc_html__('Use a predefined package for rate quoting?', 'ship-discounts'),
                        'type'              => 'checkbox',
                        'class'             => 'lar_predefined_package_checkbox',
                        'desc'              => esc_html__('You can set one package\'s dimensions and weight and use it for rate quoting. If this option is disabled, the product\'s dimensions and weight will be used instead.', 'ship-discounts'),
                        'desc_tip'          => true,
                        'id'                => 'sd_lar_settings_predefined_package'
                    ),
                    array(
                        'name'              => esc_html__('Predefined package\'s width', 'ship-discounts'),
                        'type'              => 'number',
                        'class'             => 'lar_predefined_package_dimensions_page',
                        'desc'              => esc_html__('Package\'s width in inches. The value must be between 0.1 and 144.', 'ship-discounts'),
                        'default'           => '10',
                        'desc_tip'          => true,
                        'custom_attributes' => array("step" => 0.01, "min" => 0.1, "max" => 144),
                        'id'                => 'sd_lar_settings_package_width'
                    ),
                    array(
                        'name'              => esc_html__('Predefined package\'s length', 'ship-discounts'),
                        'type'              => 'number',
                        'class'             => 'lar_predefined_package_dimensions_page',
                        'desc'              => esc_html__('Package\'s length in inches. The value must be between 0.1 and 144.', 'ship-discounts'),
                        'default'           => '10',
                        'desc_tip'          => true,
                        'custom_attributes' => array("step" => 0.01, "min" => 0.1, "max" => 144),
                        'id'                => 'sd_lar_settings_package_length'
                    ),
                    array(
                        'name'              => esc_html__('Predefined package\'s height', 'ship-discounts'),
                        'type'              => 'number',
                        'class'             => 'lar_predefined_package_dimensions_page',
                        'desc'              => esc_html__('Package\'s height in inches. The value must be between 0.1 and 144.', 'ship-discounts'),
                        'default'           => '12',
                        'desc_tip'          => true,
                        'custom_attributes' => array("step" => 0.01, "min" => 0.1, "max" => 144),
                        'id'                => 'sd_lar_settings_package_height'
                    ),
                    array(
                        'name'              => esc_html__('Predefined package\'s weight', 'ship-discounts'),
                        'type'              => 'number',
                        'class'             => 'lar_predefined_package_dimensions_page',
                        'desc'              => esc_html__('Package\'s weight in pounds. The value must be between 0.1 and 150.', 'ship-discounts'),
                        'default'           => '12',
                        'desc_tip'          => true,
                        'custom_attributes' => array("step" => 0.01, "min" => 0.1, "max" => 150),
                        'id'                => 'sd_lar_settings_package_weight'
                    ),
                    array('type' => 'sectionend', 'id' => 'sd_lar_general_settings'),
                );

            }
            return $this->settings_general;
        }

        /**
         * Return the shipping classes settings.
         * @return array Shipping classes settings.
         */
        private function sd_lar_get_classes_settings() {
            $settings = [];
            $shipping_classes = WC()->shipping()->get_shipping_classes();

            if (!empty($shipping_classes)) {
                $settings[] = array(
                    'name'    => esc_html__('Allowed/Denied classes', 'ship-discounts'),
                    'type'    => 'title',
                    'default' => '',
                     /* translators: %s: WooCommerce shipping classes settings page */
                    'desc'    => wp_kses_post(sprintf(__('Select which <a target="_blank" href="%s">product shipping classes</a> can or can not use this shipping method.', 'ship-discounts'), admin_url('admin.php?page=wc-settings&tab=shipping&section=classes'))),
                    'id'      => 'sd_lar_class_settings'
                );

                $settings[] = array(
                    'name'    => esc_html__('Class management', 'ship-discounts'),
                    'type'    => 'select',
                    'class'   => 'lar_classes_list_type',
                    'options' => array(
                        0 => esc_html__('Allow all products', 'ship-discounts'),
                        1 => esc_html__('Only allow these classes', 'ship-discounts'),
                        2 => esc_html__('Only deny these classes', 'ship-discounts'),
                    ),
                    'default' => 0,
                    'id'      => 'sd_lar_settings_class_list_type'
                );

                $classes = [];
                foreach ($shipping_classes as $shipping_class) {
                    if (isset($shipping_class->term_id)) {
                        $classes[$shipping_class->term_id] = $shipping_class->name;
                    }
                }

                $settings[] = array(
                    'name'    => esc_html__('Allowlist', 'ship-discounts'),
                    'type'    => 'multiselect',
                    'class'   => 'lar_classes_list lar_classes_list_allow lar_classes_list_hide_page lar_classes_list_allow_hide_page',
                    'options' => $classes,
                    'id'      => 'sd_lar_settings_class_list_allow',
                );

                $settings[] = array(
                    'name'    => esc_html__('Denylist', 'ship-discounts'),
                    'type'    => 'multiselect',
                    'class'   => 'lar_classes_list lar_classes_list_deny lar_classes_list_hide_page lar_classes_list_deny_hide_page',
                    'options' => $classes,
                    'id'      => 'sd_lar_settings_class_list_deny',
                );

                $settings[] = array('type' => 'sectionend', 'id' => 'sd_lar_class_settings');
            }

            return $settings;
        }

        /**
         * Return the carriers settings.
         */
        private function sd_lar_get_carriers_settings() {
            ob_start();
            $carriers = get_option('sd_lar_settings_carriers_list', Ship_Discounts_Carriers::getCarriers());

            if ($carriers) { ?>
                <h2><?php echo esc_html__('Carriers', 'ship-discounts') ?></h2>
                <div id="lar_carriers_settings-description">
                    <?php echo esc_html__('Select which carriers to display. You can also set a factor to apply on the rate for each of them.', 'ship-discounts') ?>
                </div>
                <table class="form-table">
                <?php do_action('woocommerce_settings_lar_carriers_settings');

                ?>
                <br>
                <table class="lar_carriers_table widefat">
                    <thead>
                    <tr>
                        <th><?php echo esc_html__('Carrier Code', 'ship-discounts') ?></th>
                        <th><?php echo esc_html__('Service Code', 'ship-discounts') ?></th>
                        <th><?php echo esc_html__('Name', 'ship-discounts') ?></th>
                        <th><?php echo esc_html__('Enabled', 'ship-discounts') ?></th>
                        <th>
                            <?php echo esc_html__('Factor to apply', 'ship-discounts') ?>
                            <?php
                            echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                    'desc_tip' => true,
                                    'desc'     => esc_html__('You can set a factor to apply on the actual rates. Example : A factor of 1.2 will multiply the carrier’s rate by 1.2 before being presented at checkout.', 'ship-discounts')]
                            )['tooltip_html']); ?>
                        </th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    foreach ($carriers as $code => $data) {
                        ?>
                        <tr class="lar_carriers_row">
                            <td class="lar_carriers_service_code"><?php echo esc_html($code) ?></td>
                            <td></td>
                            <td class="lar_carriers_service_code"><?php echo esc_html($data['name']) ?></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <?php
                        foreach ($data['services'] as $serv_code => $service) {
                            echo '<tr>';
                            ?>
                            <td></td>
                            <td class="lar_carriers_service_code">
                                <div class="view"><?php echo esc_html($serv_code) ?></div>
                            </td>
                            <td>
                                <div class="view">
                                    <input type="text" value="<?php echo esc_attr($service['name']) ?>"
                                           name="sd_lar_settings_carriers[<?php echo esc_attr($code) ?>][services][<?php echo esc_attr($serv_code) ?>][name]">
                                </div>
                            </td>
                            <td>
                                <div class="view">
                                    <input type="hidden" value=""
                                           name="sd_lar_settings_carriers[<?php echo esc_attr($code) ?>][services][<?php echo esc_attr($serv_code) ?>][enabled]">
                                    <input type="checkbox" value="1" <?php echo $service['enabled'] ? ' checked ' : '' ?>
                                           name="sd_lar_settings_carriers[<?php echo esc_attr($code) ?>][services][<?php echo esc_attr($serv_code) ?>][enabled]">
                                </div>
                            </td>
                            <td>
                                <div class="view">
                                    <input type="number" value="<?php echo esc_attr($service['factor']) ?>" step="0.01" min="0"
                                           placeholder="N/A"
                                           name="sd_lar_settings_carriers[<?php echo esc_attr($code) ?>][services][<?php echo esc_attr($serv_code) ?>][factor]">
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

                do_action('woocommerce_settings_lar_carriers_settings_end');
                echo '</table>';
                do_action('woocommerce_settings_lar_carriers_settings_after');
            }

            return ob_get_clean();
        }

        /**
         * Return the boxes settings.
         */
        private function sd_lar_get_boxes_settings() {
            ob_start();
            $boxes = get_option('sd_lar_settings_boxes', []);
            ?>

            <h2><?php echo esc_html__('Boxes', 'ship-discounts') ?></h2>
            <table class="form-table">
            <?php do_action('woocommerce_settings_lar_boxes_settings'); ?>
            <tr>
                <th scope="row" class="titledesc">
                    <?php echo esc_html__('Use predefined boxes for packaging?', 'ship-discounts') ?>
                </th>
				<td class="forminp forminp-checkbox">
				    <fieldset>
					    <legend class="screen-reader-text"><span><?php echo esc_html__('Use predefined boxes for packaging?', 'ship-discounts') ?></span></legend>
						<label for="sd_lar_settings_use_boxes">
						    <input name="sd_lar_settings_use_boxes" id="sd_lar_settings_use_boxes" type="checkbox" class="lar_settings_use_boxes_checkbox" value="1" <?php echo get_option('sd_lar_settings_use_boxes', false) ? 'checked' : '' ?>>
						</label>
						<p class="description"><?php echo esc_html__('You can specify the box types to be used to calculate package dimensions and weights. If no box is configured, each product will be packed individually. A product that doesn\'t fit into any box will also be packed individually.', 'ship-discounts') ?></p>
					</fieldset>
				</td>
			</tr>
            <table class="lar_boxes_table widefat">
					<thead>
						<tr>
						    <th>
						        <?php
						        echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                    'desc_tip' => true,
                                    'desc'     => esc_html__('This order will be prioritized when packaging.', 'ship-discounts')]
                                )['tooltip_html']); ?>
                            </th>
							<th><input type="checkbox" /></th>
							<th><?php echo esc_html__( 'Name', 'ship-discounts' ); ?></th>
							<th><?php echo esc_html__( 'Outer length', 'ship-discounts' ); ?></th>
							<th><?php echo esc_html__( 'Outer width', 'ship-discounts' ); ?></th>
							<th><?php echo esc_html__( 'Outer height', 'ship-discounts' ); ?></th>
							<th><?php echo esc_html__( 'Inner length', 'ship-discounts' ); ?></th>
							<th><?php echo esc_html__( 'Inner width', 'ship-discounts' ); ?></th>
							<th><?php echo esc_html__( 'Inner height', 'ship-discounts' ); ?></th>
							<th>
								<?php echo esc_html__( 'Weight of box', 'ship-discounts' ); ?>
								<?php
								echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                    'desc_tip' => true,
                                    'desc'     => esc_html__('Weight of the empty box. Will be added to the calculation.', 'ship-discounts')]
                                )['tooltip_html']); ?>
							</th>
							<th>
								<?php echo esc_html__( 'Max weight', 'ship-discounts' ); ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                    'desc_tip' => true,
                                    'desc'     => esc_html__('Maximum weight of the box (including its own weight).', 'ship-discounts')]
                                )['tooltip_html']); ?>
                            </th>
							<th>
							    <?php
							    echo esc_html__( 'Additional cost', 'ship-discounts' ) . ' (' . esc_html(get_woocommerce_currency_symbol()) . ')'; ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                    'desc_tip' => true,
                                    'desc'     => esc_html__('Additional cost for using this box. Will be added to shipping costs.', 'ship-discounts')]
                                )['tooltip_html']); ?>
							</th>
							<th>
							    <?php echo esc_html__( 'Shipping class', 'ship-discounts' ); ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                    'desc_tip' => true,
                                    'desc'     => esc_html__('If specified, the box will only be used if all items in the order belong to the chosen class.', 'ship-discounts')]
                                )['tooltip_html']); ?>
							</th>
						</tr>
					</thead>
					<tfoot>
						<tr>
							<th colspan="13">
								<a href="#" class="button lar_boxes_insert"><?php echo esc_html__( 'Add box', 'ship-discounts' ); ?></a>
								<a href="#" class="button lar_boxes_remove"><?php echo esc_html__( 'Remove selected box(es)', 'ship-discounts' ); ?></a>
							</th>
						</tr>
					</tfoot>
					<tbody>
						<?php
						    $shipping_classes = get_terms(array('taxonomy' => 'product_shipping_class', 'hide_empty' => false));

							if ($boxes) {
								foreach ($boxes as $key => $box) {

                                    $options = '<option selected value="">N/A</option>';
                                    foreach ($shipping_classes as $shipping_class) {
                                        $selected = $shipping_class->term_id == $box->class ? 'selected' : '';
                                        $options .= '<option '.$selected.' value="'.esc_attr($shipping_class->term_id).'">'.esc_html($shipping_class->name).'</option>';
                                    }
									?>
									<tr>
									    <td class="sort">&#9776;</td>
										<td class="check-column"><span><input type="checkbox" /></span></td>
										<td><span><input type="text" name="sd_lar_boxes_name[<?php echo esc_attr($key); ?>]" value="<?php echo isset( $box->name ) ? esc_attr( $box->name ) : ''; ?>" /></span></td>
										<td><span><input type="text" name="sd_lar_boxes_outer_length[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->outer_length ); ?>" />in</span></td>
										<td><span><input type="text" name="sd_lar_boxes_outer_width[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->outer_width ); ?>" />in</span></td>
										<td><span><input type="text" name="sd_lar_boxes_outer_height[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->outer_height ); ?>" />in</span></td>
										<td><span><input type="text" name="sd_lar_boxes_inner_length[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->length ); ?>" />in</span></td>
										<td><span><input type="text" name="sd_lar_boxes_inner_width[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->width ); ?>" />in</span></td>
										<td><span><input type="text" name="sd_lar_boxes_inner_height[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->height ); ?>" />in</span></td>
										<td><span><input type="text" name="sd_lar_boxes_box_weight[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->weight ); ?>" />lbs</span></td>
										<td><span><input type="text" name="sd_lar_boxes_max_weight[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->max_weight ); ?>" />lbs</span></td>
										<td><span><input type="text" name="sd_lar_boxes_price[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr( $box->price ); ?>" /></span></td>
										<td><span><select name="sd_lar_boxes_class[<?php echo esc_attr($key); ?>]"><?php echo $options // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $options already escaped. ?></select></span></td>
									</tr>
									<?php
								}
							}
						?>
					</tbody>
				</table>
            <?php
            do_action('woocommerce_settings_lar_boxes_settings_end');
            echo '</table>';
            do_action('woocommerce_settings_lar_boxes_settings_after');
            ?>
            <?php
            return ob_get_clean();
        }

        /**
         * Return the products for which weight, dimensions and SKU are missing.
         */
        private function sd_lar_get_products_missing() {
            ob_start();
            $args = array(
                    'status' => 'publish',
                    'visibility' => 'visible',
                    'limit' => -1
            );
            $products = wc_get_products($args);
            $missings = [];

            foreach ($products as $product) {
                // Only products that need shipping
                if (!$product->needs_shipping())
                    continue;

                $variations = $product->get_children();
                // Check variations
                if ($variations) {
                    foreach ($variations as $variation) {
                        $p = wc_get_product($variation);
                        if ($p) {
                            $missing = Ship_Discounts_LAR_Plugin::sd_lar_check_product_missing($p);
                            if ($missing) $missings[] = $missing;
                        }
                    }
                }
                // Check simple products
                else {
                    $missing = Ship_Discounts_LAR_Plugin::sd_lar_check_product_missing($product);
                    if ($missing) $missings[] = $missing;
                }
            }
            unset($products);

            if ($missings) {
                ?>
                <div id="lar_products_missing">
                    <h4 class="lar_danger_message">
                        <?php echo esc_html__('Please note ! The following products do not have a valid weight, length, height, width and/or SKU:', 'ship-discounts') ?>
                        <?php
                        echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                    'desc_tip' => true,
                                    'desc'     => esc_html__('The weight must be between 0.1 and 150 pounds. The dimensions must be between 0.1 and 144 inches. Products too small and/or light can still be purchased if used with packages and/or boxes.', 'ship-discounts')]
                            )['tooltip_html']); ?>
                    </h4>
                    <table>
                        <div id="lar_products_missing_scroll">
                        <?php foreach ($missings as $missing) { ?>
                            <a href="/wp-admin/post.php?post=<?php echo esc_attr($missing['parent']) ?: esc_attr($missing['id']) ?>&action=edit"><?php echo esc_html($missing['name']) ?></a>
                            <span class="lar_danger_message">(<?php echo esc_html($missing['missing']) ?>)</span>
                            <br><br>
                        <?php } ?>
                        </div>
                    </table>
                </div>
            <?php
            }

            return ob_get_clean();
        }

        /**
         * Returns the current instance. If it does not exist, it is first created.
         * @return Ship_Discounts_Settings Current instance.
         */
        public static function getInstance(): Ship_Discounts_Settings {
            if (!self::$instance) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        /**
         * Adds the Ship Discounts settings page
         * @param $settings_tab mixed Settings tab.
         * @return mixed Settings tabs.
         */
        function sd_lar_add_settings_section($settings_tab) {
            $settings_tab['sd_lar_settings'] = esc_html__('Ship Discounts', 'ship-discounts');

            return $settings_tab;
        }

        /**
         * Sets the Ship Discounts settings page.
         */
        function sd_lar_set_settings_section() {
            global $current_section;

            if ('sd_lar_settings' === $current_section) {
                WC_Admin_Settings::output_fields($this->sd_lar_get_general_settings());
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables in HTML are escaped.
                echo $this->sd_lar_get_products_missing();
                WC_Admin_Settings::output_fields($this->sd_lar_get_classes_settings());
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables in HTML are escaped.
                echo $this->sd_lar_get_boxes_settings();
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables in HTML are escaped.
                echo $this->sd_lar_get_carriers_settings();

                $allowed_html = array(
	                'input' => array(
		                'id' => array(),
		                'type' => array(),
		                'name' => array(),
		                'value' => array(),
	                ),
                );
                echo wp_kses(wp_nonce_field('lar-form-nonce', 'lar-nonce', true, false), $allowed_html);
            }
        }

        /**
         * Saves the Ship Discounts settings.
         */
        function sd_lar_save_settings_section() {
            global $current_section;

            if ('sd_lar_settings' === $current_section && isset($_POST['lar-nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lar-nonce'])), 'lar-form-nonce')) {
                WC_Admin_Settings::save_fields($this->sd_lar_get_general_settings());
                WC_Admin_Settings::save_fields($this->sd_lar_get_classes_settings());

                // Boxes
                update_option('sd_lar_settings_use_boxes', (bool)$_POST['sd_lar_settings_use_boxes']);

                $boxes = [];
                $boxes_name         = wc_clean($_POST['sd_lar_boxes_name']) ?: array();
		        $boxes_outer_length = wc_clean($_POST['sd_lar_boxes_outer_length']) ?: array();
		        $boxes_outer_width  = wc_clean($_POST['sd_lar_boxes_outer_width']) ?: array();
		        $boxes_outer_height = wc_clean($_POST['sd_lar_boxes_outer_height']) ?: array();
		        $boxes_inner_length = wc_clean($_POST['sd_lar_boxes_inner_length']) ?: array();
		        $boxes_inner_width  = wc_clean($_POST['sd_lar_boxes_inner_width']) ?: array();
		        $boxes_inner_height = wc_clean($_POST['sd_lar_boxes_inner_height']) ?: array();
		        $boxes_box_weight   = wc_clean($_POST['sd_lar_boxes_box_weight']) ?: array();
		        $boxes_max_weight   = wc_clean($_POST['sd_lar_boxes_max_weight']) ?: array();
                $boxes_price        = wc_clean($_POST['sd_lar_boxes_price']) ?: array();
                $boxes_class        = wc_clean($_POST['sd_lar_boxes_class']) ?: array();

                $max = max(count($boxes_name), count($boxes_outer_length), count($boxes_outer_width),
                count($boxes_outer_height), count($boxes_inner_length), count($boxes_inner_width),
                count($boxes_inner_height), count($boxes_box_weight), count($boxes_max_weight), count($boxes_price));

                for ($i = 0; $i < $max; $i++) {
                    $boxes[] = new Ship_Discounts_Box(
                        $boxes_name[$i],
                        floatval($boxes_inner_length[$i]),
                        floatval($boxes_outer_length[$i]),
                        floatval($boxes_inner_width[$i]),
                        floatval($boxes_outer_width[$i]),
                        floatval($boxes_inner_height[$i]),
                        floatval($boxes_outer_height[$i]),
                        floatval($boxes_box_weight[$i]),
                        floatval($boxes_max_weight[$i]),
                        floatval($boxes_price[$i]),
                        $boxes_class[$i],
                    );
                }
                update_option('sd_lar_settings_boxes', wc_clean($boxes));

                // Carriers
                $carriers = wc_clean(Ship_Discounts_Carriers::getCarriers());
                if (is_array($_REQUEST['sd_lar_settings_carriers'])) {
                    if ($user_carriers = wc_clean($_REQUEST['sd_lar_settings_carriers'])) {
                        foreach ($carriers as $code => $data) {
                            foreach ($data['services'] as $serv_code => $service) {
                                $user_carriers[$code]['services'][$serv_code]['name'] = wc_clean($user_carriers[$code]['services'][$serv_code]['name']);
                                $user_carriers[$code]['services'][$serv_code]['enabled'] = (bool)$user_carriers[$code]['services'][$serv_code]['enabled'];

                                $factor = wc_clean($user_carriers[$code]['services'][$serv_code]['factor']);
                                $user_carriers[$code]['services'][$serv_code]['factor'] = is_numeric($factor) ? $factor : null;

                                $carriers[$code]['services'][$serv_code] = wc_clean($user_carriers[$code]['services'][$serv_code]);
                            }
                        }
                    }
                }
                update_option('sd_lar_settings_carriers_list', $carriers);
            }
        }
    }

    $Ship_Discounts_Settings = Ship_Discounts_Settings::getInstance();
}

if (!function_exists('sd_lar_ajax_get_shipping_classes')) {
    /**
     * Returns the shipping classes as options.
     * @return void
     */
    function sd_lar_ajax_get_shipping_classes() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lar-ajax-nonce') || !current_user_can('manage_options'))
            wp_send_json_error();

        $shipping_classes = get_terms(array('taxonomy' => 'product_shipping_class', 'hide_empty' => false));
        $options = '';
        foreach ($shipping_classes as $shipping_class) {
            $options .= '<option value="'.esc_attr($shipping_class->term_id).'">'.esc_html($shipping_class->name).'</option>';
        }
        wp_send_json_success($options);
    }
    add_action('wp_ajax_sd_lar_get_shipping_classes', 'sd_lar_ajax_get_shipping_classes');
}
