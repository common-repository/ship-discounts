<?php
if (!defined('ABSPATH'))
    exit;

if (!class_exists('Ship_Discounts_Carriers')) {
    /**
     * Class managing the carriers.
     */
    class Ship_Discounts_Carriers {
        /**
         * Returns all the carriers.
         * @return array[] Carriers.
         */
        static public function getCarriers(): array {
            return array(
                "ICS" => array(
                    "name"     => esc_html__("ICS Courrier", 'ship-discounts'),
                    "services" => array(
                        "NEXTDAY" => array(
                            "name"    => esc_html__("Next Day", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "GROUND"  => array(
                            "name"    => esc_html__("Ground", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                    ),
                ),

                "DICOM" => array(
                    "name"     => esc_html__("Dicom", 'ship-discounts'),
                    "services" => array(
                        "REGULAR" => array(
                            "name"    => esc_html__("Regular", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                    ),
                ),

                "NATIONEX" => array(
                    "name"     => esc_html__("Nationex", 'ship-discounts'),
                    "services" => array(
                        "REGULAR" => array(
                            "name"    => esc_html__("Regular", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                    ),
                ),

                "CANADAPOST" => array(
                    "name"     => esc_html__("Poste Canada", 'ship-discounts'),
                    "services" => array(
                        "EXPEDITED" => array(
                            "name"    => esc_html__("Expedited", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "PRIORITY"  => array(
                            "name"    => esc_html__("Priority", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "XPRESS"    => array(
                            "name"    => esc_html__("Xpresspost", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                    ),
                ),

                "PUROLATOR" => array(
                    "name"     => esc_html__("Purolator", 'ship-discounts'),
                    "services" => array(
                        "EXPRESS"     => array(
                            "name"    => esc_html__("Express", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "SATURDAY"    => array(
                            "name"    => esc_html__("On Saturday", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "EXPRESS9"    => array(
                            "name"    => esc_html__("Tomorrow at 9H00", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "EXPRESS1030" => array(
                            "name"    => esc_html__("Tomorrow at 10H30", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "GROUND"      => array(
                            "name"    => esc_html__("Ground", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                    ),
                ),

                "UPS" => array(
                    "name"     => esc_html__("UPS", 'ship-discounts'),
                    "services" => array(
                        "STANDARD"     => array(
                            "name"    => esc_html__("Regular", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "EXPRESS"      => array(
                            "name"    => esc_html__("Tomorrow at 10H30", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                        "EXPRESSSAVER" => array(
                            "name"    => esc_html__("Tomorrow at 15H00", 'ship-discounts'),
                            "enabled" => true,
                            "factor"  => null,
                        ),
                    ),
                ),
            );
        }

        /**
         * Returns the possible delivery status.
         * @return array Delivery status.
         */
        static public function getDeliveryStatus(): array {
            return array(
                0  => esc_html__('Unknown', 'ship-discounts'),
                10 => esc_html__('Label created', 'ship-discounts'),
                20 => esc_html__('In transit', 'ship-discounts'),
                40 => esc_html__('Delivered', 'ship-discounts'),
                50 => esc_html__('Canceled', 'ship-discounts'),
                90 => esc_html__('Need attention', 'ship-discounts'),
            );
        }

        /**
         * Gets the carriers’ service availabilities and rates and filter the result.
         * @param WC_Shipping_Method $method Shipping method.
         * @param string $postal_code Customer's postal code.
         * @param array $packages Packages.
         * @param bool $signature Required signature.
         * @param bool $ncv Non-conveyable product.
         * @param bool $dim Check dimension unit.
         * @param bool $predefined If predefined packets and boxes must be used.
         * @param bool $free_shipping Free shipping.
         * @param null|WP_User $user Logged-in user.
         * @param array $items Order items (WC_Order_Item_Product).
         * @return array|false Carriers’ service availabilities and rates or false on error.
         */
        public static function getQuotes(WC_Shipping_Method $method, $postal_code, $packages = [], $signature = false, $ncv = false, $dim = true, $predefined = true, $free_shipping = false, $user = null, $items = []) {
            $api_key = get_option('sd_lar_api_dev', "") ? get_option('sd_lar_api_key_dev', "") : get_option('sd_lar_api_key_prod', "");
            $url = get_option('sd_lar_api_url', SD_LAR_API_URL_PROD) . Ship_Discounts_LAR_Plugin::CARRIER_QUOTE_URL . Ship_Discounts_LAR_Plugin::API_PARAM . $api_key;

            $weight_unit = get_option('woocommerce_weight_unit');
            if ($method->id === 'sd_lar_method' && $method->predefined_package !== 'no' && $predefined) {
                foreach ($packages as $id => $package) {
                    $packages[$id]['width'] = floatval($method->package_width);
                    $packages[$id]['length'] = floatval($method->package_length);
                    $packages[$id]['height'] = floatval($method->package_height);
                    $packages[$id]['weight'] = floatval($method->package_weight) + floatval(Ship_Discounts_LAR_Plugin::convertToLbs($weight_unit, $package['weight']));
                }
            }
            else {
                if ($dim) {
                    $dimension_unit = get_option('woocommerce_dimension_unit');
                    if (!($weight_unit === 'lbs' && $dimension_unit === 'in')) {
                        foreach ($packages as $id => $package) {
                            $packages[$id]['width'] = floatval(Ship_Discounts_LAR_Plugin::convertToIn($dimension_unit, $package['width']));
                            $packages[$id]['length'] = floatval(Ship_Discounts_LAR_Plugin::convertToIn($dimension_unit, $package['length']));
                            $packages[$id]['height'] = floatval(Ship_Discounts_LAR_Plugin::convertToIn($dimension_unit, $package['height']));
                            $packages[$id]['weight'] = floatval(Ship_Discounts_LAR_Plugin::convertToLbs($weight_unit, $package['weight']));
                        }
                    }
                }
            }

            $boxes_cost = 0;
            if (get_option('sd_lar_settings_use_boxes', false) && $predefined) {
                $packed = Ship_Discounts_Box::packAllItems(get_option('sd_lar_settings_boxes', []), $packages);
                $packages = [];
                foreach ($packed as $id => $p) {
                    $packages[$id]['width'] = $p->width;
                    $packages[$id]['length'] = $p->length;
                    $packages[$id]['height'] = $p->height;
                    $packages[$id]['weight'] = $p->weight;
                    $boxes_cost += $p->price;
                }
            }
            WC()->session->set('sd_lar_boxes_cost', apply_filters('sd_lar_boxes_cost_value', $boxes_cost, get_option('sd_lar_settings_use_boxes', false), $user, $items));
            WC()->session->set('sd_lar_packages', $packages);

            $params = wp_json_encode(array(
                "requestOrigin" => 'wc'.SD_LAR_VERSION,
                "packages"       => $packages,
                "signature"      => (bool)$signature,
                "ncv"            => (bool)$ncv,
                "insurance"      => floatval($method->shipment_value),
                "dangerous"      => 0,
                "postalcode"     => $postal_code,
                "postalcodeFrom" => str_replace(' ', '', WC()->countries->get_base_postcode()),
            ));

            $args = array(
                'body'    => $params,
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
            );
            $request = wp_remote_post($url, $args);

            if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
                $result = json_decode(wp_remote_retrieve_body($request), true);

                if (isset($result['carriers'])) {
                    // Log
                    if (!$result['carriers']) {
                        $log = array('user' => get_current_user_id(), 'error' => $result['ValidationResult']);
                        wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-get-quotes'));
                    }

                    return self::filterCarriers($result['carriers'], $method, $free_shipping, $user, $items);
                }
                else {
                    // Log
                    $log = array('user' => get_current_user_id(), 'error' => $result['errDetails']);
                    wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-get-quotes'));

                    foreach ($result["errDetails"] as $error) {
                        if ($error['errCode'] === "VALI002" ||
                            $error['errCode'] === "VALI009" ||
                            ($error['errCode'] === 'VALI003' && strpos($error['errDetail'], 'postalCode') !== false))
                            return 'POSTALCODE';

                        if ($error['errCode'] === 'VALI003')
                            return 'DIMENSIONS';
                    }
                }
            }

            // Log
            $log = array('user' => get_current_user_id(), 'status' => $request);
            if (!is_wp_error($request)) $log['result'] = json_decode(wp_remote_retrieve_body($request), true);
            wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-get-quotes'));

            return false;
        }

        /**
         * Submit an order.
         * @param WC_Order $order Order.
         * @param array $carrier Chosen carrier.
         * @param bool $signature Required signature.
         * @param bool $ncv If non-conveyable.
         * @param string $note Note for the carrier.
         * @return string|false The order number or false on error.
         */
        public static function submitOrder($order, $carrier, $packages = [], $signature = false, $ncv = false, $note = '') {
            $api_key = get_option('sd_lar_api_dev', "") ? get_option('sd_lar_api_key_dev', "") : get_option('sd_lar_api_key_prod', "");
            $url = get_option('sd_lar_api_url', SD_LAR_API_URL_PROD) . Ship_Discounts_LAR_Plugin::ORDER_URL . Ship_Discounts_LAR_Plugin::API_PARAM . $api_key;

            $items = [];
            foreach ($order->get_items() as $item) {
                $items[] = array(
                    'partNo' => $item->get_product()->get_sku(),
                    'name'   => $item->get_name(),
                    'qty'    => $item->get_quantity(),
                );
            }

            $address = array(
                'name'       => $order->get_shipping_first_name() . ' ' . $order->get_shipping_last_name(),
                'address1'   => $order->get_shipping_address_1(),
                'address2'   => $order->get_shipping_address_2(),
                'postalcode' => $order->get_shipping_postcode(),
                'phone1'     => $order->get_shipping_phone() ?: $order->get_billing_phone(),
                'city'       => $order->get_shipping_city(),
            );

            $phone = get_option('sd_lar_settings_phone_number', '');
            if (strrpos($phone, '+'))
                $phone = substr($phone, 0, strrpos($phone, '+'));
            if (strrpos($phone, '#'))
                $phone = substr($phone, 0, strrpos($phone, '#'));

            $shop = array(
                'name'       => get_bloginfo('name'),
                'address1'   => WC()->countries->get_base_address(),
                'address2'   => WC()->countries->get_base_address_2(),
                'postalcode' => str_replace(' ', '', WC()->countries->get_base_postcode()),
                'phone1'     => $phone,
                'city'       => WC()->countries->get_base_city(),
            );

            $params = wp_json_encode(array(
                "requestOrigin" => 'wc'.SD_LAR_VERSION,
                "order" => array(
                    "items"              => $items,
                    "packages"           => $packages,
                    "orderType"          => "label",
                    "insurance"          => floatval($carrier['insurance']),
                    "dangerous"          => false,
                    "signature"          => (bool)$signature,
                    "ncv"                => (bool)$ncv,
                    "deliveryCost"       => floatval($carrier['services']['cost']),
                    "carrierNotes"       => mb_substr(strval($note), 0, 30),
                    "carrierCode"        => $carrier['carrierCode'],
                    "carrierServiceCode" => $carrier['services']['serviceCode'],
                    "reference1"         => null,
                    "reference2"         => null,
                    "reference3"         => null,
                    "deliverTo"          => $address,
                    "soldTo"             => $address,
                    "shipFrom"           => $shop,
                ),
            ));

            $args = array(
                'body'    => $params,
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
            );
            $request = wp_remote_post($url, $args);

            if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
                $result = json_decode(wp_remote_retrieve_body($request), true);

                if ($result['orderNumber'])
                    return $result['orderNumber'];
                else {
                    // Log
                    $log = array('order' => $order->get_id(), 'error' => $result["errDetails"]);
                    wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-submit-order'));

                    foreach ($result["errDetails"] as $error) {
                        if ($error['errCode'] === "VALI009-deliverTo")
                            return 'PHONE-deliverTo';
                        else if ($error['errCode'] === "VALI009-soldTo")
                            return 'PHONE-soldTo';
                        else if ($error['errCode'] === "VALI009-shipfrom")
                            return 'PHONE-shipfrom';
                        else if ($error['errCode'] === 'VALI002')
                            return 'MISSING';
                    }

                    return false;
                }
            }

            // Log
            $log = array('order' => $order->get_id(), 'status' => $request);
            if (!is_wp_error($request)) $log['result'] = json_decode(wp_remote_retrieve_body($request), true);
            wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-submit-order'));

            return false;
        }

        /**
         * Delete an order.
         * @param int|string $order Order ID.
         * @param WC_Order $wc_order WooCommerce Order.
         * @return bool If the order has been deleted or not.
         */
        public static function deleteOrder($order, $wc_order) {
            $api_key = get_option('sd_lar_api_dev', "") ? get_option('sd_lar_api_key_dev', "") : get_option('sd_lar_api_key_prod', "");
            $url = get_option('sd_lar_api_url', SD_LAR_API_URL_PROD) . Ship_Discounts_LAR_Plugin::ORDER_URL . '/' . $order . Ship_Discounts_LAR_Plugin::API_PARAM . $api_key . '&requestOrigin=wc'.SD_LAR_VERSION;

            $args = array(
                'method'  => 'DELETE',
                'timeout' => 30,
            );
            $request = wp_remote_request($url, $args);

            if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
                $result = json_decode(wp_remote_retrieve_body($request), true);

                // Log
                if (!isset($result['result']) || $result['result'] !== 'success') {
                    $log = array('order' => $wc_order->get_id(), 'sd_lar_order' => $order, 'error' => $result["errDetails"]);
                    wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-delete-order'));
                }

                return $result['result'] === 'success';
            }

            // Log
            $log = array('order' => $wc_order->get_id(), 'sd_lar_order' => $order, 'status' => $request);
            if (!is_wp_error($request)) $log['result'] = json_decode(wp_remote_retrieve_body($request), true);
            wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-delete-order'));

            return false;
        }

        /**
         * Get an order's details.
         * @param int|string $order Order ID.
         * @param WC_Order $wc_order WooCommerce Order.
         * @return mixed The order's details or false on error.
         */
        public static function getOrder($order, $wc_order) {
            $api_key = get_option('sd_lar_api_dev', "") ? get_option('sd_lar_api_key_dev', "") : get_option('sd_lar_api_key_prod', "");
            $url = get_option('sd_lar_api_url', SD_LAR_API_URL_PROD) . Ship_Discounts_LAR_Plugin::ORDER_URL . '/' . $order . Ship_Discounts_LAR_Plugin::API_PARAM . $api_key . '&requestOrigin=wc'.SD_LAR_VERSION;

            $args = array(
                'timeout' => 30,
            );
            $request = wp_remote_get($url, $args);

            if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
                $result = json_decode(wp_remote_retrieve_body($request), true);

                $ok = $result['order']['details'] ? $result['order']['details'][0] : false;

                // Log
                if (!$ok) {
                    $log = array('order' => $wc_order->get_id(), 'sd_lar_order' => $order);
                    $log['error'] = !empty(array_filter((array)$result)) ? $result : 'Cannot find order #'.$order;
                    wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-get-order'));
                }

                return $ok;
            }

            // Log
            $log = array('order' => $wc_order->get_id(), 'sd_lar_order' => $order, 'status' => $request);
            if (!is_wp_error($request)) $log['result'] = json_decode(wp_remote_retrieve_body($request), true);
            wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-get-order'));

            return false;
        }

        /**
         * Get an order's label.
         * @param int|string $order Order ID.
         * @param WC_Order $wc_order WooCommerce Order.
         * @return mixed The order's label or false on error.
         */
        public static function getLabel($order, $wc_order) {
            $api_key = get_option('sd_lar_api_dev', "") ? get_option('sd_lar_api_key_dev', "") : get_option('sd_lar_api_key_prod', "");
            $url = get_option('sd_lar_api_url', SD_LAR_API_URL_PROD) . Ship_Discounts_LAR_Plugin::ORDER_LABEL_URL . '/' . $order . Ship_Discounts_LAR_Plugin::API_PARAM . $api_key . '&requestOrigin=wc'.SD_LAR_VERSION;

            $args = array(
                'timeout' => 30,
            );
            $request = wp_remote_get($url, $args);

            if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
                $result = json_decode(wp_remote_retrieve_body($request), true);

                // Log
                if (isset($result["errDetails"])) {
                    $log = array('order' => $wc_order->get_id(), 'sd_lar_order' => $order, 'error' => $result["errDetails"]);
                    wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-get-label'));
                }

                return $result["documentURL"];
            }

            // Log
            $log = array('order' => $wc_order->get_id(), 'sd_lar_order' => $order, 'status' => $request);
            if (!is_wp_error($request)) $log['result'] = json_decode(wp_remote_retrieve_body($request), true);
            wc_get_logger()->debug(wc_print_r($log, true), array('source' => 'lar-get-label'));

            return false;
        }

        /**
         * Filters an array of carriers (API) based on the user's options.
         * @param array $carriers Array of carriers.
         * @param WC_Shipping_Method $method Shipping method.
         * @param bool $free_shipping Free shipping.
         * @param null|WP_User $user Logged-in user.
         * @param array $items Order items (WC_Order_Item_Product).
         * @return array Filtered array of carriers.
         */
        private static function filterCarriers(array $carriers, WC_Shipping_Method $method, bool $free_shipping = false, $user = null, $items = []): array {
            $result = [];

            if ($method->instance_id == 0 || $method->id !== 'sd_lar_method')
                $method->carriers_list = get_option('sd_lar_settings_carriers_list', []);

            foreach ($carriers as $carrier) {
                foreach ($carrier['prices'] as $price) {
                    if ($method->carriers_list[$carrier['carrierCode']]['services'][$price['serviceCode']]['enabled']) {
                        $result[$carrier['carrierCode']]['insurance'] = $method->shipment_value;
                        $result[$carrier['carrierCode']]['carrierName'] = $carrier['carrierName'];
                        $result[$carrier['carrierCode']]['services'][$price['serviceCode']] = array(
                            'serviceName'   => $method->carriers_list[$carrier['carrierCode']]['services'][$price['serviceCode']]['name'],
                            'pickupService' => $carrier['pickupService'],
                            'deliveryDate'  => apply_filters('sd_lar_delivery_date_value', $price['deliveryDate'], $carrier['carrierCode'], $price['serviceCode'], $user, $items),
                            'cost'          => $price['cost']
                        );

                        // Free shipping
                        if ($free_shipping)
                            $result[$carrier['carrierCode']]['services'][$price['serviceCode']]['displayCost'] = 0;
                        // Factor
                        else {
                            $factor = $method->carriers_list[$carrier['carrierCode']]['services'][$price['serviceCode']]['factor'];
                            if (is_numeric($factor) && $factor != 1 && $factor >= 0) {
                                if (is_numeric($price['cost'])) {
                                    $result[$carrier['carrierCode']]['services'][$price['serviceCode']]['displayCost'] = $price['cost'] * $factor;
                                }
                            }
                            // Filter
                            $result[$carrier['carrierCode']]['services'][$price['serviceCode']]['displayCost'] = apply_filters('sd_lar_carrier_display_cost_value', $result[$carrier['carrierCode']]['services'][$price['serviceCode']]['displayCost'], $price['cost'], $carrier['carrierCode'], $price['serviceCode'], $user, $items);
						}
                    }
                }
            }

            return $result;
        }

        /**
         * Finds the cheapest carrier and service codes in an array of carriers.
         * @param array $carriers Carriers.
         * @return array Cheapest carrier and service codes.
         */
        public static function findCheapestCarrier(array $carriers): array {
            $cheapest = [];
            $compare = null;

            foreach ($carriers as $c_code => $carrier) {
                foreach ($carrier['services'] as $code => $service) {
                    if ($compare === null || $compare > $service['cost']) {
                        $compare = $service['cost'];
                        $cheapest['carrier'] = $c_code;
                        $cheapest['service'] = $code;
                    }
                }
            }

            return $cheapest;
        }
    }
}