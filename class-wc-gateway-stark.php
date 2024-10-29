<?php
if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly

/**
 * Plugin Name: Stark
 * Plugin URI: https://starkpayments.com/
 * Description: Pay using a digital currency, such as Bitcoin, Ethereum or BitcoinCash from any wallet via Stark
 * Author: starkpayments.com
 * Author URI: https://www.starkpayments.com/
 * Version: 1.0.2
 */

/**
 * Starkpayments.com Payment
 * Pay with your digital currency, such as bitcoin ethereum via Stark
 *
 * Provides a Starkpayments.net Payment Gateway.
 *
 * @class          WC_Starkpayment
 * @extends        WC_Gateway_Stark
 * @version        1.0.2
 * @package        WooCommerce/Classes/Payment
 * @author         Starkpayments.com
 */

add_action('plugins_loaded', 'stark_gateway_load', 0);
function stark_gateway_load()
{

    if (!class_exists('WC_Payment_Gateway')) {
        // oops!
        return;
    }

    /**
     * Add the gateway to WooCommerce.
     */
    add_filter('woocommerce_payment_gateways', 'wccstarkpayments_add_gateway');
    function wccstarkpayments_add_gateway($methods)
    {
        if (!in_array('WC_Gateway_Stark', $methods)) {
            $methods[] = 'WC_Gateway_Stark';
        }
        return $methods;
    }

    /**
     * Adds plugin page links
     *
     * @since 1.0.0
     * @param array $links all plugin links
     * @return array $links all plugin links + our custom links (i.e., "Settings")
     */
    function wc_stark_gateway_plugin_links($links)
    {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=starkpayments') . '">' . __('Configure', 'wc-stark-payments') . '</a>',
        );
        return array_merge($plugin_links, $links);
    }
    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'wc_stark_gateway_plugin_links');

    class WC_Gateway_Stark extends WC_Payment_Gateway
    {
        /**
         * Constructor for the gateway.
         *
         * @access public
         * @return void
         */
        public function __construct()
        {
            global $woocommerce;

            $this->endpoint     = 'https://pay.starkpayments.net/api/payment';
            $this->id           = 'starkpayments';
            $this->icon         = apply_filters('woocommerce_stark_icon', plugins_url('/accept-crypto-payments-on-woocommerce/assets/images/icons/stark.png', _FILE_));
            $this->has_fields   = false;
            $this->method_title = __('Stark', 'wc-stark-payments');

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title       = $this->get_option('title');
            $this->description = $this->get_option('description');

            // Actions
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));

            //Payment listener/API hook
            add_action('woocommerce_api_wc_gateway_stark', array($this, 'check_webhook_response'));
        }

        public function thankyou_page($order_id)
        {
            $order = wc_get_order($order_id);
            if ($order->get_status() != 'completed' && sanitize_text_field($_POST['status']) == 'success') {
                $order->add_order_note('Stark > Payment has been submitted to the blockchain and is waiting to be confirmed');
            }
            if ($order->get_status() != 'completed' && sanitize_text_field($_POST['status']) == 'failed') {
                $order->update_status('failed', 'Payment cancelled by user');
                $order->add_order_note('Stark > Payment cancelled by user');
            }
        }

        /**
         * Admin Panel Options
         *
         * @since 1.0.0
         */
        public function admin_options()
        {
            ?>
            <h3><?php _e('Stark', 'woocommerce');?></h3>
            <p><?php _e('Stark works my adding a new payment option at the point of checkout that allows your customer to pay with a digital currency, such as bitcoin, ethereum, bitcoin cash and others. <a target="_blank" href="https://starkpayments.com/">Sign Up</a> for a stark account and get your API keys.', 'woocommerce');?></p>
                <table class="form-table">
                <?php
                // Generate the HTML For the settings form.
                $this->generate_settings_html();
                ?>
                </table><!--/.form-table-->
            <?php
        }

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields()
        {

            $this->form_fields = apply_filters('wc_stark_form_fields', array(

                'enabled'      => array(
                    'title'   => __('Enable/Disable', 'wc-stark-payments'),
                    'type'    => 'checkbox',
                    'label'   => __('Enable Stark', 'wc-stark-payments'),
                    'default' => 'yes',
                ),

                'title'        => array(
                    'title'       => __('Title', 'wc-stark-payments'),
                    'type'        => 'text',
                    'description' => __('This controls the title for the payment method the customer sees during checkout.', 'wc-stark-payments'),
                    'default'     => __('Digital Currency (Stark)', 'wc-stark-payments'),
                    'desc_tip'    => true,
                ),

                'description'  => array(
                    'title'       => __('Description', 'wc-stark-payments'),
                    'type'        => 'textarea',
                    'description' => __('Payment method description that the customer will see on your checkout.', 'wc-stark-payments'),
                    'default'     => __('Pay with your digital currency, such as bitcoin ethereum via Stark', 'wc-stark-payments'),
                    'desc_tip'    => true,
                ),

                'instructions' => array(
                    'title'       => __('Instructions', 'wc-stark-payments'),
                    'type'        => 'textarea',
                    'description' => __('Instructions that will be added to the thank you page and emails.', 'wc-stark-payments'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
                'api_details'  => array(
                    'title'       => __('API credentials', 'woocommerce'),
                    'type'        => 'title',
                    'description' => sprintf(__('You must enter your store API key below. <a htarget="_blank" href="%s">Login or create a merchant account</a> to obtain a new API key', 'woocommerce'), 'https://dashboard.starkpayments.net/'),
                ),
                'api_key'      => array(
                    'title'       => __('API Key', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Get your API credentials from Stark.', 'woocommerce'),
                    'default'     => '',
                    'desc_tip'    => true,
                ),
            ));

        }

        /**
         * Process the payment and return the result
         *
         * @access public
         * @param int $order_id
         * @return array
         */
        function process_payment($order_id)
        {
            $order = wc_get_order($order_id);
            if (!$this->get_option('api_key')) {
                wc_add_notice(__('Stark Payment Gateway Error : Please provide Stark API Key'), 'error');
                return array(
                    'result' => 'failure',
                );
            }

            $data = array(
                'amount'      => $order->get_total(),
                'currency'    => $order->get_currency(),
                'description' => get_site_url() . ' > Order #' . $order_id,
                'redirectUrl' => $this->get_return_url($order),
                'reference'   => $order_id,
            );

            $payload = [
                'headers' => [
                    'X-Api-Key'    => $this->get_option('api_key'),
                    'Content-type' => 'application/json',
                ],
                'method'  => 'POST',
                'body'    => json_encode($data),
            ];

            $response = wp_remote_post($this->endpoint, $payload);
            $response = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($response['error'])) {
                wc_add_notice(__('Stark Payment Gateway Error : ' . $response['error']['message']), 'error');
                return array(
                    'result' => 'failure',
                );
            }

            if ($order->get_status() != 'completed' && get_post_meta($order->get_id(), 'Stark payment complete', true) != 'Yes') {
                $order->update_status('pending', 'Customer is being redirected to Stark Payment');
                $order->add_order_note('Stark > A customer is in the process of making a payment via the Stark Payment gateway, please wait for the Transaction to be confirmed');
            }

            update_post_meta($order->get_id(), 'Stark Transaction ID', $response['id']);

            return array(
                'result'   => 'success',
                'redirect' => $response['links']['paymentUrl'],
            );

        }

        /**
         * Check for CoinPayments Webhook Response
         *
         * @access public
         * @return void
         */
        function check_webhook_response()
        {
            @ob_clean();
            if (trim($_SERVER['X-Stark-Webhook-Key']) == $this->get_option('api_key') || trim($_SERVER['HTTP_X_STARK_WEBHOOK_KEY']) == $this->get_option('api_key')) {
                $order = wc_get_order(sanitize_text_field($_POST['reference']));
                if ($order->get_status() != 'completed' && sanitize_text_field($_POST['status']) == 'success') {
                    $order->update_status('completed', 'Order Confirmed by Stark Payment');
                    $order->add_order_note('Stark > Transaction has been confirmed.');
                }
                die("OK");
            } else {
                die("Unauthorized!");
            }
        }

    }

    class WC_Starkpayment extends WC_Gateway_Stark
    {
        public function __construct()
        {
            _deprecated_function('WC_Starkpayment', '1.4', 'WC_Gateway_Stark');
            parent::__construct();
        }
    }
}
