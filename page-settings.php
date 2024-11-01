<?php
if (!defined('ABSPATH'))
    exit;

if (!function_exists('sd_lar_validate_activate_key_api')) {
    /**
     * Calls API to activate or deactivate the shop.
     * @param string $url Endpoint.
     * @param string $params POST parameters.
     * @param bool $conn If it is a connection request.
     * @return false|mixed If the shop has been activated or deactivated.
     */
    function sd_lar_validate_activate_key_api($url, $params, $conn = false) {
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

            // Allow disconnection if the shop does not exist yet.
            if (!$conn && isset($result['errDetails']))
                $ok = count($result['errDetails']) === 1 && $result['errDetails'][0]['errCode'] === "VALI004";
            else
                $ok = $result['success'];

            // Log
            if (!$ok)
                wc_get_logger()->debug(wc_print_r($result['errDetails'], true), array('source' => 'lar-shop-activation'));

            return $ok;
        }

        return false;
    }
}

if (!function_exists('sd_lar_validate_activate_key')) {
    /**
     * Deactivate and/or activate the shop.
     * @param string $key New API key.
     * @param string $old_key Old API key.
     * @param bool $dev Dev mode or not.
     * @return string Message.
     */
    function sd_lar_validate_activate_key($key, $old_key, $dev) {
        $url = $dev ? SD_LAR_API_URL_DEV : SD_LAR_API_URL_PROD;

        // Parameters
        $params = array(
            "store" => array(
                "storeType" => "woocommerce",
                "domain"    => wp_parse_url(home_url())['host']
            ),
        );

        // Deactivate
        $disc = true;
        if ($old_key) {
            $disc = sd_lar_validate_activate_key_api($url . 'ecomm/disconnect/?apiKey=' . $old_key, wp_json_encode($params));

            if (!$disc)
                $disc = sd_lar_validate_activate_key_api($url . 'ecomm/disconnect/?apiKey=' . $key, wp_json_encode($params));
        }

        // Activate
        $conn = false;
        if ($disc) {
            $token = $dev ? get_option('sd_lar_api_token_dev', "") : get_option('sd_lar_api_token_prod', "");
            if (!$token) {
                try {
                    if ($dev)
                        update_option('sd_lar_api_token_dev', bin2hex(random_bytes(16)));
                    else
                        update_option('sd_lar_api_token_prod', bin2hex(random_bytes(16)));

                    $token = $dev ? get_option('sd_lar_api_token_dev', "") : get_option('sd_lar_api_token_prod', "");
                } catch (\Throwable $e) {
                    wc_get_logger()->debug(wc_print_r($e, true), array('source' => 'lar-shop-activation'));
                }
            }

            if ($token) {
                $params['store']['param1'] = $token;
                $conn = sd_lar_validate_activate_key_api($url . 'ecomm/connect/?apiKey=' . $key, wp_json_encode($params), true);
            }
        }

        // Messages
        $mode = $dev ? esc_html__('Development', 'ship-discounts') : esc_html__('Production', 'ship-discounts');
        $message = '<b>' . $mode . ':</b><br>';
        if ($disc && $conn) {
            $class = 'notice notice-success';
            $message .= esc_html__('Your shop has been connected.', 'ship-discounts');
            update_option('sd_lar_account_activated', true);
        }
        else {
            $class = 'notice notice-error';
            if (!$disc) {
                $message .= esc_html__('An error has occurred. Your shop could not be disconnected.', 'ship-discounts');
            }
            if (!$disc && !$conn) {
                $message .= '<br>';
            }
            if (!$conn) {
                $message .= esc_html__('An error has occurred. Your shop could not be connected.', 'ship-discounts');
                update_option('sd_lar_account_activated', false);
            }
        }

        return '<div class="' . $class . '"><p>' . $message . '</p></div>';
    }
}

$messages = [];
if (isset($_POST["lar-submit"]) && isset($_POST['lar-nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lar-nonce'])), 'lar-form-nonce')) {
    // Mode
    $old_mode = get_option('sd_lar_api_dev', "");
    $new_mode = wc_clean($_POST['sd_lar_api_dev']) ?: '';

    if ($new_mode !== $old_mode)
        update_option('sd_lar_api_dev', $new_mode);

    if ($new_mode) {
        update_option('sd_lar_api_url', SD_LAR_API_URL_DEV);
        update_option('sd_lar_client_url', SD_LAR_CLIENT_URL_DEV);
    }
    else {
        update_option('sd_lar_api_url', SD_LAR_API_URL_PROD);
        update_option('sd_lar_client_url', SD_LAR_CLIENT_URL_PROD);
    }

    // Dev key
    if ($new_mode && isset($_POST['sd_lar_api_key_dev'])) {
        $old_dev = get_option('sd_lar_api_key_dev', "");
        $new_dev = wc_clean($_POST['sd_lar_api_key_dev']) ?: '';

        if ($old_mode != $new_mode || $old_dev != $new_dev || !get_option('sd_lar_account_activated', false))
            $messages[] = sd_lar_validate_activate_key($new_dev, $old_dev, true);

        update_option('sd_lar_api_key_dev', $new_dev);
    }

    // Prod key
    if (!$new_mode && isset($_POST['sd_lar_api_key_prod'])) {
        $old_prod = get_option('sd_lar_api_key_prod', "");
        $new_prod = wc_clean($_POST['sd_lar_api_key_prod']) ?: '';

        if ($old_mode != $new_mode || $old_prod != $new_prod || !get_option('sd_lar_account_activated', false))
            $messages[] = sd_lar_validate_activate_key($new_prod, $old_prod, false);

        update_option('sd_lar_api_key_prod', $new_prod);
    }
}

$dev = get_option('sd_lar_api_dev', "");
$api_key_dev = get_option('sd_lar_api_key_dev', "");
$api_key_prod = get_option('sd_lar_api_key_prod', "");
$token_dev = get_option('sd_lar_api_token_dev', "");
$token_prod = get_option('sd_lar_api_token_prod', "");
$account_activated = get_option('sd_lar_account_activated', false);

// Messages
if ($messages) {
    foreach ($messages as $message) {
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $message already escaped.
        echo $message;
    }
}
?>

<h1 class="wp-heading-inline"><?php echo esc_html__('Ship Discounts API Activation', 'ship-discounts') ?></h1>
<div id="mainwp_wrap-inside">
    <div class="general">
        <div class="inside">
            <?php if (!$account_activated) { ?>
                <div class="notice notice-error">
                    <p><?php echo esc_html__('Your shop is currently disconnected.', 'ship-discounts') ?></p>
                </div>
            <?php } ?>
            <form method="post">
                <table class="form-table" role="presentation">
                    <tbody>
                    <tr>
                        <th scope="row">
                            <label for="sd_lar_api_dev"><?php echo esc_html__('Development mode', 'ship-discounts') ?></label>
                        </th>
                        <td>
                            <input name="sd_lar_api_dev" type="checkbox" id="sd_lar_api_dev"
                                   value="1" <?php echo $dev ? 'checked' : '' ?>>
                            <p class="description"><?php echo esc_html__('If this option is activated, data will be retrieved from and sent to the Ship Discounts development platform. Please note that no actual orders will be created.', 'ship-discounts') ?></p>
                        </td>
                    </tr>
                    <tr id="dev-key" <?php echo $dev ? '' : 'style="display:none;"' ?>>
                        <th scope="row">
                            <label for="sd_lar_api_key_dev"><?php echo esc_html__('API Key', 'ship-discounts') ?></label>
                        </th>
                        <td>
                            <input name="sd_lar_api_key_dev" type="text" id="sd_lar_api_key_dev"
                                   value="<?php echo esc_attr($api_key_dev) ?>"
                                   class="regular-text">
                            <br><small><?php echo esc_html__('API Token', 'ship-discounts') ?> :
                                <code><?php echo esc_html($token_dev) ?></code></small>
                        </td>
                    </tr>
                    <tr id="prod-key" <?php echo $dev ? 'style="display:none;"' : '' ?>>
                        <th scope="row">
                            <label for="sd_lar_api_key_prod"><?php echo esc_html__('API Key', 'ship-discounts') ?></label>
                        </th>
                        <td>
                            <input name="sd_lar_api_key_prod" type="text" id="sd_lar_api_key_prod"
                                   value="<?php echo esc_attr($api_key_prod) ?>" class="regular-text">
                            <br><small><?php echo esc_html__('API Token', 'ship-discounts') ?> :
                                <code><?php echo esc_html($token_prod) ?></code></small>
                        </td>
                    </tr>
                    </tbody>
                </table>

                <?php
                $allowed_html = array(
	                'input' => array(
		                'id' => array(),
		                'type' => array(),
		                'name' => array(),
		                'value' => array(),
	                ),
                );
                echo wp_kses(wp_nonce_field('lar-form-nonce', 'lar-nonce', true, false), $allowed_html); ?>

                <p>
                    <input class="button-primary" type="submit" name="lar-submit"
                           value="<?php echo esc_html__('Save changes', 'ship-discounts') ?>">
                </p>
            </form>
        </div>
    </div>
</div>