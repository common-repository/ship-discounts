<?php
if (!defined('ABSPATH'))
    exit;

require_once 'API_LAR_Order.php';

use API_SD_LAR\Address;
use API_SD_LAR\Customer;
use API_SD_LAR\LineItem;
use API_SD_LAR\Order;
use API_SD_LAR\ShippingLines;

if (!class_exists('Ship_Discounts_SD_LAR_API')) {
    /**
     * Class of the REST API of Ship Discounts.
     */
    class Ship_Discounts_SD_LAR_API extends WP_REST_Controller {

        /**
         * Register the routes for the objects of the controller.
         */
        public function register_routes() {
            $version = '1';
            $namespace = 'lar/v' . $version;
            // Get the order
            register_rest_route($namespace, '/order/(?P<id>[\d]+)', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_order'),
                    'permission_callback' => array($this, 'item_permissions_check'),
                    'args'                => array(
                        'context' => $this->get_context_param(array('default' => 'view')),
                        'token'   => array(
                            'required' => true,
                        )
                    ),
                ),
            ));
            // Get all orders
            register_rest_route($namespace, '/order', array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_orders'),
                    'permission_callback' => array($this, 'item_permissions_check'),
                    'args'                => array(
                        'token'    => array(
                            'required' => true,
                        ),
                        'dateFrom' => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return $this->validate_callback_dates($param);
                            },
                            'sanitize_callback' => function ($param, $request, $key) {
                                return $this->sanitize_callback_dates($param);
                            },
                        ),
                        'dateTo'   => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return $this->validate_callback_dates($param);
                            },
                            'sanitize_callback' => function ($param, $request, $key) {
                                return $this->sanitize_callback_dates($param);
                            },
                        ),
                        'status'   => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return array_key_exists($this->sanitize_callback_status($param), wc_get_order_statuses());
                            },
                            'sanitize_callback' => function ($param, $request, $key) {
                                return $this->sanitize_callback_status($param);
                            },
                        ),
                    ),
                ),
            ));
            // Update an order
            register_rest_route($namespace, '/fulfill', array(
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_order'),
                    'permission_callback' => array($this, 'item_permissions_check'),
                    'args'                => array(
                        'token'          => array(
                            'required' => true,
                        ),
                        'lar_id'         => array(
                            'required'          => true,
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param) && $param >= 0;
                            }
                        ),
                        'wc_id'          => array(
                            'required'          => true,
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param) && $param >= 0;
                            }
                        ),
                        'carrierCode'    => array(
                            'required'          => true,
                            'validate_callback' => function ($param, $request, $key) {
                                return is_string($param);
                            }
                        ),
                        'serviceCode'    => array(
                            'required'          => true,
                            'validate_callback' => function ($param, $request, $key) {
                                return is_string($param);
                            }
                        ),
                        'cost'           => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param) && $param >= 0;
                            }
                        ),
                        'displayCost'    => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param) && $param >= 0;
                            }
                        ),
                        'packages'       => array(
                            'validate_callback' => function ($param, $request, $key) {
                                if (!is_array($param))
                                    return false;

                                foreach ($param as $p) {
                                    if (!((isset($p['width']) && is_numeric($p['width'])) &&
                                        (isset($p['length']) && is_numeric($p['length'])) &&
                                        (isset($p['height']) && is_numeric($p['height'])) &&
                                        (isset($p['weight']) && is_numeric($p['weight']))))
                                        return false;
                                }

                                return true;
                            }
                        ),
                        'trackingNumber' => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return is_string($param);
                            }
                        ),
                        'trackingURL'    => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return esc_url($param) === $param;
                            }
                        ),
                        'label'          => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return esc_url($param) === $param;
                            }
                        ),
                        'boxesCost'      => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param) && $param >= 0;
                            }
                        ),
                        'insurance'      => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return is_numeric($param) && $param >= 0;
                            }
                        ),
                        'signature'      => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return filter_var($param, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
                            }
                        ),
                        'ncv'            => array(
                            'validate_callback' => function ($param, $request, $key) {
                                return filter_var($param, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
                            }
                        ),
                    ),
                ),
            ));
        }

        /**
         * Checks if a given request has access to get and/or update a specific item.
         * @param WP_REST_Request $request Full details about the request.
         * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
         */
        public function item_permissions_check($request) {
            if (!get_option('sd_lar_account_activated', false))
                return new WP_Error('shop_disconnected', esc_html__('This shop has not been connected.', 'ship-discounts'), array('status' => 400));

            $dev = get_option('sd_lar_api_dev', "");
            $token = $dev ? get_option('sd_lar_api_token_dev', "") : get_option('sd_lar_api_token_prod', "");

            if (($dev && !$token) || (!$dev && !$token))
                return new WP_Error('no_api_token', esc_html__('This shop does not have a token.', 'ship-discounts'), array('status' => 400));

            if ($request->get_param('token') !== $token)
                return new WP_Error('wrong_api_token', esc_html__('The token does not match the shop\'s.', 'ship-discounts'), array('status' => 401));

            return true;
        }

        /**
         * Validates if it is a date.
         * @param $param mixed The date.
         * @return bool Whether it is a date.
         */
        public function validate_callback_dates($param) {
            try {
                new DateTime($param);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }

        /**
         * Creates a DateTime object based on a date parameter.
         * @param $param mixed The date.
         * @return DateTime The DateTime object.
         * @throws Exception
         */
        public function sanitize_callback_dates($param) {
            try {
                return new DateTime($param, new DateTimeZone('America/Montreal'));
            } catch (Exception $e) {
                return new DateTime('now', new DateTimeZone('America/Montreal'));
            }
        }

        /**
         * Validates if it is a WooCommerce status.
         * @param $param mixed The status.
         * @return string The WooCommerce status.
         */
        public function sanitize_callback_status($param) {
            if (!$param)
                return $param;

            $param = trim(strtolower($param));
            if (strpos($param, 'wc-') !== 0)
                $param = 'wc-' . $param;

            return $param;
        }

        /**
         * Get a specific order.
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_Error|WP_REST_Response The order or an error.
         */
        public function get_order($request) {
            $id = $request->get_param('id');

            if ($id && (int)$id) {
                $wc_order = wc_get_order($id);
                if ($wc_order)
                    return new WP_REST_Response($this->prepare_order($wc_order), 200);
                else
                    return new WP_Error("invalid_woocommerce_order_id", esc_html__('Invalid Woocommerce Order ID.', 'ship-discounts'), array('status' => 400));
            }
            else {
                return new WP_Error("invalid_id_format", esc_html__('Invalid ID Format.', 'ship-discounts'), array('status' => 400));
            }
        }

        /**
         * Get all the orders.
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_REST_Response All the orders.
         */
        public function get_orders($request) {
            $params = $request->get_params();
            $data = [];

            // Filters
            $args = array(
                'limit' => -1,
                'post_type' => 'shop_order',
                'orderby' => 'date',
                'order' => 'ASC',
            );

            if (isset($params['dateFrom']))
                $dateFrom = gmdate('Y-m-d H:i:s', strtotime($params['dateFrom']->format('Y-m-d H:i:s T')) ?: null);

            if (isset($params['dateTo']))
                $dateTo = gmdate('Y-m-d H:i:s', strtotime($params['dateTo']->format('Y-m-d H:i:s T')) ?: null);

            if (isset($params['status']))
                $args['status'] = $params['status'];

            if (isset($dateFrom) && isset($dateTo))
                $args['date_created'] = $dateFrom .'...'. $dateTo;
            else if (isset($dateFrom))
                $args['date_after'] = $dateFrom;
            else if (isset($dateTo))
                $args['date_before'] = $dateTo;

            // Get orders
            $orders = wc_get_orders($args);
            if ($orders) {
                foreach ($orders as $order) {
                    $data[] = $this->prepare_order($order);
                }
            }

            return new WP_REST_Response($data, 200);
        }

        /**
         * Update one item from the collection
         * @param WP_REST_Request $request Full data about the request.
         * @return WP_Error|WP_REST_Response
         */
        public function update_order($request) {
            $id = $request->get_param('wc_id');
            if ($id && (int)$id) {
                // Check if the WC order exists
                $wc_order = wc_get_order($id);
                if (!$wc_order)
                    return new WP_Error("invalid_woocommerce_order_id", esc_html__('Invalid Woocommerce Order ID.', 'ship-discounts'), array('status' => 400));
                else {
                    // Check if we can access the LAR order
                    $lar_order = $request->get_param('lar_id');
                    if (!$lar_order || !Ship_Discounts_Carriers::getOrder($lar_order, $wc_order))
                        return new WP_Error('lar_invalid_id', esc_html__('Invalid LAR Order ID.', 'ship-discounts'), array('status' => 400));

                    $carriers = Ship_Discounts_Carriers::getCarriers();

                    // Check if the carrier code is valid
                    $carrierCode = $request->get_param('carrierCode') ?? '';
                    if (!$carrierCode || !Ship_Discounts_LAR_Plugin::multiArrayKeyExists(Ship_Discounts_Carriers::getCarriers(), $carrierCode)) {
                        $errMsg = esc_html__('Invalid Carrier Code.', 'ship-discounts') . ' ' . esc_html__('Accepted values', 'ship-discounts') . ': ' . implode(', ', array_keys($carriers));
                        return new WP_Error('lar_invalid_carrier', $errMsg, array('status' => 400));
                    }

                    // Check if the service code is valid
                    $serviceCode = $request->get_param('serviceCode') ?? '';
                    if (!$serviceCode || !Ship_Discounts_LAR_Plugin::multiArrayKeyExists(Ship_Discounts_Carriers::getCarriers(), $serviceCode)) {
                        $errMsg = esc_html__('Invalid Service Code.', 'ship-discounts') . ' ' . esc_html__('Accepted values', 'ship-discounts') . ' (' . $carrierCode . '): ' . implode(', ', array_keys($carriers[$carrierCode]['services']));
                        return new WP_Error('lar_invalid_service', $errMsg, array('status' => 400));
                    }

                    // Other values
                    $packages = $request->get_param('packages') ?: $wc_order->get_meta('_sd_lar_packages');
                    $trackingNumber = $request->get_param('trackingNumber') ?: $wc_order->get_meta('_sd_lar_tracking_nb');
                    $trackingURL = $request->get_param('trackingURL') ?: $wc_order->get_meta('_sd_lar_tracking_url');
                    $label = $request->get_param('label') ?: $wc_order->get_meta('_sd_lar_label');

                    $carrier_data = $wc_order->get_meta('sd_lar_carrier_data');
                    if (!$carrier_data) {
                        $carrier_data = [];
                        $carrier_data['insurance'] = $carrier_data['services']['cost'] = $carrier_data['services']['displayCost'] = 0;
                    }

                    $cost = $request->get_param('cost') !== null ? floatval($request->get_param('cost')) : $carrier_data['services']['cost'];
                    $displayCost = $request->get_param('displayCost');
                    if ($request->get_param('cost') === null && $displayCost === null)
                        $displayCost = $carrier_data['services']['displayCost'] ?: $cost;
                    else
                        $displayCost = $displayCost !== null && $displayCost !== '' ? floatval($displayCost) : $cost;

                    $insurance = $request->get_param('insurance') !== null ? floatval($request->get_param('insurance')) : $carrier_data['insurance'];
                    $signature = $request->get_param('signature') !== null ? filter_var($request->get_param('signature'), FILTER_VALIDATE_BOOLEAN) : $wc_order->get_meta('_sd_lar_signature');
                    $ncv = $request->get_param('ncv') !== null ? filter_var($request->get_param('ncv'), FILTER_VALIDATE_BOOLEAN) : $wc_order->get_meta('_sd_lar_ncv');
                    $boxesCost = $request->get_param('boxesCost') !== null ? floatval($request->get_param('boxesCost')) : $wc_order->get_meta('_sd_lar_boxes_cost');

                    // Carrier data
                    $carrier = [];
                    $carrier['insurance'] = $insurance;
                    $carrier['carrierCode'] = $carrierCode;
                    $carrier['carrierName'] = $carriers[$carrierCode]['name'];
                    $carrier['services']['serviceCode'] = $serviceCode;
                    $carrier['services']['cost'] = $cost;
                    $carrier['services']['displayCost'] = $displayCost;

                    // Update the WC order
                    $wc_order->update_meta_data('_sd_lar_order_number', wc_clean($lar_order));
                    $wc_order->update_meta_data('_sd_lar_carrier_code', wc_clean($carrierCode));
                    $wc_order->update_meta_data('_sd_lar_service_code', wc_clean($serviceCode));

                    $wc_order->update_meta_data('_sd_lar_packages', wc_clean($packages));
                    $wc_order->update_meta_data('_sd_lar_tracking_url', wc_clean($trackingURL));
                    $wc_order->update_meta_data('_sd_lar_tracking_nb', wc_clean($trackingNumber));
                    $wc_order->update_meta_data('_sd_lar_label', wc_clean($label));

                    $wc_order->update_meta_data('_sd_lar_service_cost', wc_clean($cost));
                    $wc_order->update_meta_data('_sd_lar_service_display_cost', wc_clean($displayCost));
                    $wc_order->update_meta_data('_sd_lar_boxes_cost', wc_clean($boxesCost));

                    $wc_order->update_meta_data('_sd_lar_signature', $signature);
                    $wc_order->update_meta_data('_sd_lar_ncv', $ncv);
                    $wc_order->update_meta_data('sd_lar_carrier_data', wc_clean($carrier));

                    // Shipping method
                    if (!$wc_order->get_items('shipping')) {
                        $item = new WC_Order_Item_Shipping();
                        $item->set_method_title(esc_html__('Ship Discounts', 'ship-discounts'));
                        $item->set_method_id('sd_lar_method');
                        $wc_order->add_item($item);
                        $wc_order->save();
                    }
                    $wc_order->set_status('processing');
                    $wc_order->save();

                    // Note: We do not change the order total

                    return new WP_REST_Response($this->prepare_order($wc_order), 200);
                }
            }
            else {
                return new WP_Error("invalid_id_format", esc_html__('Invalid ID Format.', 'ship-discounts'), array('status' => 400));
            }
        }

        /**
         * Prepare the order for the response.
         * @param WC_Order|WC_Order_Refund $item Woocommerce order.
         * @return Order
         */
        public function prepare_order($item) {
            /** @var $item WC_Order|WC_Order_Refund */

            // Shipping method
            $method_id = $method_title = "";
            $method = $item->get_shipping_methods();
            if ($method) {
                $method = reset($method);
                $method_id = $method->get_method_id();
                $method_title = $method->get_method_title();
            }

            // Customer
            $customer = new Customer($item->get_billing_first_name() ?: '', $item->get_billing_last_name() ?: '',
                $item->get_billing_phone() ?: '', $item->get_billing_email() ?: '');

            // Billing address
            $billingAdress = new Address($item->get_billing_address_1() ?: '', $item->get_billing_address_2() ?: '',
                $item->get_billing_city() ?: '', $item->get_billing_company() ?: '', $item->get_billing_country() ?: '',
                $item->get_billing_state() ?: '', $item->get_billing_postcode() ?: '');

            // Shipping address
            $shippingAdress = new Address($item->get_shipping_address_1() ?: '', $item->get_shipping_address_2() ?: '',
                $item->get_shipping_city() ?: '', $item->get_shipping_company() ?: '', $item->get_shipping_country() ?: '',
                $item->get_shipping_state() ?: '', $item->get_shipping_postcode() ?: '');

            // Shipping note
            $shipping_note = $item->meta_exists('_sd_lar_shipping_note') ? $item->get_meta('_sd_lar_shipping_note') : $item->get_customer_note();

            // Products
            $products = [];
            foreach ($item->get_items() as $product) {
                $name = '';
                $sku = '';

                $p = $product->get_product();
                if ($p) {
                    $name = $p->get_title() ?: '';
                    $sku = $p->get_sku() ?: '';
                }

                $variation_id = $product->get_variation_id() ?: null;
                $variation_name = $variation_id ? $product->get_name() : '';

                $products[] = new LineItem($product->get_id(), $name, floatval($product->get_subtotal()),
                    $product->get_quantity(), $sku, $variation_id, $variation_name);
            }

            // Shipping
            $serviceCost = $item->get_meta('_sd_lar_service_cost') ?: 0;
            $serviceDisplayCost = $item->get_meta('_sd_lar_service_display_cost');
            $serviceDisplayCost = $serviceDisplayCost !== null && $serviceDisplayCost !== '' ? $serviceDisplayCost : $serviceCost;
            $shippingLines = new ShippingLines($item->get_meta('_sd_lar_carrier_code') ?: '',
                $item->get_meta('_sd_lar_service_code') ?: '', $serviceCost, $serviceDisplayCost,
                $item->get_meta('_sd_lar_tracking_nb') ?: '', $item->get_meta('_sd_lar_tracking_url') ?: '',
                $item->get_meta('_sd_lar_boxes_cost') ?: 0);

            // Order
            return new Order($item->get_id(), $item->get_status(), $item->get_shipping_total(), $item->get_subtotal(),
                $item->get_total_tax(), $item->get_total(), $method_title, $method_id, $customer, $billingAdress, $shippingAdress, $products,
                $shippingLines, $shipping_note, $item->get_date_created(), $item->get_date_modified(),
                $item->get_date_completed());
        }
    }

    /**
     * Register the routes.
     * @return void
     */
    function sd_lar_register_rest_routes() {
        $controller = new Ship_Discounts_SD_LAR_API();
        $controller->register_routes();
    }

    add_action('rest_api_init', 'sd_lar_register_rest_routes');
}