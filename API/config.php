<?php
    require_once __DIR__ .'/../vendor/autoload.php';

    use APG\APGClient;
    use APG\Request;
    use APG\TransactionTypes;

    /*function SendTentative($amount){
    
        $referringUrl = get_home_url()."/checkout";

        $paymentGateway = WC()->payment_gateways->payment_gateways()['alert'];

        $PaymentDetails = new Request();
        $PaymentDetails->MerchantReference = $paymentGateway->settings['alert-merchantaccount'];
        $PaymentDetails->Amount = $amount;
        $PaymentDetails->CurrencyCode = "EUR";
        $PaymentDetails->CurrencyNumber = "978";
        $PaymentDetails->BankMerchantNo = $paymentGateway->settings['alert-bankmerchantno'];
        $PaymentDetails->ApplicationGUID = $paymentGateway->settings['alert-merchantguid'];
        $PaymentDetails->Recurring = "false";
        
        $APGClient = new APGClient($paymentGateway->settings['alert-userid'], $paymentGateway->settings['alert-password'], $referringUrl);

        $PaymentDetails->AMEXPurchaseType = null;
        $PaymentDetails->TransactionType = TransactionTypes::Tentative;

        return $APGClient->ProcessTransaction($PaymentDetails, false);
    }*/

    function MakePurchase($request){
        $referringUrl = get_home_url()."/checkout";

        $paymentGateway = WC()->payment_gateways->payment_gateways()['alert'];

        $request->MerchantReference = $paymentGateway->settings['alert-merchantaccount'];
        $request->CurrencyCode = "EUR";
        $request->CurrencyNumber = "978";
        $request->BankMerchantNo = $paymentGateway->settings['alert-bankmerchantno']; //Changed this
        //$request->TerminalURL = plugins_url('../PAResponse', __FILE__ ) ;
        $request->TerminalURL = get_site_url(null,"PAResponse") ;
        $request->ApplicationGUID = $paymentGateway->settings['alert-merchantguid']; //Changed this
        $request->ChallengeNotificationUrl = plugins_url('../API/Redirects/ACSChallengeHandler.php', __FILE__ ) ;
        $request->Recurring = "false";

        $request->AMEXPurchaseType = null;
        $request->TransactionType = TransactionTypes::Purchase;

        $APGClient = new APGClient($paymentGateway->settings['alert-userid'], $paymentGateway->settings['alert-password'], $referringUrl);

        return $APGClient->ProcessVbVTransaction($request, $request->TerminalURL);
    }

    function GetCardBrands(){
        
        $referringUrl = get_home_url()."/checkout";

        $paymentGateway = WC()->payment_gateways->payment_gateways()['alert'];

        $APGClient = new APGClient($paymentGateway->settings['alert-userid'], $paymentGateway->settings['alert-password'], $referringUrl);

        $xml = $APGClient->GetAllowedCardBrands($paymentGateway->settings['alert-bankmerchantno']);

        $array = array();

        for($i = 0; $i < count($xml); $i++){
            $array[strval($xml[$i]->{"CODE"})] = strval($xml[$i]->{"DESCRIPTION"});
        }
    
        return $array;
    }

    function Start3DS2($request){
        $referringUrl = get_home_url()."/checkout";

        $paymentGateway = WC()->payment_gateways->payment_gateways()['alert'];

        $APGClient = new APGClient($paymentGateway->settings['alert-userid'], $paymentGateway->settings['alert-password'], $referringUrl);

        return $APGClient->Initiate3DSecure2($request);
    }

    function GetJSBrowserData(){
        $referringUrl = get_home_url()."/checkout";

        $paymentGateway = WC()->payment_gateways->payment_gateways()['alert'];

        $APGClient = new APGClient($paymentGateway->settings['alert-userid'], $paymentGateway->settings['alert-password'], $referringUrl);

        return $APGClient->GetJSBrowserData();
    }
