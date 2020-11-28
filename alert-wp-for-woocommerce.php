<?php
/**
 * Plugin Name: WooCommerce Alert Payment Gateway
 * Plugin URI: ""
 * Description: This integrates the Alert Payment Gateway into WooCommerce.
 * Version: 1.0.0
 * Author: Access Point
 * Author URI: https://www.accesspoint.com.mt/
 * License: PL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: alert-payments-woo
 * 
 * Class WC_Gateway_Alert file.
 *
 * @package WooCommerce\APG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'alert_payment_init', 11 );
//add_filter( 'woocommerce_currencies', 'techiepress_add_ugx_currencies' );
//add_filter( 'woocommerce_currency_symbol', 'techiepress_add_ugx_currencies_symbol', 10, 2 );
add_filter( 'woocommerce_payment_gateways', 'add_to_woo_alert_payment_gateway');

function alert_payment_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-alert.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/alert-order-statuses.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/alert-checkout-description-fields.php';
	}
}

function add_to_woo_alert_payment_gateway( $gateways ) {
    $gateways[] = 'WC_Gateway_Alert';
    return $gateways;
}


/*function techiepress_add_ugx_currencies( $currencies ) {
	$currencies['UGX'] = __( 'Ugandan Shillings', 'alert-payments-woo' );
	return $currencies;
}

function techiepress_add_ugx_currencies_symbol( $currency_symbol, $currency ) {
	switch ( $currency ) {
		case 'UGX': 
			$currency_symbol = 'UGX'; 
		break;
	}
	return $currency_symbol;
}*/