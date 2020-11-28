<?php
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	error_reporting(E_ALL);

	require_once __DIR__ . '/../../../../../wp-load.php';
	require_once __DIR__ .'/../../vendor/autoload.php';

	use APG\APGClient;
	use APG\Request;
	use APG\TransactionTypes;
	use APG\ResultTypes;

	try {	
		$order = wc_get_order( $_COOKIE["orderid"]);
        $referringUrl = get_home_url()."/checkout";

		$paymentGateway = WC()->payment_gateways->payment_gateways()['alert'];
		
        $APGClient = new APGClient($paymentGateway->settings['alert-userid'], $paymentGateway->settings['alert-password'], $referringUrl);
		//Create Post-Checkout Object for ThankYou Page
		$objPARequest = new Request();        

		$objPARequest->TransactionReferenceID = $_POST['MD'];
		$objPARequest->BankMerchantNo = $paymentGateway->settings['alert-bankmerchantno'];
        $objPARequest->CurrencyCode = "EUR";
        $objPARequest->CurrencyNumber = "978";
		$objPARequest->Recurring = "false";
		$objPARequest->AMEXPurchaseType = null;
		$objPARequest->MerchantReference = $paymentGateway->settings['alert-merchantaccount'];
		$objPARequest->OrderID = $paymentGateway->settings['alert-merchantaccount'];
		$objPARequest->Amount = $order->get_total();
		$objPARequest->TransactionType = TransactionTypes::Tentative;	
		
		$xml = $APGClient->ProcessPATransaction($objPARequest, $_POST['PaRes']);
		
		$objPARequest->TransactionReferenceID = (string)$xml->TransactionReferenceID;

		if (($xml->Status) && ($xml->Result == ResultTypes::Captured)) {
			WC()->cart->empty_cart();
			$order->payment_complete();

			setcookie("3ds", null);

			header("Location: ".$paymentGateway->get_return_url($order));
			exit();
		}else{
			echo "The transaction was rejected by the bank. Reason: " .$xml->Message;
		}

	} catch (Exception $ex) {
		echo $ex->getMessage();
	}
?>