<?php
if (!defined('ABSPATH'))
    exit;

if (!function_exists('sd_lar_hide_based_on_shipping_class')) {
    /**
     * Hide the Ship Discounts shipping method based on the user's choices.
     * @param $rates mixed Rates.
     * @param $package mixed Package.
     * @return mixed|void Rates or void.
     */
    function sd_lar_hide_based_on_shipping_class($rates, $package) {
        if (is_admin() && !defined('DOING_AJAX'))
            return;

        $shipping_zone = WC_Shipping_Zones::get_zone_matching_package($package);
        $shipping_methods = $shipping_zone->get_shipping_methods(true, 'values');
        $lar_methods = [];

        foreach ($shipping_methods as $instance_id => $shipping_method) {
            if ($rates['sd_lar_method:' . $instance_id])
                $lar_methods['sd_lar_method:' . $instance_id] = $shipping_method;
        }

        $cart_items = WC()->cart->get_cart();
        if ($cart_items) {
            foreach ($lar_methods as $method_id => $method) {
                $type = $method->get_instance_option('class_list_type');
                $keep = null;
                $ids = [];

                if ($type == 1) {
                    $keep = true;
                    $ids = $method->get_instance_option('class_list_allow');
                }
                else if ($type == 2) {
                    $keep = false;
                    $ids = $method->get_instance_option('class_list_deny');
                }

                if ($keep !== null) {
                    foreach ($cart_items as $cart_item) {
                        $unset = $keep ? array_search($cart_item['data']->get_shipping_class_id(), $ids) === false :
                            array_search($cart_item['data']->get_shipping_class_id(), $ids) !== false;

                        if ($unset) {
                            unset($rates[$method_id]);
                            break;
                        }
                    }
                }
            }
        }

        // Get the chosen carrier's quote
        $carriers = WC()->session->get('sd_lar_carriers_list') ?: [];
        $chosen = WC()->session->get('chosen_sd_lar_carrier');
        if ($chosen) $chosen = explode('%', $chosen);
        $cost = null;

        if ($carriers && $chosen) {
            if (array_key_exists($chosen[1], $carriers[$chosen[0]]['services'])) {
                $cost = $carriers[$chosen[0]]['services'][$chosen[1]]['displayCost'] !== null && $carriers[$chosen[0]]['services'][$chosen[1]]['displayCost'] !== '' ?
                    $carriers[$chosen[0]]['services'][$chosen[1]]['displayCost'] :
                    $carriers[$chosen[0]]['services'][$chosen[1]]['cost'];
            }
        }
        else {
            $chosen[0] = $chosen[1] = null;
        }

        // Calculate the new shipping cost
        // Free shipping
        $free_shipping = apply_filters('sd_lar_free_shipping_value', get_option('sd_lar_settings_free_shipping_value', ''), wp_get_current_user(), Ship_Discounts_LAR_Plugin::sd_lar_cart_items_to_item_products(WC()->cart->get_cart()));
        $free_shipping = is_numeric($free_shipping) && floatval(WC()->cart->get_subtotal()) >= floatval($free_shipping);

        if ($free_shipping) {
            foreach ($rates as $rate_key => $rate) {
                if ($rate->method_id == 'sd_lar_method') {
                    // Shipping cost
                    $rates[$rate_key]->cost = 0;
                    // Taxes
                    $rates[$rate_key]->taxes = [];
                }
            }
        }
        // No free shipping
        else {
            $cost = $cost ?: 0;
            $boxes = WC()->session->get('sd_lar_boxes_cost') ?: 0;
            $new_cost = $cost + $boxes;

            foreach ($rates as $rate_key => $rate) {
                if ($rate->method_id == 'sd_lar_method') {
                    // Shipping cost
                    $default_cost = $rate->cost;
                    $rates[$rate_key]->cost = floatval(apply_filters('sd_lar_method_cost_value', $default_cost, wp_get_current_user(), Ship_Discounts_LAR_Plugin::sd_lar_cart_items_to_item_products(WC()->cart->get_cart()))) + $new_cost;
                    if ($default_cost > 0)
                        $rate_conversion = $rates[$rate_key]->cost / $default_cost;
                    else
                        $rate_conversion = 1;

                    // Taxes
                    $taxes = [];
                    $has_taxes = false;
                    $shipping_taxes = $rate->taxes;
                    $shipping_taxes = $shipping_taxes ?: WC_Tax::calc_shipping_tax(floatval($rates[$rate_key]->cost), WC_Tax::get_shipping_tax_rates());
                    if ($shipping_taxes) {
                        foreach ($shipping_taxes as $key => $tax) {
                            if ($tax > 0) {
                                $taxes[$key] = $tax * $rate_conversion;
                                $has_taxes = true;
                            }
                        }
                    }
                    if ($has_taxes) $rates[$rate_key]->taxes = $taxes;
                }
            }
        }

        return $rates;
    }

    add_filter('woocommerce_package_rates', 'sd_lar_hide_based_on_shipping_class', 10, 2);
}

if (!function_exists('sd_lar_checkout_shipping_form_carriers')) {
    /**
     * Shows the enabled carriers' delays and rates for the Ship Discounts shipping method in cart and checkout.
     * @return void
     */
    function sd_lar_checkout_shipping_form_carriers() {
        ob_start();
        WC()->session->set('sd_lar_method', null);

        // Do action only if there are items in cart
        $shipping_packages = WC()->cart->get_shipping_packages();
        if ($shipping_packages) {

            // Do action only if a Ship Discounts method is chosen
            $chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');
            foreach ($chosen_shipping_methods as $shipping_id) {

                // Get the WC_Shipping_Ship_Discounts objects
                $shipping_zone = wc_get_shipping_zone(reset($shipping_packages));
                $shipping_methods = $shipping_zone->get_shipping_methods(true, 'values');
                $lar_methods = [];
                foreach ($shipping_methods as $instance_id => $shipping_method) {
                    if ($shipping_method->id == 'sd_lar_method')
                        $lar_methods['sd_lar_method:' . $instance_id] = $shipping_method;
                }
                unset($shipping_zone, $shipping_methods);

                if (array_key_exists($shipping_id, $lar_methods)) {
                    WC()->session->set('sd_lar_method', $shipping_id);

                    // Force "woocommerce_package_rates" for rates calculation
                    WC_Cache_Helper::get_transient_version('shipping', true);

                    // Get the postal code
                    $postal_code = $shipping_packages[0]['destination']['postcode'];

                    // Get the packages' dimensions and weight and check if SKU exists
                    $packages = [];
                    $dim = false;
                    $sku = false;
                    foreach ($shipping_packages[0]['contents'] as $values) {
                        $qty = intval($values['quantity']);
                        while ($qty) {
                            $sku = $values["data"]->get_sku();
                            if (!$sku) break 2;

                            // Check dimensions only if no packages and boxes
                            if ($lar_methods[$shipping_id]->predefined_package != 'yes' &&
                                !get_option('sd_lar_settings_use_boxes', false)) {
                                $dim = Ship_Discounts_LAR_Plugin::sd_lar_check_product_missing($values["data"]);
                                if ($dim) break 2;
                            }

                            $packages[] = array(
                                "width"  => $values["data"]->get_width(),
                                "length" => $values["data"]->get_length(),
                                "height" => $values["data"]->get_height(),
                                "weight" => $values["data"]->get_weight(),
                                "class"  => $values["data"]->get_shipping_class_id(),
                            );
                            $qty = $qty - 1;
                        }
                    }

                    $free_shipping = false;
                    $cheapest = [];

                    $signature = get_option('sd_lar_settings_signature', 'yes') !== 'no';
                    if (!$signature) WC()->session->set('sd_lar_signature', null);

                    if (!$sku)
                        $carriers = 'SKU';
                    else if ($dim)
                        $carriers = 'DIMENSIONS';
                    else {
                        $free_shipping = apply_filters('sd_lar_free_shipping_value', get_option('sd_lar_settings_free_shipping_value', ''), wp_get_current_user(), Ship_Discounts_LAR_Plugin::sd_lar_cart_items_to_item_products(WC()->cart->get_cart()));
                        $free_shipping = is_numeric($free_shipping) && floatval(WC()->cart->get_subtotal()) >= floatval($free_shipping);

                        wc_clean($carriers = Ship_Discounts_Carriers::getQuotes($lar_methods[$shipping_id], $postal_code, $packages, WC()->session->get('sd_lar_signature'), WC()->session->get('sd_lar_ncv'), true, true, $free_shipping, wp_get_current_user(), Ship_Discounts_LAR_Plugin::sd_lar_cart_items_to_item_products(WC()->cart->get_cart())));
                    }

                    if (is_array($carriers) && count($carriers) > 0) {
                        WC()->session->set('sd_lar_carriers_list', $carriers);

                        $chosen = WC()->session->get('chosen_sd_lar_carrier');
                        $chosen = empty($chosen) ? WC()->checkout->get_value('sd_lar_carrier') : $chosen;
                        $chosen = empty($chosen) ? '__FIRST__' : $chosen;

                        $chosen_carrier = $chosen != '__FIRST__' ? explode('%', $chosen) : '';
                        $chosen_carrier = $chosen_carrier ? $chosen_carrier[1] : '';

                        if ($chosen !== '__FIRST__' && !Ship_Discounts_LAR_Plugin::multiArrayKeyExists($carriers, $chosen_carrier)) {
                            WC()->session->set('chosen_sd_lar_carrier', null);
                            $chosen = '__FIRST__';
                        }

                        if ($free_shipping) {
                            $cheapest = wc_clean(Ship_Discounts_Carriers::findCheapestCarrier($carriers));
                            if ($carriers) {
                                $chosen = $cheapest['carrier'] . '%' . $cheapest['service'];
                                WC()->session->set('chosen_sd_lar_carrier', $chosen);
                            }
                        }

                        if ($signature) {
                        ?>
                        <tr id="lar_cart_checkout_signature">
                            <th></th>
                            <td>
                                <input type="checkbox" value="1" id="lar_signature"
                                    <?php echo WC()->session->get('sd_lar_signature') ? 'checked' : '' ?>>
                                <label for="lar_signature"><?php echo esc_html__('Signature is required', 'ship-discounts') ?></label>
                            </td>
                        </tr>
                        <?php } ?>

                        <tr id="lar_cart_checkout_carriers">
                            <th><?php echo esc_html($lar_methods[$shipping_id]->title) . ' ' . esc_html__('Carriers', 'ship-discounts') ?></th>
                            <td>
                                <ul id="lar_cart_checkout_carriers_list">
                                    <?php
                                    foreach ($carriers as $c_code => $carrier) {
                                        if ($cheapest && $cheapest['carrier'] !== $c_code) continue;

                                        echo '<label class="lar_cart_checkout_carriers_label">' . esc_html($carrier['carrierName']) . '</label>';

                                        foreach ($carrier['services'] as $code => $service) {
                                            if ($cheapest && $cheapest['service'] !== $code) continue;
                                            ?>
                                            <li class="lar_cart_checkout_carriers_service">
                                                <input type="radio" name="lar_carrier"
                                                       value="<?php echo esc_attr($c_code . '%' . $code) ?>"
                                                       id="lar_carrier_<?php echo esc_attr($code) ?>"
                                                    <?php echo $chosen == '__FIRST__' || $chosen == $c_code . '%' . $code ? 'checked' : '' ?>>
                                                <label for="lar_carrier_<?php echo esc_attr($code) ?>">
                                                    <?php echo esc_html($service['serviceName']) ?>
                                                </label>

                                                <?php
                                                // TODO: Convertir dans la bonne devise
                                                echo '<p><span class="lar_cart_checkout_cost">' . esc_html__('Cost', 'ship-discounts') . ' : ';
                                                echo $service['displayCost'] !== null && $service['displayCost'] !== '' ? wp_kses_post(wc_price($service['displayCost'])) : wp_kses_post(wc_price($service['cost']));
                                                echo '</span>';

                                                $timestamp = strtotime($service['deliveryDate']);
                                                if ($timestamp) {
                                                    echo '<br><span class="lar_cart_checkout_date">' . esc_html__('Delivery date', 'ship-discounts') . ' : ';
                                                    echo esc_html(wp_date(get_option('date_format'), $timestamp));
                                                    echo '</span>';
                                                }
                                                echo '</p>';
                                                ?>
                                            </li>
                                            <?php

                                            $chosen = $chosen == '__FIRST__' ? $code : $chosen;
                                        }
                                    }
                                    ?>
                                </ul>
                            </td>
                        </tr>
                        <?php
                    }
                    else {
                        WC()->session->set('chosen_sd_lar_carrier', null);
                        WC()->session->set('sd_lar_carriers_list', null);
                        WC()->session->set('sd_lar_boxes_cost', null);
                        WC()->session->set('sd_lar_packages', null);
                        WC()->session->set('sd_lar_signature', null);
                        WC()->session->set('sd_lar_ncv', null);

                        $msg = esc_html__('Sorry, we could not fetch the carriers\' quotes. Please try again.', 'ship-discounts');

                        if (is_array($carriers) && count($carriers) == 0)
                            $msg = esc_html__('Sorry, there are no available carriers.', 'ship-discounts');

                        if ($carriers == 'POSTALCODE')
                            $msg = esc_html__('To view our carriers\' quotes, please insert a valid postal code.', 'ship-discounts');

                        if ($carriers == 'SKU' || $carriers == 'DIMENSIONS')
                            $msg = esc_html__('Sorry, one or more products do not meet Ship Discounts\' requirements.', 'ship-discounts');

                        ?>
                        <tr id="lar_cart_checkout_carriers">
                            <th><?php echo esc_html__('Ship Discounts Carriers', 'ship-discounts') ?></th>
                            <td><?php echo esc_html($msg) ?></td>
                        </tr>
                        <?php
                    }
                }
            }
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables in HTML are escaped.
        echo ob_get_clean();
    }

    add_action('woocommerce_cart_totals_after_shipping', 'sd_lar_checkout_shipping_form_carriers', 20);
    add_action('woocommerce_review_order_after_shipping', 'sd_lar_checkout_shipping_form_carriers', 20);
}

if (!function_exists('sd_lar_set_signature_ajax_data')) {
    /**
     * Sets the signature value.
     * @return void
     */
    function sd_lar_set_signature_ajax_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lar-ajax-nonce'))
            die();

        WC()->session->set('sd_lar_signature', wc_clean($_POST['checked']) === 'true');
        echo wp_json_encode(WC()->session->get('sd_lar_signature'));
        die();
    }

    add_action('wp_ajax_sd_lar_signature', 'sd_lar_set_signature_ajax_data');
    add_action('wp_ajax_nopriv_sd_lar_signature', 'sd_lar_set_signature_ajax_data');
}

if (!function_exists('sd_lar_set_carrier_ajax_data')) {
    /**
     * Saves the chosen Ship Discounts carrier in the WC session.
     * @return void
     */
    function sd_lar_set_carrier_ajax_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lar-ajax-nonce'))
            die();

        if (isset($_POST['sd_lar_carrier'])) {
            $option = wc_clean($_POST['sd_lar_carrier']);
            WC()->session->set('chosen_sd_lar_carrier', $option);
            echo wp_json_encode($option);
        }
        die();
    }

    add_action('wp_ajax_sd_lar_carrier', 'sd_lar_set_carrier_ajax_data');
    add_action('wp_ajax_nopriv_sd_lar_carrier', 'sd_lar_set_carrier_ajax_data');
}

if (!function_exists('sd_lar_get_cart_qty_ajax_data')) {
    /**
     * Get the current number of items in cart.
     * @return void
     */
    function sd_lar_get_cart_qty_ajax_data() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lar-ajax-nonce'))
            die();

        // Check if there is a non-conveyable product
        WC()->session->set('sd_lar_ncv', false);
        foreach (WC()->cart->get_cart() as $item) {
            $id = isset($item['variation_id']) && $item['variation_id'] ? $item['variation_id'] : $item['product_id'];
            if (get_post_meta($id, '_sd_lar_ncv', true)) {
                WC()->session->set('sd_lar_ncv', true);
                break;
            }
        }

        echo wp_json_encode(WC()->cart->get_cart_contents_count());
        die();
    }

    add_action('wp_ajax_sd_lar_cart_qty', 'sd_lar_get_cart_qty_ajax_data');
    add_action('wp_ajax_nopriv_sd_lar_cart_qty', 'sd_lar_get_cart_qty_ajax_data');
}

if (!function_exists('sd_lar_checkout_validation')) {
    /**
     * Stops the checkout process if the Ship Discounts method is used without a carrier.
     * @return void
     */
    function sd_lar_checkout_validation() {
        if ($lar_method = WC()->session->get('sd_lar_method')) {
            $methods = WC()->session->get('chosen_shipping_methods');
            foreach ($methods as $method) {
                if ($lar_method === $method && (!WC()->session->get('sd_lar_carriers_list') || !WC()->session->get('chosen_sd_lar_carrier'))) {
                    wc_add_notice('<b>' . esc_html__('Sorry, you can not use the Ship Discounts shipping method without a carrier.', 'ship-discounts') . '</b>', 'error');
                    break;
                }
            }
        }
    }

    add_action('woocommerce_checkout_process', 'sd_lar_checkout_validation');
}

if (!function_exists('sd_lar_checkout_update_order_meta')) {
    /**
     * Adds the chosen carrier, signature and non-conveyable as custom order metadata.
     * @param $order
     * @return void
     */
    function sd_lar_checkout_update_order_meta($order) {
        if ($order->has_shipping_method('sd_lar_method')) {
            // Get the chosen carrier's data
            $carriers = wc_clean(WC()->session->get('sd_lar_carriers_list')) ?: [];
            $chosen = wc_clean(WC()->session->get('chosen_sd_lar_carrier'));
            if ($chosen)
                $chosen = explode('%', $chosen);
            $data = null;

            if ($carriers && $chosen) {
                if (array_key_exists($chosen[1], $carriers[$chosen[0]]['services'])) {
                    $data = $carriers[$chosen[0]];
                    $data['carrierCode'] = $chosen[0];
                    $data['services'] = $carriers[$chosen[0]]['services'][$chosen[1]];
                    $data['services']['serviceCode'] = $chosen[1];
                    $data['services']['cost'] = $carriers[$chosen[0]]['services'][$chosen[1]]['cost'];
                    $data['services']['displayCost'] = $carriers[$chosen[0]]['services'][$chosen[1]]['displayCost'];
                }
            }

            if ($data) $order->update_meta_data('sd_lar_carrier_data', $data);

            // Get the signature
            $order->update_meta_data('_sd_lar_signature', (bool)WC()->session->get('sd_lar_signature'));

            // Get if there is a non-conveyable product
            $order->update_meta_data('_sd_lar_ncv', (bool)WC()->session->get('sd_lar_ncv'));

            // Get the packages
            $order->update_meta_data('_sd_lar_packages', wc_clean(WC()->session->get('sd_lar_packages')));

            // Get the boxes cost
            $order->update_meta_data('_sd_lar_boxes_cost', wc_clean(WC()->session->get('sd_lar_boxes_cost')));
        }
    }

    add_action('woocommerce_checkout_create_order', 'sd_lar_checkout_update_order_meta');
}

if (!function_exists('sd_lar_submit_and_update_order')) {
    /**
     * Submit an order if it has not been submitted yet and update its metadata.
     * @param $order_id
     * @return void
     */
    function sd_lar_submit_and_update_order($order_id) {
        $order = wc_get_order($order_id);

        if (SD_LAR_AUTOMATIC_ORDER && $order->has_shipping_method('sd_lar_method') && !$order->get_meta('_sd_lar_order_number')) {
            $carrier = $order->get_meta('sd_lar_carrier_data');
            $signature = $order->get_meta('_sd_lar_signature');
            $ncv = $order->get_meta('_sd_lar_ncv');
            $packages = $order->get_meta('_sd_lar_packages') ?: [];

            if ($carrier) {
                $code = Ship_Discounts_Carriers::submitOrder($order, $carrier, $packages, $signature, $ncv, $order->get_customer_note());

                if ($code !== false) {
                    $order->update_meta_data('_sd_lar_order_number', wc_clean($code));
                    $order->update_meta_data('_sd_lar_carrier_code', wc_clean($carrier['carrierCode']));
                    $order->update_meta_data('_sd_lar_service_code', wc_clean($carrier['services']['serviceCode']));
                    $order->update_meta_data('_sd_lar_service_cost', wc_clean($carrier['services']['cost']));
                    if ($carrier['services']['displayCost'] !== null && wc_clean($carrier['services']['displayCost'] !== ''))
                        $order->update_meta_data('_sd_lar_service_display_cost', wc_clean($carrier['services']['displayCost']));

                    $label = Ship_Discounts_Carriers::getLabel($code, $order);
                    if ($label)
                        $order->update_meta_data('_sd_lar_label', wc_clean($label[0]));

                    $details = Ship_Discounts_Carriers::getOrder($code, $order);
                    if ($details) {
                        $order->update_meta_data('_sd_lar_tracking_url', wc_clean($details['trackingUrl']));
                        $order->update_meta_data('_sd_lar_tracking_nb', wc_clean($details['tracking']));
                    }

                    $order->save();
                }
            }
        }
    }

    add_action('woocommerce_order_status_processing', 'sd_lar_submit_and_update_order');
    //add_action('woocommerce_order_status_completed', 'sd_lar_submit_and_update_order');
}