<?php
/*
Plugin Name: WooCommerce Paypal Simple
Description: Método de pago Paypal.
Version: 1.0
Author: Orange612
Author URI: https://orange612.com
Text Domain: paypal-simple
*/

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'wc_offline_gateway_init', 11 );
function wc_offline_gateway_init() {

    class WC_Paypal_Simple extends WC_Payment_Gateway {

            public function __construct() {
            $this->id = 'paypal_simple';
            // $this->icon = '';
            $this->has_fields = false;
            $this->title = __('Paypal', 'wc-paypal-simple');
            $this->method_title = __('Paypal Simple', 'wc-paypal-simple');
            $this->method_description = __('Pagos a través de un link de Paypal', 'wc-paypal-simple');

            $this->description = $this->get_option('description');
            $this->instructions = $this->get_option('instructions');

            $this->init_form_fields();
            $this->init_settings();

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_filter('woocommerce_thankyou_order_received_text', array($this, 'thankyou_page'), 10, 2 );
            add_filter( 'woocommerce_available_payment_gateways', array($this, 'o612_payment_gateway_disable_country') );

        }

        public function init_form_fields() {
            global $woocommerce;
            $this->form_fields = array(

                'enabled' => array(
                    'title'   => __( 'Enable/Disable', 'wc-paypal-simple' ),
                    'type'    => 'checkbox',
                    'label'   => __( 'Activar Paypal Simple', 'wc-paypal-simple' ),
                    'default' => 'yes'
                ),

                'description' => array(
                    'title'       => __( 'Descripción', 'wc-paypal-simple' ),
                    'type'        => 'textarea',
                    'description' => __( 'Descripción del método de pago que el cliente verá en su pago.', 'wc-paypal-simple' ),
                    'default'     => '',
                    'required' => true,
                    'desc_tip'    => true,
                ),

                'instructions' => array(
                    'title'       => __( 'Instrucciones', 'wc-paypal-simple' ),
                    'type'        => 'textarea',
                    'description' => __( 'Instrucciones que se agregarán a la página de agradecimiento y a los correos electrónicos.', 'wc-paypal-simple' ),
                    'default'     => '',
                    'desc_tip'    => true,
                    'required' => true,
                ),
            );
        }

        public function process_payment( $order_id ) {

            $order = wc_get_order( $order_id );

            // Mark as on-hold (we're awaiting the payment)
            $order->update_status( 'on-hold', __( 'Esperando pago de paypal', 'wc-paypal-simple' ) );

            // Reduce stock levels
            $order->reduce_order_stock();

            // Remove cart
            WC()->cart->empty_cart();

            // Return thankyou redirect
            return array(
                'result'    => 'success',
                'redirect'  => $this->get_return_url( $order )
            );
        }

        public function thankyou_page() {
            if ( $this->instructions ) {
                echo wpautop( wptexturize( $this->instructions ) );
            }
        }

        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {

            if ( $this->instructions && ! $sent_to_admin && 'offline' === $order->payment_method && $order->has_status( 'on-hold' ) ) {
                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
            }
        }

        function o612_payment_gateway_disable_country( $available_gateways ) {
            if ( is_admin() ) return $available_gateways;
            if ( isset( $available_gateways['paypal_simple'] ) && WC()->customer->get_billing_country() == 'PE' ) {
                unset( $available_gateways['paypal_simple'] );
            } else {
                unset( $available_gateways['bacs'] );
                unset( $available_gateways['cheque'] );
                unset( $available_gateways['cod'] );
            }
            return $available_gateways;
        }


    }
}

function wc_offline_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_Paypal_Simple';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_offline_add_to_gateways' );
