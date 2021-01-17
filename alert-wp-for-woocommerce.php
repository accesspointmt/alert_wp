<?php

require_once __DIR__ .'/vendor/autoload.php';

use APG\APGClient;
use APG\Request;
use APG\TransactionTypes;
use APG\ResultTypes;
/**
 * Plugin Name: Alert Payment Gateway for Woo
 * Plugin URI: ""
 * Description: This integrates the Alert Payment Gateway into WooCommerce.
 * Version: 1.0.0
 * Author: Access Point
 * Developer: Access Point
 * Author URI: https://www.accesspoint.com.mt/
 * License: PL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: alert-payments-woo
 * 
 * WC tested up to: 4.7.1
 * 
 * Class WC_Gateway_Alert file.
 *
 * @package WooCommerce\APG
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) return;

add_action( 'plugins_loaded', 'APG_init', 11 );
add_filter( 'woocommerce_payment_gateways', 'APG_woo_integration');

function APG_init() {
    if( class_exists( 'WC_Payment_Gateway' ) ) {

		//Redirect Handlers
		add_action('parse_request', 'APG_custom_url_handler');	

		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-payment-gateway-alert.php';
		require_once plugin_dir_path( __FILE__ ) . '/includes/alert-checkout-description-fields.php';
	}
}


function APG_woo_integration( $gateways ) {
    $gateways[] = 'WC_Gateway_Alert';
    return $gateways;
}

function APG_custom_url_handler() {
	if($_SERVER["REQUEST_URI"] == '/APG_PAResponse') {
		try {	
			$order = wc_get_order( $_COOKIE["orderid"]);

			$referringUrl = get_home_url()."/checkout";
	
			$paymentGateway = WC()->payment_gateways->payment_gateways()['alert'];
			
			$APGClient = new APGClient($paymentGateway->settings['alert-userid'], $paymentGateway->settings['alert-password'], $referringUrl);
			//Create Post-Checkout Object for ThankYou Page
			$objPARequest = new Request();        
	
			$objPARequest->TransactionReferenceID = sanitize_text_field($_POST['MD']);
			$objPARequest->BankMerchantNo = $paymentGateway->settings['alert-bankmerchantno'];
			$objPARequest->CurrencyCode = "EUR";
			$objPARequest->CurrencyNumber = "978";
			$objPARequest->Recurring = "false";
			$objPARequest->AMEXPurchaseType = null;
			$objPARequest->MerchantReference = $paymentGateway->settings['alert-merchantaccount'];
			$objPARequest->OrderID = $paymentGateway->settings['alert-merchantaccount'];
			$objPARequest->Amount = $order->get_total();
			$objPARequest->TransactionType = TransactionTypes::Tentative;	
			
			$xml = $APGClient->ProcessPATransaction($objPARequest, sanitize_text_field($_POST['PaRes']));
			
			$objPARequest->TransactionReferenceID = (string)$xml->TransactionReferenceID;
	
			if (($xml->Status) && ($xml->Result == ResultTypes::Captured)) {
				WC()->cart->empty_cart();
				$order->payment_complete();
	
				setcookie("orderid", null, time()-3600);
	
				header("Location: ".$paymentGateway->get_return_url($order));
				exit();
			}else{
				echo "The transaction was rejected by the bank. Reason: " .$xml->Message;
			}
	
		} catch (Exception $ex) {
			echo $ex->getMessage();
		}
	}else if($_SERVER["REQUEST_URI"] == '/APG_3DSFlow') {	
		if (!session_id()) {
			session_start();
		}
		
		echo $_SESSION["3ds"];
		//echo esc_html($_SESSION["3ds"]);
		//echo wp_kses($_SESSION["3ds"], array('input ' => array(), 'SCRIPT' => array(), 'form' => array()));
		exit();
	}
 }