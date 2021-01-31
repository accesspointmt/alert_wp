<?php
    require_once __DIR__ .'/../API/config.php';

    add_filter('woocommerce_credit_card_form_fields', 'alert_credit_card_form', 20,2);
    add_action('woocommerce_checkout_process', 'alert_description_fields_validation');

    function alert_checkout_required_js(){
        echo "<script language='javascript' type='text/javascript'>";
            echo GetJSBrowserData();
        echo "</script>";
    }

    function alert_credit_card_form( $default_fields, $payment_id){

        $array = GetCardBrands();

        if (count($array) <= 0) {
            return "Error establishing connection to Alert API, please try again later.";
        }else{
            woocommerce_form_field(
                $payment_id.'-card-holder',
                array(
                    'type' => 'text',
                    'label' =>__( 'Card Holder Name', 'alert-payments-woo' ),
                    'class' => array( 'form-row', 'form-row-wide' ),
                    'required' => true,
                )
            );  

            woocommerce_form_field(
                $payment_id.'-card-brand',
                array(
                    'type' => 'select',
                    'label' =>__( 'Card Brand', 'alert-payments-woo' ),
                    'class' => array( 'form-row', 'form-row-wide' ),
                    'required' => true,
                    'options' => $array,
                )
            );
    
            return $default_fields;
        }
    }

    function alert_description_fields_validation(){
        if('alert' === $_POST['payment_method']){
            if(!isset($_POST[$_POST['payment_method'].'-card-holder']) || empty($_POST[$_POST['payment_method'].'-card-holder'])){
                wc_add_notice('Please enter the Card Holder Name', 'error');
            }

            if(!isset($_POST[$_POST['payment_method'].'-card-number']) || empty($_POST[$_POST['payment_method'].'-card-number'])){
                wc_add_notice('Please enter the Card Number', 'error');
            }else if(strlen($_POST[$_POST['payment_method'].'-card-number']) < 8 || strlen($_POST[$_POST['payment_method'].'-card-number']) > 19){
                wc_add_notice('Card Number is invalid', 'error');  
            }

            if(!isset($_POST[$_POST['payment_method'].'-card-expiry']) || empty($_POST[$_POST['payment_method'].'-card-expiry'])){
                wc_add_notice('Please enter the Expiry Date', 'error');
            }else{
                $enteredDt = DateTime::createFromFormat("m / y", $_POST[$_POST['payment_method'].'-card-expiry']);
                if(!$enteredDt){
                    $enteredDt = DateTime::createFromFormat("m / Y", $_POST[$_POST['payment_method'].'-card-expiry']);
                }
                $dt = new DateTime();

                if($enteredDt->getTimestamp() < $dt->getTimestamp()){
                    wc_add_notice('Expiry Date is in the past','error');
                }
            }
            
            if(!isset($_POST[$_POST['payment_method'].'-card-cvc']) || empty($_POST[$_POST['payment_method'].'-card-cvc'])){
                wc_add_notice('Please enter the Card\'s CVC', 'error');
            }
            
        }
    }
?>