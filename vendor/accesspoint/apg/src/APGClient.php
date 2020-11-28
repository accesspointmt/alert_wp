<?php
    namespace APG;

    mb_internal_encoding("UTF-8");
    require_once 'APGClient-config.php';

    class Params {
        public $RequestValue;
        public $PaRes;
        public $BankMerchantID;
        public $MerchantBankID;
        public $strTerminalURL;
        public $CardDetails;
        public $MerchantDetails;
        public $CardGuid;
        public $CardGuids;
        public $BankMerchantNo;
        public $MerchantBankIDs;
        public $ApplicationGuid;
        public $TransactionReferenceID;
        public $CardStoredFor;
        public $RootTransactionID;
    }

    class Authorization{
        public $UserID;
        public $Password;
        public $ReferringURL;
    }

    class CardDetails {
        public $CardHolder;
        public $CardNumber;
        public $CardBrand;
        public $CardExpiryMonth;
        public $CardExpiryYear;
        public $CVV;
        public $CardGuid;
    }

    class MerchantDetails {
        public $PublicKeyID;
        public $Authorization;
        public $ApplicationGUID;
        public $BankMerchantNo;
        public $MerchantReference;
    }

    class BankMerchantIDs {
        public $string;
    }

    class CardGuids {
        public $guid;
    }

    class Request {
        public $KeyID;
        public $TransactionReferenceID;
        public $MerchantReference;
        public $BankMerchantNo;
        public $BankAlias;
        public $TransactionType;
        public $CardBrand;
        public $CurrencyNumber;
        public $CurrencyCode;
        public $Amount;
        public $CardNumber;
        public $CVV2;
        public $ExpiryYear;
        public $ExpiryMonth;
        public $ExpiryDay;
        public $CardHolder;
        public $Address;
        public $Postcode;
        public $UserDefinedField1;
        public $UserDefinedField2;
        public $UserDefinedField3;
        public $UserDefinedField4;
        public $UserDefinedField5;
        public $ClientIP;
        public $AMEXPurchaseType;
        public $Recurring;
        public $ServerTransactionID;
        public $BillingAddress;
        public $ShippingAddress;
        public $BrowserDataString;
        public $browserData;
        public $ChallengeNotificationUrl;
    }

    class TransactionTypes {
        const Authorization = "Authorization";
        const VoidAuthorization = "VoidAuthorization";
        const Capture = "Capture";
        const VoidCapture = "VoidCapture";
        const Purchase = "Purchase";
        const VoidPurchase = "VoidPurchase";
        const Credit = "Credit";
        const VoidCredit = "VoidCredit";
        const Tentative = "Tentative";
    }

    class ResultTypes {
        const Captured = "Captured";
        const Approved = "Approved";
        const Voided = "Voided";
        const NotCaptured = "NotCaptured";
        const NotApproved = "NotApproved";
        const NotVoided = "NotVoided";
        const BankConnectionTimeout = "BankConnectionTimeout";
        const BankRiskRejected = "BankRiskRejected";
        const InProgress = "InProgress";
        const BankRejected = "BankRejected";
        const Reconciled = "Reconciled";
        const NotReconciled = "NotReconciled";
        const ReSubmitted = "ReSubmitted";
        const NotReSubmitted = "NotReSubmitted";
        const Enrolled = "Enrolled";
        const NotEnrolled = "NotEnrolled";
        const Unavailable = "Unavailable";
        const Enrolled3DS2 = "Enrolled3DS2";
        const Enrolled3DS2Challenge = "Enrolled3DS2Challenge";
    }

    Class TransactionAddress {
        public $ID;
        public $TransactionID;
        public $AddressTypeID;
        public $Address1;
        public $Address2;
        public $Address3;
        public $City;
        public $PostalCode;
        public $State;
        public $CountryCode;
    }


    class APGClient {

        public $APGClientConfiguration;
        public $referringURL;
        public $ClientIP;
        public $soap;


        public function __construct()
        {
            $arguments = func_get_args();
            $numberOfArguments = func_num_args();
    
            if (method_exists($this, $function = '__construct'.$numberOfArguments)) {
                call_user_func_array(array($this, $function), $arguments);
            }
        }


        function __construct2($secureAPGPath, $referringURL)
        {
            $this->referringURL = $referringURL;
            $this->ClientIP = $_SERVER["REMOTE_ADDR"];
            $this->APGClientConfiguration = new \APGClientConfiguration($secureAPGPath);

            $this->soap = @new \SoapClient($this->APGClientConfiguration->WSDL, array('trace' => 1));

            /* set the Headers of Soap Client. */
            $this->soap->__setSoapHeaders($this->GetSoapHeader());
        }

        function __construct3($secureAPGUserName,$secureAPGPassword, $referringURL)
        {
            $this->referringURL = $referringURL;
            $this->ClientIP = $_SERVER["REMOTE_ADDR"];
            $this->APGClientConfiguration = new \APGClientConfiguration();

            $this->soap = @new \SoapClient($this->APGClientConfiguration->WSDL, array('trace' => 1));

            /* set the Headers of Soap Client. */
            $this->soap->__setSoapHeaders($this->GetSoapHeaderDirect($secureAPGUserName,$secureAPGPassword));
        }

        /* <summary>
        * Raise a transaction with supplied Request for current user account.
        * </summary>
        */

        public function ProcessTransaction($objRequest, $CardGuid=null, $ApplicationGuid=null) {

            try {
                //Get Key ID from Init.
                $resultNode = $this->GetInitToken();

                //Set additional values for request
                $objRequest->KeyID = (string) $resultNode->KeyID;

                if($CardGuid == null && $ApplicationGuid == null && $objRequest->CardNumber != null){
                    $objRequest->CardNumber = $this->EncryptString($objRequest->CardNumber, $resultNode->Key);
                    $objRequest->CVV2 = $this->EncryptString($objRequest->CVV2, $resultNode->Key);
                    $objRequest->ExpiryYear = $this->EncryptString($objRequest->ExpiryYear, $resultNode->Key);
                    $objRequest->ExpiryMonth = $this->EncryptString($objRequest->ExpiryMonth, $resultNode->Key);
                    $objRequest->ExpiryDay = $this->EncryptString($objRequest->ExpiryDay, $resultNode->Key);
                    $objRequest->CardHolder = $this->EncryptString($objRequest->CardHolder, $resultNode->Key);
                }
                
                $objRequest->ClientIP = $this->ClientIP;
                
                //Prepare request
                $params = new Params();
                $params->RequestValue = $objRequest;

                if($CardGuid != null && $ApplicationGuid != null){
                    $params->ApplicationGuid = $ApplicationGuid;
                    $params->CardGuid = $CardGuid;
                }

                //Make request to ProcessTransaction method
                if($CardGuid != null && $ApplicationGuid != null){
                    $this->soap->ProcessCardTransaction($params);
                }else{
                    $this->soap->ProcessTransaction($params);
                }

                $xml = @simplexml_load_string($this->soap->__getLastResponse());

                $parentNode = $xml->xpath("soap:Body");

                if($CardGuid != null && $ApplicationGuid != null){
                    $resultNode = $parentNode[0]->ProcessCardTransactionResponse[0]->ProcessCardTransactionResult[0];
                }else{
                    $resultNode = $parentNode[0]->ProcessTransactionResponse[0]->ProcessTransactionResult[0];
                }

                //return result
                return $resultNode;

            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");

            }
        }

        /* <summary>
        * Process VbV transaction
        * </summary>
        */

        public function ProcessVbVTransaction($objRequest, $strTerminalURL,$ApplicationGuid=null, $CardGuid=null) {

            try {

                $resultNode = $this->GetInitToken();

                $objRequest->KeyID = (string) $resultNode->KeyID;

                if($CardGuid == null && $ApplicationGuid == null && $objRequest->CardNumber != null){
                    $objRequest->CardNumber = $this->EncryptString($objRequest->CardNumber, $resultNode->Key);
                    $objRequest->CVV2 = $this->EncryptString($objRequest->CVV2, $resultNode->Key);
                    $objRequest->ExpiryYear = $this->EncryptString($objRequest->ExpiryYear, $resultNode->Key);
                    $objRequest->ExpiryMonth = $this->EncryptString($objRequest->ExpiryMonth, $resultNode->Key);
                    $objRequest->ExpiryDay = $this->EncryptString($objRequest->ExpiryDay, $resultNode->Key);
                    $objRequest->CardHolder = $this->EncryptString($objRequest->CardHolder, $resultNode->Key);
                }

                $objRequest->BrowserDataString = $this->EncryptString($objRequest->BrowserDataString, $resultNode->Key);
                $objRequest->ClientIP = $this->ClientIP;
                
                $params = new Params();
                $params->RequestValue = $objRequest;
                $params->strTerminalURL = $strTerminalURL;
                
                if($CardGuid != null && $ApplicationGuid != null){
                    $params->ApplicationGuid = $ApplicationGuid;
                    $params->CardGuid = $CardGuid;
                }

                if($CardGuid != null && $ApplicationGuid != null){
                    $this->soap->ProcessCardVbVTransaction($params);
                }else{
                    $this->soap->ProcessVbVTransaction($params);
                }

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");

                if($CardGuid != null && $ApplicationGuid != null){
                    $resultNode = $parentNode[0]->ProcessCardVbVTransactionResponse[0]->ProcessCardVbVTransactionResult[0];
                }else{
                    $resultNode = $parentNode[0]->ProcessVbVTransactionResponse[0]->ProcessVbVTransactionResult[0];
                }

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");
            }
        }

        /* <summary>
        * Process PA transaction
        * </summary>
        */

        public function ProcessPATransaction($objRequest, $PaRes,$ApplicationGuid=null) {

            try {

                $resultNode = $this->GetInitToken();
                $objRequest->KeyID = (string) $resultNode->KeyID;

                if($ApplicationGuid == null && $objRequest->CardNumber != null){
                    $objRequest->CardNumber = $this->EncryptString($objRequest->CardNumber, $resultNode->Key);
                    $objRequest->CVV2 = $this->EncryptString($objRequest->CVV2, $resultNode->Key);
                    $objRequest->ExpiryYear = $this->EncryptString($objRequest->ExpiryYear, $resultNode->Key);
                    $objRequest->ExpiryMonth = $this->EncryptString($objRequest->ExpiryMonth, $resultNode->Key);
                    $objRequest->ExpiryDay = $this->EncryptString($objRequest->ExpiryDay, $resultNode->Key);
                    $objRequest->CardHolder = $this->EncryptString($objRequest->CardHolder, $resultNode->Key);
                }
                //$objRequest->TransactionReferenceID = $lngTransactionHistoryID;
                $objRequest->ClientIP = $this->ClientIP;

                $params = new Params();
                $params->RequestValue = $objRequest;
                $params->PaRes = $PaRes;
                if($ApplicationGuid != null){
                    $params->ApplicationGuid = $ApplicationGuid;
                }

                if($ApplicationGuid != null){
                    $this->soap->ProcessCardPATransaction($params);
                }else{
                    $this->soap->ProcessPATransaction($params);
                }


                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");

                
                if($ApplicationGuid != null){
                    $resultNode = $parentNode[0]->ProcessCardPATransactionResponse[0]->ProcessCardPATransactionResult[0];
                }else{
                    $resultNode = $parentNode[0]->ProcessPATransactionResponse[0]->ProcessPATransactionResult[0];
                }

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");
            }
        }

        /*<summary>
        * Get info on an existing transaction
        * </summary>
        */

        public function GetTransaction($TransactionReferenceID) {

            try {

                $params = new Params();
                $params->TransactionReferenceID = $TransactionReferenceID;

                $this->soap->GetTransaction($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->GetTransactionResponse[0]->GetTransactionResult[0];
                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to retrieve transaction.");
            }
        }


        //TODO yet to work
        public function GetTransactionHistory($RootTransactionID, $BankMerchantNo) {

            try {

                $params = new Params();
                $params->RootTransactionID = $RootTransactionID;
                $params->BankMerchantNo = $BankMerchantNo;

                $this->soap->GetTransactionHistory($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->GetTransactionHistoryResponse[0]->GetTransactionHistoryResult[0];
                return $resultNode;
            } catch (\Exception $ex) {
                print_r($ex);
                throw new \Exception("Unable to retrieve transaction.");
            }
        }

        /* <summary>
        * Gets supported card brands for Merchant Bank Account
        * </summary>
        * <param name="BankMerchantID">Merchant's Bank ID</param>
        */

        function GetAllowedCardBrands($BankMerchantID) {

            try {

                $params = new Params();
                $params->BankMerchantID = $BankMerchantID;
                $this->soap->GetAllowedCardBrands($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse())->xpath("//Card");
                
                return $xml;
                
            } catch (\Exception $ex) {
                throw new \Exception("Unable to retrieve cards.");
            }
        }

        /* <summary>
        * Gets supported transaction types for Merchant Bank Account
        * </summary>
        * <param name="BankMerchantID">Merchant's Bank ID</param>
        */

        public function GetAllowedTransactions($BankMerchantID) {

            try {

                $params = new Params();
                $params->BankMerchantID = $BankMerchantID;

                $this->soap->GetAllowedTransactions($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse())->xpath("//TransactionType");

                return $xml;
            } catch (\Exception $ex) {
                print_r($ex);
                throw new \Exception("Unable to retrieve allowed transactions.");
            }
        }

        /* <summary>
        * Gets the application details for this merchant
        * </summary>
        */

        public function GetApplicationDetails($MerchantBankID, $AppGUID) {

            try {

                $params = new Params();
                $params->MerchantBankID = $MerchantBankID;
                $params->ApplicationGuid = $AppGUID;

                $this->soap->GetApplicationDetails($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());

                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->GetApplicationDetailsResponse[0]->GetApplicationDetailsResult[0];

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to retrieve Application Details.");
            }
        }

        /* <summary>
        * Gets the currency supported by a Merchant Bank Account
        * </summary>
        * <param name="BankMerchantID">Merchant's Bank ID</param>
        */

        function GetSupportedCurrency($BankMerchantID) {

            try {

                $params = new Params();
                $params->BankMerchantID = $BankMerchantID;
                $this->soap->GetSupportedCurrency($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->GetSupportedCurrencyResponse[0]->GetSupportedCurrencyResult[0];
                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to retrieve currency.");
            }
        }

        function AddCard($objRequest, $AppGUID, $BankMerchantNo, $CardStoredFor=null) {
            try {

                $resultNode = $this->GetInitToken();


                //$objRequest->KeyID = (string) $resultNode->KeyID;

                $cardDetails = new CardDetails();
                $cardDetails->CardHolder = $this->EncryptString($objRequest->CardHolder, $resultNode->Key);
                $cardDetails->CardNumber = $this->EncryptString($objRequest->CardNumber, $resultNode->Key);
                $cardDetails->CardBrand = $this->EncryptString($objRequest->CardBrand, $resultNode->Key);
                $cardDetails->CardExpiryMonth = $this->EncryptString($objRequest->ExpiryMonth, $resultNode->Key);
                $cardDetails->CardExpiryYear = $this->EncryptString($objRequest->ExpiryYear, $resultNode->Key);
                $cardDetails->CVV = $this->EncryptString($objRequest->CVV2, $resultNode->Key);

                $xml = @simplexml_load_file($this->APGClientConfiguration->secureDOTapgPath);

                $authorization = new Authorization();
                $authorization->UserID = $xml->UserID;
                $authorization->Password = $xml->Password;
                $authorization->ReferringURL = $this->referringURL;


                $merchantDetails = new MerchantDetails();
                $merchantDetails->PublicKeyID = (string) $resultNode->KeyID;
                $merchantDetails->Authorization = $authorization;
                $merchantDetails->ApplicationGUID = $AppGUID;
                $merchantDetails->BankMerchantNo = $BankMerchantNo;
                $merchantDetails->MerchantReference = $objRequest->MerchantReference;

                $params = new Params();
                $params->CardDetails = $cardDetails;
                $params->MerchantDetails = $merchantDetails;

                if($CardStoredFor != null){
                    $params->CardStoredFor = $CardStoredFor;
                }

                $this->soap->AddCard($params);

                echo "<br/>";
                echo "<textarea>".$this->soap->__getLastRequest()."</textarea>";
                echo "<br/>";
                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->AddCardResponse[0]->AddCardResult[0];

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");
            }
        }

        function RemoveCard($objRequest, $AppGUID, $CardGuid) {
            try {

                $resultNode = $this->GetInitToken();

                /*$objRequest->CardNumber = $this->EncryptString($objRequest->CardNumber, $resultNode->Key);
                $objRequest->CardHolder = $this->EncryptString($objRequest->CardHolder, $resultNode->Key);
                $objRequest->CardBrand = $this->EncryptString($objRequest->CardBrand, $resultNode->Key);
                $objRequest->CVV2 = $this->EncryptString($objRequest->CVV2, $resultNode->Key);
                $objRequest->ExpiryYear = $this->EncryptString($objRequest->ExpiryYear, $resultNode->Key);
                $objRequest->ExpiryMonth = $this->EncryptString($objRequest->ExpiryMonth, $resultNode->Key);
                $objRequest->ExpiryDay = $this->EncryptString($objRequest->ExpiryDay, $resultNode->Key);*/

                $xml = @simplexml_load_file($this->APGClientConfiguration->secureDOTapgPath);

                $authorization = new Authorization();
                $authorization->UserID = $xml->UserID;
                $authorization->Password = $xml->Password;
                $authorization->ReferringURL = $this->referringURL;

                $merchantDetails = new MerchantDetails();
                $merchantDetails->PublicKeyID = (string) $resultNode->KeyID;
                $merchantDetails->Authorization = $authorization;
                $merchantDetails->ApplicationGUID = $AppGUID;
                $merchantDetails->BankMerchantNo = $objRequest->BankMerchantNo;

                $params = new Params();
                $params->MerchantDetails = $merchantDetails;
                $params->CardGuid = $CardGuid;
                $params->

                $this->soap->RemoveCard($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->RemoveCardResponse[0]->RemoveCardResult[0];

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");
            }
        }

        function UpdateCard($objRequest, $AppGUID, $CardGuid, $CardStoredFor) {
            try {

                $resultNode = $this->GetInitToken();

                /*$objRequest->CardNumber = $this->EncryptString($objRequest->CardNumber, $resultNode->Key);
                $objRequest->CardHolder = $this->EncryptString($objRequest->CardHolder, $resultNode->Key);
                $objRequest->CardBrand = $this->EncryptString($objRequest->CardBrand, $resultNode->Key);
                $objRequest->CVV2 = $this->EncryptString($objRequest->CVV2, $resultNode->Key);
                $objRequest->ExpiryYear = $this->EncryptString($objRequest->ExpiryYear, $resultNode->Key);
                $objRequest->ExpiryMonth = $this->EncryptString($objRequest->ExpiryMonth, $resultNode->Key);
                $objRequest->ExpiryDay = $this->EncryptString($objRequest->ExpiryDay, $resultNode->Key);*/

                $xml = @simplexml_load_file($this->APGClientConfiguration->secureDOTapgPath);

                $authorization = new Authorization();
                $authorization->UserID = $xml->UserID;
                $authorization->Password = $xml->Password;
                $authorization->ReferringURL = $this->referringURL;

                $cardDetails = new CardDetails();
                $cardDetails->CardGuid = $CardGuid;
                $cardDetails->CardHolder = $this->EncryptString($objRequest->CardHolder, $resultNode->Key);
                $cardDetails->CardNumber = $this->EncryptString($objRequest->CardNumber, $resultNode->Key);
                $cardDetails->CardBrand = $this->EncryptString($objRequest->CardBrand, $resultNode->Key);
                $cardDetails->CardExpiryMonth = $this->EncryptString($objRequest->ExpiryMonth, $resultNode->Key);
                $cardDetails->CardExpiryYear = $this->EncryptString($objRequest->ExpiryYear, $resultNode->Key);
                $cardDetails->CVV = $this->EncryptString($objRequest->CVV2, $resultNode->Key);

                $merchantDetails = new MerchantDetails();
                $merchantDetails->PublicKeyID = (string) $resultNode->KeyID;
                $merchantDetails->Authorization = $authorization;
                $merchantDetails->ApplicationGUID = $AppGUID;
                $merchantDetails->BankMerchantNo = $objRequest->BankMerchantNo;

                $params = new Params();
                $params->CardDetails = $cardDetails;
                $params->MerchantDetails = $merchantDetails;

                if($CardStoredFor != null){
                    $params->CardStoredFor = $CardStoredFor;
                }

                $this->soap->UpdateCard($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->UpdateCardResponse[0]->UpdateCardResult[0];

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");
            }
        }

        function GetCards($MerchantBankIds, $CardGuids, $AppGUID) {
            try {
                $bankMerchantIDs = new BankMerchantIDs();
                $bankMerchantIDs->string = array_filter($MerchantBankIds);

                /*$filename = 'saved_cards.txt';
                $fp = @fopen($filename, 'r');

                if ($fp) {
                    $CardGuid = explode("\r\n", fread($fp, filesize($filename)));
                }*/

                $CardGuids = array_filter($CardGuids);

                $CardGuids = new CardGuids();
                $CardGuids->guid = $CardGuids;

                $params = new Params();
                $params->CardGuids = $CardGuids;
                $params->ApplicationGuid = $AppGUID;
                $params->MerchantBankIDs = $bankMerchantIDs;

                $this->soap->GetCards($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->GetCardsResponse[0]->GetCardsResult[0];

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");
            }
        }

        /*function EnableRecurringTransactions($objRequest, $AppGUID, $CardGuid) {
            try {

                $xml = @simplexml_load_file($this->APGClientConfiguration->secureDOTapgPath);

                $params = new Params();
                $params->BankMerchantNo = $objRequest->BankMerchantNo;
                $params->ApplicationGuid = $AppGUID;
                $params->CardGuid = $CardGuid;
                $params->TransactionReferenceID = $objRequest->TransactionReferenceID;

                $this->soap->EnableRecurringTransactionsForCard($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->EnableRecurringTransactionsForCardResponse[0]->EnableRecurringTransactionsForCardResult[0];

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");
            }
        }

        function DisableRecurringTransactions($objRequest, $AppGUID, $CardGuid) {
            try {
                $xml = @simplexml_load_file($this->APGClientConfiguration->secureDOTapgPath);

                $params = new Params();
                $params->BankMerchantNo = $objRequest->BankMerchantNo;
                $params->ApplicationGuid = $AppGUID;
                $params->CardGuid = $CardGuid;

                $this->soap->DisableRecurringTransactionsForCard($params);

                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->DisableRecurringTransactionsForCardResponse[0]->DisableRecurringTransactionsForCardResult[0];

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to process transaction.");
            }
        }

        /* <summary>
        * Returns the current URL as string
        * </summary>
        */

        /*private function selfURL() {
            if (!isset($_SERVER['REQUEST_URI'])) {
                $serverrequri = $_SERVER['PHP_SELF'];
            } else {
                $serverrequri = $_SERVER['REQUEST_URI'];
            }
            $s = empty($_SERVER["HTTPS"]) ? '' : (($_SERVER["HTTPS"] == "on") ? "s" : "");
            $protocol = $this->strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/") . $s;
            $port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":" . $_SERVER["SERVER_PORT"]);
            return $protocol . "://" . $_SERVER['SERVER_NAME'] . $port . $serverrequri;
        }

        private function strleft($s1, $s2) {
            return substr($s1, 0, strpos($s1, $s2));
        }*/

        /* <summary>
        * Returns the SOAP header the way it's needed.
        * </summary>
        */

        private function GetSoapHeader() {

            $xml = simplexml_load_file($this->APGClientConfiguration->secureDOTapgPath);

            /* Body of the SOAP Header. */
            $headerbody = array('Identification' => 
                array(
                    'UserID' => $xml->UserID,
                    'Password' => $xml->Password,
                    'ReferringURL' => $this->referringURL
                )
            );
            /* Create SOAP Header. */
            return new \SOAPHeader($this->APGClientConfiguration->NameSpace, 'SecureSoapHeader', $headerbody);
        }

        private function GetSoapHeaderDirect($username, $password) {

            /* Body of the SOAP Header. */
            $headerbody = array('Identification' => 
                array(
                    'UserID' => $username,
                    'Password' => $password,
                    'ReferringURL' => $this->referringURL
                )
            );
            /* Create SOAP Header. */
            return new \SOAPHeader($this->APGClientConfiguration->NameSpace, 'SecureSoapHeader', $headerbody);
        }

        private function GetInitToken() {

            try {
                $this->soap->InitToken();

                $xml = @simplexml_load_string($this->soap->__getLastResponse());

                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->InitTokenResponse[0]->InitTokenResult[0];

                return $resultNode;
            } catch (\Exception $ex) {
                throw new \Exception("Unable to retrieve InitToken. " .$ex);
            }
        }

        public function EncryptString($bytText, $xmlkey) {
            $rsa = new \phpseclib\Crypt\RSA();
            $xml = new \DOMDocument();
            $xml->loadXML($xmlkey);

            $decodedModulus = base64_decode($xml->getElementsByTagName('Modulus')->item(0)->nodeValue);

            $modulus = new \phpseclib\Math\BigInteger($decodedModulus, 256);
            $exponent = new \phpseclib\Math\BigInteger(base64_decode($xml->getElementsByTagName('Exponent')->item(0)->nodeValue), 256);

            $rsa->loadKey(array("modulus" => $modulus, "exponent" => $exponent), \phpseclib\Crypt\RSA::PUBLIC_FORMAT_RAW);

            $rsa->setEncryptionMode(\phpseclib\Crypt\RSA::ENCRYPTION_OAEP);

            /* Dim intKeySize As Integer = PublicKey.KeySize \ 8 */
            $intKeySize = strlen($decodedModulus);

            /* Dim intMaxLength As Integer = intKeySize - 42 */
            $intMaxLength = floor(($intKeySize - 42) / 4);

            /* Dim intDataLength As Integer = bytText.Length */
            $intDataLength = strlen($bytText);

            /* Dim intIterations As Integer = intDataLength \ intMaxLength */
            $intIterations = floor($intDataLength / $intMaxLength);

            /* Dim sbText As New System.Text.StringBuilder() */
            $sbText = "";

            /* For i As Integer = 0 To intIterations */
            for ($i = 0; $i <= $intIterations; $i++) {
                /* Buffer.BlockCopy(bytText, intMaxLength * i, bytTempBytes, 0, bytTempBytes.Length) */
                $bytTempBytes = substr($bytText, $intMaxLength * $i, $intMaxLength);
                $bytTempBytes = mb_convert_encoding($bytTempBytes, "UTF-32LE");

                /* Dim bytEncryptedBytes As Byte() = objRSACryptoServiceProvider.Encrypt(bytTempBytes, True) */
                $bytEncryptedBytes = $rsa->encrypt($bytTempBytes);

                /* Array.Reverse(bytEncryptedBytes) */
                $bytEncryptedBytes = strrev($bytEncryptedBytes);

                /* sbText.Append(Convert.ToBase64String(bytEncryptedBytes)) */
                $sbText .= base64_encode($bytEncryptedBytes);
            }

            return $sbText;
        }
        
        /* <summary>
        * Get the JavaScript syntax to gather the browser data
        * </summary>
        */		
        public function GetJSBrowserData(){
            try{
            
                //$resultNode = $this->GetInitToken();
                    
                $this->soap->GetJSBrowserData();
                
                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->GetJSBrowserDataResponse[0]->GetJSBrowserDataResult[0];
                
                return $resultNode;		
            }catch(\Exception $ex){
                throw new \Exception($ex ." Unable to get the JavaScript to gather the browser data");
            }
        }
        
        /* <summary>
        * Initiate the 3D secure 2
        * </summary>
        *<param name="objRequest">Request Object</param>
        */	
        public function Initiate3DSecure2($objRequest){
            try{
                //$resultNode = $this->GetInitToken();

                $params = new Params();
                $params->RequestValue = $objRequest;
                
                $this->soap->Initiate3DSecure2($params);
                
                $xml = @simplexml_load_string($this->soap->__getLastResponse());
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->Initiate3DSecure2Response[0]->Initiate3DSecure2Result[0];
                
                return $resultNode;		
            }catch(\Exception $ex){
                throw new \Exception("Unable to process transaction.");
            }
        }
        
        /* <summary>
        * Initiate the 3D secure 2
        * </summary>
        * <param name="strResponse">Handler Response</param>
        */		
        public function Process3DSChallengeResult($strResponse){
            try{
                //$resultNode = $this->GetInitToken();
                
                $params = new Params();
                $params->Cres = $strResponse;
                
                $this->soap->Process3DSChallengeResult($params);
                
                $xml = @simplexml_load_string($this->soap->__getLastResponse());			
                $parentNode = $xml->xpath("soap:Body");
                $resultNode = $parentNode[0]->Process3DSChallengeResultResponse[0]->Process3DSChallengeResultResult[0];
                
                return $resultNode;
                
            }catch(\Exception $ex){
                throw new \Exception("Unable to process transaction.");
            }
        }

    }
?>