<?php
if (!defined('ABSPATH'))
    exit;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

add_action('add_meta_boxes', 'sd_lar_order_meta_box');

if (!function_exists('sd_lar_order_meta_box')) {
    /**
     * Adds a meta box displaying the order's services and quotes.
     * @return void
     */
    function sd_lar_order_meta_box() {
        try {
            if (class_exists('\Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController') &&
                wc_get_container()->get(CustomOrdersTableController::class)->custom_orders_table_usage_is_enabled())
                $screen = wc_get_page_screen_id('shop-order');
            else
                $screen = 'shop_order';
        } catch (\Throwable $e) {
            $screen = 'shop_order';
        }

        add_meta_box(
            'sd_lar_order_meta_box',
            esc_html__('Ship Discounts', 'ship-discounts'),
            'sd_lar_order_meta_box_callback',
            $screen, //'woocommerce_page_wc-orders',
            'side',
            'high'
        );
    }
}

if (!function_exists('sd_lar_order_meta_box_callback')) {
    /**
     * Callback function for the Ship Discounts meta box.
     * @return void
     */
    function sd_lar_order_meta_box_callback($post) {
        if (get_option('sd_lar_api_dev', "")) {
            echo '<p class="lar_danger_message"><strong>' . esc_html__('Be careful! Development mode is activated. No real orders will be created.', 'ship-discounts') . '</strong></p>';
        }

        $order = wc_get_order($post->ID);
        if ($order) {
            // TODO: Éventuellement retirer la condition 'lar_method' après 1.0.7
            if (!$order->has_shipping_method('sd_lar_method') && !$order->has_shipping_method('lar_method'))
                echo '<p>' . esc_html__('Note: the customer did not choose the Ship Discounts shipping method.', 'ship-discounts') . '</p>';

            if (!sd_lar_check_shop_info())
                /* translators: %s: WooCommerce general settings page */
                echo '<p class="lar_danger_message">' . wp_kses_post(sprintf(__('You must enter <a target="_blank" href="%s">your store\'s contact details</a> in order to create an order.', 'ship-discounts'), admin_url('admin.php?page=wc-settings&tab=general'))) . '</p>';
            else if (!$order->get_meta('_sd_lar_order_number'))
                echo '<p>' . esc_html__('The order has not been submitted yet.', 'ship-discounts') . '</p>';
            else {
                $order_number = $order->get_meta('_sd_lar_order_number');
                $carrier_code = $order->get_meta('_sd_lar_carrier_code');
                $service_code = $order->get_meta('_sd_lar_service_code');
                $cost = $order->get_meta('_sd_lar_service_cost');
                $display_cost = $order->get_meta('_sd_lar_service_display_cost') !== null && $order->get_meta('_sd_lar_service_display_cost') !== '' ? $order->get_meta('_sd_lar_service_display_cost') : $cost;
                $signature = $order->get_meta('_sd_lar_signature') ? esc_html__('The signature is required.', 'ship-discounts') : '';
                $ncv = $order->get_meta('_sd_lar_ncv') ? esc_html__('There is a non-conveyable object.', 'ship-discounts') : '';

                echo '<p>' . esc_html__('Order number', 'ship-discounts') . ': <b>' . esc_html($order_number) . '</b>';
                echo '<br>' . esc_html__('Carrier code', 'ship-discounts') . ': <b>' . esc_html($carrier_code) . '</b>';
                echo '<br>' . esc_html__('Service code', 'ship-discounts') . ': <b>' . esc_html($service_code) . '</b>';
                echo '<br>' . esc_html__('Quoted rate', 'ship-discounts') . ': <b>' . wp_kses_post(wc_price($cost)) . '</b>';
                echo '<br>' . esc_html__('Shown rate', 'ship-discounts') . ': <b>' . wp_kses_post(wc_price($display_cost)) . '</b></p>';

                if ($signature || $ncv) {
                    echo '<p>';
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $signature is already escaped.
                    echo $signature ? $signature . '<br>' : '';
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $ncv is already escaped.
                    echo $ncv;
                    echo '</p>';
                }

                echo '<p>';
                // Tracking & Status
                $details = wc_clean(Ship_Discounts_Carriers::getOrder($order_number, $order));
                if ($details) {
                    if ($order->get_meta('_sd_lar_tracking_nb') !== $details['tracking'])
                        $order->update_meta_data('_sd_lar_tracking_nb', $details['tracking']);
                    if ($order->get_meta('_sd_lar_tracking_url') !== $details['trackingUrl'])
                        $order->update_meta_data('_sd_lar_tracking_url', $details['trackingUrl']);
                    $order->save();
                }
                $tracking = $order->get_meta('_sd_lar_tracking_nb');
                $tracking_url = $order->get_meta('_sd_lar_tracking_url');

                if (!$details)
                    echo esc_html__('Sorry, we can not fetch the order\'s current status right now.', 'ship-discounts') . '<br>';
                else if ($details['CarrierDeliveryStatus']) {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Returned value is already escaped.
                    echo esc_html__('Delivery status:', 'ship-discounts') . ' <b>' . Ship_Discounts_Carriers::getDeliveryStatus()[$details['CarrierDeliveryStatus']] . '</b><br>';
                }

                if ($tracking) {
                    echo esc_html__('Tracking:', 'ship-discounts') . ' <b><a href="' . esc_url($tracking_url) . '" target="_blank">' . esc_html($tracking) . '</a></b><br>';
                }

                // Label
                if (!$order->get_meta('_sd_lar_label')) {
                    $label = wc_clean(Ship_Discounts_Carriers::getLabel($order_number, $order));
                    $order->update_meta_data('_sd_lar_label', $label === false ? false : $label[0]);
                    $order->save();
                }
                $label = $order->get_meta('_sd_lar_label');

                if ($label === false) {
                    echo esc_html__('Sorry, we can not fetch the order\'s label right now.', 'ship-discounts') . '<br>';
                }
                else {
                    if ($label) {
                        echo '<b><a href="' . esc_url($label) . '" target="_blank">' . esc_html__('See the label', 'ship-discounts') . '</a></b>';
                    }
                    else
                        echo esc_html__('The label has not been created yet.', 'ship-discounts');
                }
                echo '</p>';
            }

            // Client URL
            echo '<p><b><a href="' . esc_url(get_option('sd_lar_client_url', SD_LAR_CLIENT_URL_PROD)) . '" target="_blank">' .
                esc_html__('Manage my orders (Customer Portal)', 'ship-discounts') . '</a></b></p>';

            // (Re)send order
            if ((!$order->get_meta('_sd_lar_order_number') || SD_LAR_ADVANCED_SETTINGS) && sd_lar_check_shop_info())
                sd_lar_send_order_modal($order);

            // Previous not deleted orders
            if (SD_LAR_ADVANCED_SETTINGS) {
                $old_orders = $order->get_meta('_sd_lar_old_orders');
                if ($old_orders) {
                    echo '<p class="lar_danger_message"><strong>' .
                        esc_html__('These previous orders have not been cancelled. You should remove them.', 'ship-discounts') .
                        '</strong><br>';
                    foreach ($old_orders as $old_order)
                        echo '#' . esc_html($old_order) . '<br>';
                    echo '</p>';

                    echo '<button id="btn-lar-cancel-orders" name="btn-lar-cancel-orders" class="button" type="button" value="cancel">' .
                        esc_html__('Cancel these orders', 'ship-discounts') .
                        '</button>';
                }
            }
        }
    }
}

if (!function_exists('sd_lar_send_order_modal')) {
    /**
     * Displays the HTML of the "Send to Ship Discounts" modal.
     * @param $order WC_Order|WC_Order_Refund Order.
     * @return void
     */
    function sd_lar_send_order_modal($order) {
        add_thickbox();
        ob_start(); ?>

        <br>
        <a href="#TB_inline?&width=600&height=550&inlineId=lar-send-order"
           title="<?php echo esc_html__('Send to Ship Discounts', 'ship-discounts') ?>"
           class="thickbox button">
            <?php echo esc_html__('Send to Ship Discounts', 'ship-discounts') ?>
        </a>

        <div id="lar-send-order" style="display:none;">
            <p class="send-order-desc">
                <?php echo esc_html__('You can (re)send an order for these products to this address. You can change the carrier, packaging, boxes cost, whether a signature is required and if there are one or more non-conveyable products. Please note that the total cost may change.', 'ship-discounts') ?>
            </p>

            <form id="lar-send-order-form">
                <table class="form-table">
                    <tbody>
                    <tr style="vertical-align:top">
                        <th scope="row" class="titledesc">
                            <label for="signature">
                                <?php echo esc_html__('Signature', 'ship-discounts') ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                        'desc_tip' => true,
                                        'desc'     => esc_html__('Whether or not the signature is required.', 'ship-discounts')]
                                )['tooltip_html']); ?>
                            </label>
                        </th>
                        <td class="forminp forminp-text">
                            <input name="signature" id="signature" type="checkbox"
                                   value="1" <?php echo $order->get_meta('_sd_lar_signature') ? 'checked' : '' ?>>
                        </td>
                    </tr>

                    <tr style="vertical-align:top">
                        <th scope="row" class="titledesc">
                            <label for="ncv">
                                <?php echo esc_html__('Non-conveyable', 'ship-discounts') ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                        'desc_tip' => true,
                                        'desc'     => esc_html__('Whether or not this contains a non-conveyable product.', 'ship-discounts')]
                                )['tooltip_html']); ?>
                            </label>
                        </th>
                        <td class="forminp forminp-text">
                            <input name="ncv" id="ncv" type="checkbox"
                                   value="1" <?php echo $order->get_meta('_sd_lar_ncv') ? 'checked' : '' ?>>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table>
                    <tbody>
                    <tr style="vertical-align:top">
                        <th scope="row" class="titledesc">
                            <label>
                                <?php echo esc_html__('Package dimensions and weights', 'ship-discounts') ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                        'desc_tip' => true,
                                        'desc'     => esc_html__('Dimensions and weights of the used packages.', 'ship-discounts')]
                                )['tooltip_html']); ?>
                            </label>
                        </th>
                    </tr>
                    <tr style="vertical-align:top">
                        <td>
                            <table class="lar_boxes_table widefat">
                                <thead>
                                <tr>
                                    <th><input type="checkbox"/></th>
                                    <th><?php echo esc_html__('Length', 'ship-discounts'); ?></th>
                                    <th><?php echo esc_html__('Width', 'ship-discounts'); ?></th>
                                    <th><?php echo esc_html__('Height', 'ship-discounts'); ?></th>
                                    <th><?php echo esc_html__('Weight', 'ship-discounts'); ?></th>
                                </tr>
                                </thead>
                                <tfoot>
                                <tr>
                                    <th colspan="13">
                                        <a href="#"
                                           class="button lar_boxes_insert"><?php echo esc_html__('Add box', 'ship-discounts'); ?></a>
                                        <a href="#"
                                           class="button lar_boxes_remove"><?php echo esc_html__('Remove selected box(es)', 'ship-discounts'); ?></a>
                                    </th>
                                </tr>
                                </tfoot>
                                <tbody>
                                <?php
                                $packages = $order->get_meta('_sd_lar_packages');
                                if ($packages) {
                                    foreach ($packages as $key => $box) { ?>
                                        <tr>
                                            <td class="check-column"><span><input type="checkbox"/></span></td>
                                            <td><span><input type="text"
                                                             name="packages_length[<?php echo esc_attr($key); ?>]"
                                                             value="<?php echo esc_attr($box['length']); ?>"/>in</span>
                                            </td>
                                            <td><span><input type="text"
                                                             name="packages_width[<?php echo esc_attr($key); ?>]"
                                                             value="<?php echo esc_attr($box['width']); ?>"/>in</span>
                                            </td>
                                            <td><span><input type="text"
                                                             name="packages_height[<?php echo esc_attr($key); ?>]"
                                                             value="<?php echo esc_attr($box['height']); ?>"/>in</span>
                                            </td>
                                            <td><span><input type="text"
                                                             name="packages_weight[<?php echo esc_attr($key); ?>]"
                                                             value="<?php echo esc_attr($box['weight']); ?>"/>lbs</span>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr style="vertical-align:top">
                        <th scope="row" class="titledesc">
                            <label for="cost-boxes">
                                <?php echo esc_html__('Extra cost of box(es)', 'ship-discounts') ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                        'desc_tip' => true,
                                        'desc'     => esc_html__('Additional cost for using these boxes. Will be added to shipping costs.', 'ship-discounts')]
                                )['tooltip_html']); ?>
                            </label>
                        </th>
                        <td class="forminp forminp-text">
                            <input name="cost-boxes" id="cost-boxes" type="text"
                                   value="<?php echo esc_attr($order->get_meta('_sd_lar_boxes_cost')) ?: 0 ?>">
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php $shipping_note = $order->meta_exists('_sd_lar_shipping_note') ? $order->get_meta('_sd_lar_shipping_note') : $order->get_customer_note(); ?>
                <table class="form-table">
                    <tbody>
                    <tr style="vertical-align:top">
                        <th scope="row" class="titledesc">
                            <label for="shipping-note">
                                <?php echo esc_html__('Shipping note', 'ship-discounts') ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                        'desc_tip' => true,
                                        'desc'     => esc_html__('Note to the carrier, printed on the label. Maximum 30 characters.', 'ship-discounts')]
                                )['tooltip_html']); ?>
                            </label>
                        </th>
                        <td class="forminp forminp-textarea">
                            <textarea name="shipping-note" id="shipping-note"><?php echo esc_html($shipping_note); ?></textarea>
                            <span id="shipping-note-counter">
                                <?php echo esc_html__('Characters count', 'ship-discounts') . ' : ' ?>
                                <span style="color: <?php echo strlen($shipping_note) > 30 ? 'red' : 'green' ?>">
                                    <?php echo strlen($shipping_note) ?>
                                </span> / 30
                            </span>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <table class="form-table">
                    <tbody>
                    <tr style="vertical-align:top">
                        <th scope="row" class="titledesc">
                            <label>
                                <?php echo esc_html__('Carriers', 'ship-discounts') ?>
                                <?php
                                echo wp_kses_post(WC_Admin_Settings::get_field_description([
                                        'desc_tip' => true,
                                        'desc'     => esc_html__('Chosen carrier.', 'ship-discounts')]
                                )['tooltip_html']); ?>
                            </label>
                        </th>
                        <td class="forminp forminp-text">
                            <button id="btn-lar-get-quotes" name="btn-lar-get-quotes"
                                    class="button" type="button" value="quotes">
                                <?php echo esc_html__('Get quotes', 'ship-discounts') ?>
                            </button>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php
                $carrier_data = $order->get_meta('sd_lar_carrier_data');

                $carrier_code = $order->get_meta('_sd_lar_carrier_code');
                $service_code = $order->get_meta('_sd_lar_service_code');
                $cost = $order->get_meta('_sd_lar_service_cost');
                $display_cost = $order->get_meta('_sd_lar_service_display_cost') !== null && $order->get_meta('_sd_lar_service_display_cost') !== '' ? $order->get_meta('_sd_lar_service_display_cost') : $cost;

                if ($carrier_data) {
                    if (!$carrier_code) $carrier_code = $carrier_data['carrierCode'];
                    if (!$service_code) $service_code = $carrier_data['services']['serviceCode'];
                    if (!$cost) $cost = $carrier_data['services']['cost'];
                    if ($display_cost == null) $display_cost = $carrier_data['services']['displayCost'] !== null && $carrier_data['services']['displayCost'] !== '' ? $carrier_data['services']['displayCost'] : $cost;
                }
                ?>

                <div class="carriers-list">
                    <div class="carriers-list-header">
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span><?php echo esc_html__('Carrier code', 'ship-discounts') ?></span>
                        <span><?php echo esc_html__('Service code', 'ship-discounts') ?></span>
                        <span><?php echo esc_html__('Quoted rate', 'ship-discounts') ?></span>
                        <span><?php echo esc_html__('Shown rate', 'ship-discounts') ?></span>
                    </div>
                    <div class="carriers-list-carriers">
                        <?php if ($carrier_code) { ?>
                            <div class="carriers-list-carrier default-carrier">
                                <input type="radio" id="default" name="carrier" value="default" checked>
                                <label for="default">
                                    <span><?php echo esc_html($carrier_code) ?></span>
                                    <span><?php echo esc_html($service_code) ?></span>
                                    <span><?php echo wp_kses_post(wc_price($cost)) ?></span>
                                    <span><?php echo wp_kses_post(wc_price($display_cost)) ?></span>
                                </label>
                            </div>
                        <?php } ?>
                    </div>
                </div>

                <p id="lar-error-msg" class="lar_danger_message" style="display:none"></p>

                <p class="submit">
                    <button id="btn-lar-send-order" name="btn-lar-send-order"
                            class="button-primary" type="submit" value="submit">
                        <?php echo esc_html__('Send to Ship Discounts', 'ship-discounts') ?>
                    </button>
                </p>
            </form>
        </div>

        <?php
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Variables in HTML are escaped.
        echo ob_get_clean();
    }
}

if (!function_exists('sd_lar_check_shop_info')) {
    /**
     * Checks if the shop has all the information needed for the order creation.
     * @return bool
     */
    function sd_lar_check_shop_info() {
        return get_bloginfo('name') && WC()->countries->get_base_address() &&
            str_replace(' ', '', WC()->countries->get_base_postcode()) &&
            get_option('sd_lar_settings_phone_number', '') && WC()->countries->get_base_city();
    }
}

if (!function_exists('sd_lar_ajax_get_carriers_quotes')) {
    /**
     * Returns the carriers' quotes.
     * @return void
     */
    function sd_lar_ajax_get_carriers_quotes() {
        if (isset($_POST['order']) && isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lar-ajax-nonce') &&
            is_numeric($_POST['order']) && current_user_can('edit_posts')) {
            $order = wc_get_order(intval($_POST['order']));

            if ($order) {
                $signature = isset($_POST['signature']) && $_POST['signature'];
                $ncv = isset($_POST['ncv']) && $_POST['ncv'];
                $postcode = $order->get_shipping_postcode() ?: '';

                $shipping_methods = $order->get_shipping_methods();
                if ($shipping_methods) {
                    $shipping_method = reset($shipping_methods);
                    $shipping_class_names = WC()->shipping->get_shipping_method_class_names();
                    if (array_key_exists($shipping_method->get_method_id(), $shipping_class_names))
                        $method = new $shipping_class_names[$shipping_method->get_method_id()]($shipping_method->get_instance_id());
                }

                if (!isset($method)) {
                    $method = new WC_Shipping_Ship_Discounts();
                }

                $packages = [];
                if (isset($_POST['packages']) && is_array($_POST['packages'])) $packages = wc_clean($_POST['packages']);

                $free_shipping = apply_filters('sd_lar_free_shipping_value', get_option('sd_lar_settings_free_shipping_value', ''), $order->get_user(), $order->get_items());
                $free_shipping = is_numeric($free_shipping) && floatval(($order->get_subtotal())) >= floatval($free_shipping);

                $carriers = wc_clean(Ship_Discounts_Carriers::getQuotes($method, $postcode, $packages, $signature, $ncv, false, false, $free_shipping, $order->get_user(), $order->get_items()));

                // Format price
                if (is_array($carriers)) {
                    foreach ($carriers as $c_code => $carrier) {
                        foreach ($carrier['services'] as $code => $service) {
                            $carriers[$c_code]['services'][$code]['cost'] = wc_price($service['cost']);
                            if (isset($service['displayCost']) && $service['displayCost'] !== '')
                                $carriers[$c_code]['services'][$code]['displayCost'] = wc_price($service['displayCost']);
                            else
                                $carriers[$c_code]['services'][$code]['displayCost'] = $carriers[$c_code]['services'][$code]['cost'];
                        }
                    }
                }

                die(wp_json_encode($carriers));
            }
        }
        die(wp_json_encode(false));
    }

    add_action('wp_ajax_sd_lar_get_carriers_quotes', 'sd_lar_ajax_get_carriers_quotes');
}

if (!function_exists('sd_lar_ajax_resend_order')) {
    /**
     * Update and resend the order.
     * @return void
     */
    function sd_lar_ajax_resend_order() {
        if (isset($_POST['order']) && isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lar-ajax-nonce') &&
            is_numeric($_POST['order']) && current_user_can('edit_posts')) {
            $order = wc_get_order(intval($_POST['order']));

            if ($order) {
                $signature = isset($_POST['signature']) && $_POST['signature'];
                $ncv = isset($_POST['ncv']) && $_POST['ncv'];
                $cost_boxes = isset($_POST['cost_boxes']) ? floatval($_POST['cost_boxes']) : 0;
                $shipping_note = isset($_POST['shipping_note']) ? stripslashes(strval($_POST['shipping_note'])) : '';

                $packages = [];
                if (isset($_POST['packages']) && is_array($_POST['packages'])) $packages = wc_clean($_POST['packages']);

                if (!$packages)
                    die(wp_json_encode('NO_PACKAGES'));

                foreach ($packages as $package) {
                    if ($package['length'] < 0.1 || $package['length'] > 144 ||
                        $package['width'] < 0.1  || $package['width'] > 144  ||
                        $package['height'] < 0.1 || $package['height'] > 144 ||
                        $package['weight'] < 0.1 || $package['weight'] > 150)
                        die(wp_json_encode('PACKAGES'));
                }

                $carrier_code = wc_clean($_POST['carrier_code']) ?? '';
                $service_code = wc_clean($_POST['service_code']) ?? '';
                $cost = floatval($_POST['cost']) ?? 0;
                $display_cost = floatval($_POST['display_cost']) ?? 0;

                $shipping_methods = $order->get_shipping_methods();
                if ($shipping_methods) {
                    $shipping_method = reset($shipping_methods);
                    $shipping_class_names = WC()->shipping->get_shipping_method_class_names();
                    if (array_key_exists($shipping_method->get_method_id(), $shipping_class_names))
                        $method = new $shipping_class_names[$shipping_method->get_method_id()]($shipping_method->get_instance_id());
                }

                if (!isset($method)) {
                    $method = new WC_Shipping_Ship_Discounts();
                }

                $carrier = [];
                if ($carrier_code === 'default' || $service_code === 'default') {
                    $carrier = $order->get_meta('sd_lar_carrier_data');
                    $display_cost = $carrier['services']['displayCost'] !== null && $carrier['services']['displayCost'] !== '' ? $carrier['services']['displayCost'] : $carrier['services']['cost'];
                }
                else {
                    $carrier['insurance'] = $method->shipment_value;
                    $carrier['carrierCode'] = $carrier_code;
                    $carrier['carrierName'] = $method->carriers_list[$carrier_code]['name'];
                    $carrier['services']['serviceCode'] = $service_code;
                    $carrier['services']['cost'] = $cost;
                    $carrier['services']['displayCost'] = $display_cost;
                }

                // Delete previous order
                $ok_create = true;
                $old_code = $order->get_meta('_sd_lar_order_number');
                if ($old_code) {
                    $deleted = Ship_Discounts_Carriers::deleteOrder($old_code, $order);
                    if ($deleted === false)
                        $ok_create = false;

                    // TODO: Gérer les cas d'erreur
                }

                if ($ok_create) {
                    $code = wc_clean(Ship_Discounts_Carriers::submitOrder($order, $carrier, $packages, $signature, $ncv, $shipping_note));
                    if ($code !== false && is_numeric($code)) {
                        // Update metadata
                        $order->update_meta_data('sd_lar_carrier_data', $carrier);
                        $order->update_meta_data('_sd_lar_signature', $signature);
                        $order->update_meta_data('_sd_lar_ncv', $ncv);
                        $order->update_meta_data('_sd_lar_packages', $packages);
                        $order->update_meta_data('_sd_lar_boxes_cost', $cost_boxes);
                        $order->update_meta_data('_sd_lar_shipping_note', $shipping_note);

                        $order->update_meta_data('_sd_lar_order_number', $code);
                        $order->update_meta_data('_sd_lar_carrier_code', $carrier['carrierCode']);
                        $order->update_meta_data('_sd_lar_service_code', $carrier['services']['serviceCode']);
                        $order->update_meta_data('_sd_lar_service_cost', $carrier['services']['cost']);
                        if ($carrier['services']['displayCost'] !== null && $carrier['services']['displayCost'] !== '')
                            $order->update_meta_data('_sd_lar_service_display_cost', $carrier['services']['displayCost']);

                        $label = wc_clean(Ship_Discounts_Carriers::getLabel($code, $order));
                        if ($label)
                            $order->update_meta_data('_sd_lar_label', $label[0]);

                        $details = wc_clean(Ship_Discounts_Carriers::getOrder($code, $order));
                        if ($details) {
                            $order->update_meta_data('_sd_lar_tracking_url', $details['trackingUrl']);
                            $order->update_meta_data('_sd_lar_tracking_nb', $details['tracking']);
                        }

                        // Update shipping cost
                        $free_shipping = apply_filters('sd_lar_free_shipping_value', get_option('sd_lar_settings_free_shipping_value', ''), $order->get_user(), $order->get_items());
                        $free_shipping = is_numeric($free_shipping) && floatval(($order->get_subtotal())) >= floatval($free_shipping);

                        if (!$order->get_items('shipping')) {
                            $item = new WC_Order_Item_Shipping();
                            $item->set_method_title(esc_html__('Ship Discounts', 'ship-discounts'));
                            $item->set_method_id('sd_lar_method');
                            $order->add_item($item);
                            $order->save();
                        }

                        foreach ($order->get_items('shipping') as $item) {
                            if ($free_shipping) $item->set_total(0);
                            else {
                                $method_cost = $method->cost ?: 0;
                                $item->set_total(floatval(apply_filters('sd_lar_method_cost_value', $method_cost, $order->get_user(), $order->get_items())) + $display_cost + $cost_boxes);
                            }
                            $item->calculate_taxes();
                            $item->save();
                        }

                        // Update total and status
                        $order->calculate_shipping();
                        $order->calculate_totals();
                        $order->set_status('processing');
                        $order->save();

                        die(wp_json_encode(true));
                    }
                    // Erreurs
                    else {
                        if ($code === 'PHONE-deliverTo')
                            die(wp_json_encode('PHONE-deliverTo'));
                        if ($code === 'PHONE-soldTo')
                            die(wp_json_encode('PHONE-soldTo'));
                        if ($code === 'PHONE-shipfrom')
                            die(wp_json_encode('PHONE-shipfrom'));
                        if ($code === 'MISSING')
                            die(wp_json_encode('MISSING'));
                    }
                }
                else {
                    die(wp_json_encode('DELETE'));
                }
            }
        }
        die(wp_json_encode(false));
    }

    add_action('wp_ajax_sd_lar_resend_order', 'sd_lar_ajax_resend_order');
}

if (!function_exists('sd_lar_ajax_cancel_orders')) {
    /**
     * Returns the carriers' quotes.
     * @return void
     */
    function sd_lar_ajax_cancel_orders() {
        if (isset($_POST['order']) && isset($_POST['nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'lar-ajax-nonce-del') &&
            is_numeric($_POST['order']) && current_user_can('edit_posts')) {
            $order = wc_get_order(intval($_POST['order']));

            if ($order) {
                $old_orders = $order->get_meta('_sd_lar_old_orders');
                $failed = [];

                if ($old_orders) {
                    foreach ($old_orders as $old_order) {
                        $deleted = Ship_Discounts_Carriers::deleteOrder($old_order, $order);
                        if ($deleted === false)
                            $failed[] = $old_order;

                        // TODO: Gérer les cas d'erreur
                    }
                }

                $order->update_meta_data('_sd_lar_old_orders', $failed);
                $order->save();

                die(wp_json_encode($failed));
            }
        }
        die(wp_json_encode(false));
    }

    add_action('wp_ajax_sd_lar_cancel_orders', 'sd_lar_ajax_cancel_orders');
}