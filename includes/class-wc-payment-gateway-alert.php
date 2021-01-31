<?php
    require_once __DIR__ .'/../vendor/autoload.php';
	require_once __DIR__ .'/../API/config.php';
	require_once __DIR__ .'/alert-payment-result.php';

	use APG\Request;
	use APG\ResultTypes;
	use APG\TransactionAddress;

	/**
	 * Alert Payments Gateway.
	 *
	 * Provides a Alert Payments Payment Gateway.
	 *
	 * @class       WC_Gateway_Alert
	 * @extends     WC_Payment_Gateway
	 * @version     2.1.0
	 * @package     WooCommerce/Classes/Payment
	 */
	class WC_Gateway_Alert extends WC_Payment_Gateway_CC {

		/**
		 * Constructor for the gateway.
		 */
		public function __construct() {
			// Setup general properties.
			$this->setup_properties();

			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();

			// Get settings.
			$this->title = $this->get_option( $this->id.'-title' );
			$this->description = $this->get_option( $this->id.'-description' );
			$this->thankyou_text = $this->get_option( $this->id.'-thankyou_text' );
			$this->userid = $this->get_option( $this->id.'-userid' );
			$this->password = $this->get_option( $this->id.'-password' );
			$this->bankmerchantno = $this->get_option( $this->id.'-bankmerchantno' );
			$this->merchantaccount = $this->get_option( $this->id.'-merchantaccount' );
			$this->merchantguid = $this->get_option( $this->id.'-merchantguid' );
			
			$this->enable_for_methods = $this->get_option( $this->id.'-enable_for_methods', array() );
			$this->enable_for_virtual = $this->get_option( $this->id.'-enable_for_virtual', 'yes' ) === 'yes';

			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			//add_filter( 'woocommerce_payment_complete_order_status', array( $this, 'change_payment_complete_order_status' ), 10, 3 );

			// Customer Emails.
			add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );

		}

		/**
		 * Setup general properties for the gateway.
		 */
		protected function setup_properties() {
			$this->id                 = 'alert';
			$this->icon               = apply_filters( 'woocommerce_alert_icon', plugins_url('../assets/icon.png', __FILE__ ) );
			$this->method_title       = __( 'The Alert Payment Gateway', 'alert-payments-woo' );
			$this->method_description = __( 'Have your customers pay with the Alert Payment Gateway.', 'alert-payments-woo' );
			$this->has_fields         = true;
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = array(
				$this->id.'-title' => array(
					'title'       => 'Title',
					'type'        => 'text',
					'description' => 'This controls the title which the user sees during checkout.',
					'default'     => 'The Alert Payment Gateway',
					'desc_tip'    => true,
				),
				$this->id.'-description' => array(
					'title'       => 'Description',
					'type'        => 'textarea',
					'description' => 'This controls the description which the user sees during checkout.',
					'default'     => 'Pay securely, with the Alert Payment Gateway',
					'desc_tip'    => true,
				),
				$this->id.'-thankyou_text' => array(
					'title'       => __( 'Instructions', 'alert-payments-woo' ),
					'type'        => 'textarea',
					'description' => __( 'Instructions that will be added to the thank you page.', 'alert-payments-woo' ),
					'default'     => __( 'Thank you for using the Alert Payment Gateway!', 'alert-payments-woo' ),
					'desc_tip'    => true,
				),
				$this->id.'-userid' => array(
					'title'       => 'User ID',
					'type'        => 'text',
					'description' => 'This is obtained from the .APG file obtained on account creation.',
					'desc_tip'    => true,
				),
				$this->id.'-password' => array(
					'title'       => 'Password',
					'type'        => 'password',
					'description' => 'This is obtained from the .APG file obtained on account creation.',
					'desc_tip'    => true,
				),
				$this->id.'-bankmerchantno' => array(
					'title'       => 'Bank Merchant Number',
					'type'        => 'text'
				),
				$this->id.'-merchantaccount' => array(
					'title'       => 'Merchant Account',
					'type'        => 'text'
				),
				$this->id.'-merchantguid' => array(
					'title'       => 'Merchant GUID',
					'type'        => 'text'
				),
			);
		}

		/**
		 * Check If The Gateway Is Available For Use.
		 *
		 * @return bool
		 */
		public function is_available() {
			$order          = null;
			$needs_shipping = false;

			// Test if shipping is needed first.
			if ( WC()->cart && WC()->cart->needs_shipping() ) {
				$needs_shipping = true;
			} elseif ( is_page( wc_get_page_id( 'checkout' ) ) && 0 < get_query_var( 'order-pay' ) ) {
				$order_id = absint( get_query_var( 'order-pay' ) );
				$order    = wc_get_order( $order_id );

				// Test if order needs shipping.
				if ( 0 < count( $order->get_items() ) ) {
					foreach ( $order->get_items() as $item ) {
						$_product = $item->get_product();
						if ( $_product && $_product->needs_shipping() ) {
							$needs_shipping = true;
							break;
						}
					}
				}
			}

			$needs_shipping = apply_filters( 'woocommerce_cart_needs_shipping', $needs_shipping );

			// Virtual order, with virtual disabled.
			if ( ! $this->enable_for_virtual && ! $needs_shipping ) {
				return false;
			}

			// Only apply if all packages are being shipped via chosen method, or order is virtual.
			if ( ! empty( $this->enable_for_methods ) && $needs_shipping ) {
				$order_shipping_items            = is_object( $order ) ? $order->get_shipping_methods() : false;
				$chosen_shipping_methods_session = WC()->session->get( 'chosen_shipping_methods' );

				if ( $order_shipping_items ) {
					$canonical_rate_ids = $this->get_canonical_order_shipping_item_rate_ids( $order_shipping_items );
				} else {
					$canonical_rate_ids = $this->get_canonical_package_rate_ids( $chosen_shipping_methods_session );
				}

				if ( ! count( $this->get_matching_rates( $canonical_rate_ids ) ) ) {
					return false;
				}
			}

			return parent::is_available();
		}

		/**
		 * Checks to see whether or not the admin settings are being accessed by the current request.
		 *
		 * @return bool
		 */
		private function is_accessing_settings() {
			if ( is_admin() ) {
				// phpcs:disable WordPress.Security.NonceVerification
				if ( ! isset( $_REQUEST['page'] ) || 'wc-settings' !== $_REQUEST['page'] ) {
					return false;
				}
				if ( ! isset( $_REQUEST['tab'] ) || 'checkout' !== $_REQUEST['tab'] ) {
					return false;
				}
				if ( ! isset( $_REQUEST['section'] ) || 'alert' !== $_REQUEST['section'] ) {
					return false;
				}
				// phpcs:enable WordPress.Security.NonceVerification

				return true;
			}

			return false;
		}

		/**
		 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
		 *
		 * @since  3.4.0
		 *
		 * @param  array $order_shipping_items  Array of WC_Order_Item_Shipping objects.
		 * @return array $canonical_rate_ids    Rate IDs in a canonical format.
		 */
		private function get_canonical_order_shipping_item_rate_ids( $order_shipping_items ) {

			$canonical_rate_ids = array();

			foreach ( $order_shipping_items as $order_shipping_item ) {
				$canonical_rate_ids[] = $order_shipping_item->get_method_id() . ':' . $order_shipping_item->get_instance_id();
			}

			return $canonical_rate_ids;
		}

		/**
		 * Converts the chosen rate IDs generated by Shipping Methods to a canonical 'method_id:instance_id' format.
		 *
		 * @since  3.4.0
		 *
		 * @param  array $chosen_package_rate_ids Rate IDs as generated by shipping methods. Can be anything if a shipping method doesn't honor WC conventions.
		 * @return array $canonical_rate_ids  Rate IDs in a canonical format.
		 */
		private function get_canonical_package_rate_ids( $chosen_package_rate_ids ) {

			$shipping_packages  = WC()->shipping()->get_packages();
			$canonical_rate_ids = array();

			if ( ! empty( $chosen_package_rate_ids ) && is_array( $chosen_package_rate_ids ) ) {
				foreach ( $chosen_package_rate_ids as $package_key => $chosen_package_rate_id ) {
					if ( ! empty( $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ] ) ) {
						$chosen_rate          = $shipping_packages[ $package_key ]['rates'][ $chosen_package_rate_id ];
						$canonical_rate_ids[] = $chosen_rate->get_method_id() . ':' . $chosen_rate->get_instance_id();
					}
				}
			}

			return $canonical_rate_ids;
		}

		/**
		 * Indicates whether a rate exists in an array of canonically-formatted rate IDs that activates this gateway.
		 *
		 * @since  3.4.0
		 *
		 * @param array $rate_ids Rate ids to check.
		 * @return boolean
		 */
		private function get_matching_rates( $rate_ids ) {
			// First, match entries in 'method_id:instance_id' format. Then, match entries in 'method_id' format by stripping off the instance ID from the candidates.
			return array_unique( array_merge( array_intersect( $this->enable_for_methods, $rate_ids ), array_intersect( $this->enable_for_methods, array_unique( array_map( 'wc_get_string_before_colon', $rate_ids ) ) ) ) );
		}

		/**
		 * Process the payment and return the result.
		 *
		 * @param int $order_id Order ID.
		 * @return array
		 */
		public function process_payment( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( $order->get_total() > 0 ) {
				$state = $this->alert_payment_processing($order);
			} else {
				$order->payment_complete();
			}

			if($state->result == ResultTypes::Captured){
				// Remove cart.
				WC()->cart->empty_cart();

				$order->payment_complete();

				// Return thankyou redirect.
				return array(
					'result'   => 'success',
					'redirect' => $this->get_return_url( $order ), 
				);
			}else{
				if($state->result == ResultTypes::Enrolled3DS2Challenge){
					//TODO to be contined
					wc_add_notice('Transaction Failed! - 3DS2 to be supported soon');  
				}else if($state->result == ResultTypes::Enrolled){
					return array(
						'result'   => 'success',
						//'redirect' =>  plugins_url('../API/Redirects/3DSFlow.php', __FILE__ ),
						'redirect' => get_site_url(null,"APG_3DSFlow") 
					);
				}else{
					wc_add_notice('Transaction Failed! - '. $state->result, 'error');  
				}
			}
		}

		private function alert_payment_processing($order) {

			$request = new Request();
			$alertPaymentResult = new Alert_Payment_Result();

			$request->CardBrand = sanitize_text_field($_POST['alert-card-brand']);
			
			$request->CardNumber = sanitize_text_field(str_replace(" ", "", $_POST['alert-card-number']));
			$request->CVV2 = sanitize_text_field($_POST['alert-card-cvc']);

			$date = sanitize_text_field(str_replace(" ", "", $_POST['expiry_year']));
			$date = explode("/",$date);

			$request->ExpiryYear = $date[1];
			$request->ExpiryMonth = $date[0];
			$request->CardHolder = sanitize_text_field($_POST['alert-card-holder']);

			$request->Amount = $order->get_total();
			
			if($order->has_billing_address()){
				$address = new TransactionAddress();
	
				$address->ID = 0;
				$address->TransactionID = 0;
				$address->AddressTypeID = 0;
				$address->Address1 = $order->get_billing_address_1();
				$address->Address2 = $order->get_billing_address_2();
				$address->Address3 = '';
				$address->City = $order->get_billing_city();
				$address->PostalCode = $order->get_billing_postcode();
				$address->State = $order->get_billing_state();
				$address->CountryCode = $order->get_billing_country();	
			}

			$request->ShippingAddress = $address;
			$request->BillingAddress = $address;

			$customerIp = $_SERVER['REMOTE_ADDR']; 
			$acceptHeader = $_SERVER['HTTP_ACCEPT']; 
			
			$request->BrowserDataString = $_POST['hnfJSBrowserData'].'|'.$customerIp.'|'.$acceptHeader;	

			$xml = MakePurchase($request);

			$request->TransactionReferenceID = (string)$xml->TransactionReferenceID;
			$request->ServerTransactionID = (string)$xml->ServerTransactionID;

			if (($xml->Status) && ($xml->Result == ResultTypes::Enrolled)) {
				
				//setcookie("3ds", (string)$xml->VbVPostHTML, strtotime( '+30 days' ),"/");

				if (!session_id()) {
					session_start();
				}

				$_SESSION["3ds"] = (string)$xml->VbVPostHTML;
				setcookie("orderid", (string)$order->get_id(),strtotime( '+30 days' ),"/");

				$alertPaymentResult->result = ResultTypes::Enrolled;

			}else if ($xml->Result == ResultTypes::Enrolled3DS2){
				$xml3DS2 = Start3DS2($request);	

				if($xml3DS2->Result == ResultTypes::Captured){
					
					$alertPaymentResult->result = ResultTypes::Captured;

				}else if($xml3DS2->Result == ResultTypes::Enrolled3DS2Challenge){
					//TODO update after server side fixes
					//$_SESSION["request"] = $request;
					$alertPaymentResult->result = "3DS2 is not yet supported";
				}else{
					
					$alertPaymentResult->result = $xml3DS2->Message . ' | ' . $xml3DS2->Result;
				}
			}else if($xml->Result == ResultTypes::Enrolled3DS2Challenge){
				//TODO update after server side fixes
				//$_SESSION["request"] = $request;
				$alertPaymentResult->result = "3DS2 is not yet supported";
				return ResultTypes::Enrolled3DS2Challenge;

			} else if ($xml->Result == ResultTypes::NotEnrolled){
				
				$alertPaymentResult->result = "The credit card details you provided were not allowed.";
				
			}else if ($xml->Result == ResultTypes::Unavailable){
			
				$alertPaymentResult->result = "The credit card details you provided could not be checked by the bank.";
				
			} else if ($xml->Result == ResultTypes::Captured){
				
				$alertPaymentResult->result = ResultTypes::Captured;
				
			}else {	
				
				$alertPaymentResult->result = json_encode($xml);
					
			}

			return $alertPaymentResult;	
		}

		/**
		 * Output for the order received page.
		 */
		public function thankyou_page() {
			if ( $this->thankyou_text ) {
				echo wp_kses_post( wpautop( wptexturize( $this->thankyou_text ) ) );
			}
		}

		/**
		 * Add content to the WC emails.
		 *
		 * @param WC_Order $order Order object.
		 * @param bool     $sent_to_admin  Sent to admin.
		 * @param bool     $plain_text Email format: plain text or HTML.
		 */
		public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
			if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() ) {
				echo wp_kses_post( wpautop( wptexturize( $this->instructions ) ) . PHP_EOL );
			}
		}

		function PreparePOSTForm($url,$MethodName, $MethodData, $targetIframe, $asyncMethod, $sessionData){
			//Build the JavaScript which will do the Posting operation
			$strScript = '';//'<script language="javascript" type="text/javascript">';

        	//If the javascript should not wait for the response, the asyncMethod should be set to true
			If ($asyncMethod == true){
				$ua = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
				if (preg_match('~MSIE|Internet Explorer~i', $ua) || (strpos($ua, 'Trident/7.0') !== false && strpos($ua, 'rv:11.0') !== false)) {
					$strScript .= 'function setIframeSrc(){ ';
				}else{
					$strScript .= 'async function setIframeSrc(){ ';
				}
			}

			$strScript .= 'var form = document.createElement("form");';
			$strScript .= 'form.setAttribute("method","POST");';
			$strScript .= 'form.setAttribute("action","' .$url .'");';
			$strScript .= 'form.setAttribute("target","' .$targetIframe .'");';
			$strScript .= 'var txtMethodData = document.createElement("input");';
			$strScript .= 'txtMethodData.setAttribute("type","hidden");';
			$strScript .= 'txtMethodData.setAttribute("name","' .$MethodName .'");';
			$strScript .= 'txtMethodData.setAttribute("value","' .$MethodData .'");';
			$strScript .= 'form.appendChild(txtMethodData);';

			If ($sessionData <> ''){
				$strScript .= $sessionData;
			}

			$strScript .= 'document.body.appendChild(form);';
			$strScript .= 'form.submit();';

			If ($asyncMethod == true){
				$strScript .= '}; setIframeSrc();';
			}
			
			$strScript .= '';

			Return $strScript;	
		}
	}