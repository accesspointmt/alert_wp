<?php
    require_once __DIR__ .'/../API/config.php';

    //add_filter('woocommerce_gateway_description', 'alert_description_fields', 20,2);
    add_filter('woocommerce_credit_card_form_fields', 'alert_credit_card_form', 20,2);
    add_action('woocommerce_checkout_process', 'alert_description_fields_validation');
    //add_action('woocommerce_checkout_update_order_meta', 'alert_checkout_update_order_meta', 10, 1);

    //add_action('woocommerce_before_checkout_form', 'alert_checkout_required_js', 5, 1);

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

    /*function alert_description_fields($description, $payment_id){

        if('alert' !== $payment_id){
            return $description;
        }

        $array = GetCardBrands();

        if (count($array) <= 0) {
            return "Error establishing connection to Alert API, please try again later.";
        }else{
            ob_start();
    
            echo '<div style="display: block; width:300px; height:auto;">';
            
                woocommerce_form_field(
                    'card_holder',
                    array(
                        'type' => 'text',
                        'label' =>__( 'Card Holder Name', 'alert-payments-woo' ),
                        'class' => array( 'form-row', 'form-row-wide' ),
                        'required' => true,
                    )
                );  
    
                woocommerce_form_field(
                    'card_brand',
                    array(
                        'type' => 'select',
                        'label' =>__( 'Card Brand', 'alert-payments-woo' ),
                        'class' => array( 'form-row', 'form-row-wide' ),
                        'required' => true,
                        'options' => $array,
                    )
                );

                woocommerce_form_field(
                    'card_number',
                    array(
                        'type' => 'text',
                        'label' =>__( 'Card Number', 'alert-payments-woo' ),
                        'class' => array( 'form-row', 'form-row-wide' ),
                        'required' => true,
                    )
                );
    
                woocommerce_form_field(
                    'expiry_month',
                    array(
                        'type' => 'number',
                        'label' => __( 'Expiry Month', 'alert-payments-woo' ),
                        'required' => true
                    )
                );
            
                woocommerce_form_field(
                    'expiry_year',
                    array(
                        'type' => 'number',
                        'label' => __( 'Expiry Year', 'alert-payments-woo' ),
                        'required' => true
                    )
                );
    
                woocommerce_form_field(
                    'cvc',
                    array(
                        'type' => 'number',
                        'label' =>__( 'Card Code (CVC)', 'alert-payments-woo' ),
                        'class' => array( 'form-row', 'form-row-wide' ),
                        'required' => true,
                    )
                );
        
            echo '</div>';
        
            $description .= ob_get_clean();
        
            return $description;
        }
    }*/

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

    /*function alert_checkout_update_order_meta($order_id){

        if(isset($_POST["card_holder"]) && !empty($_POST["card_holder"])){
            update_post_meta($order_id, 'card_holder', $_POST["card_holder"]);
        }

        if(isset($_POST["card_number"]) && !empty($_POST["card_number"]) && strlen($_POST["card_number"]) >= 8 && strlen($_POST["card_number"]) <= 19){
            update_post_meta($order_id, 'card_number', $_POST["card_number"]);
        }
        
        if(isset($_POST["expiry_month"]) && !empty($_POST["expiry_month"]) && strlen($_POST["expiry_month"]) <= 2 && isset($_POST["expiry_year"]) && !empty($_POST["expiry_year"]) && strlen($_POST["expiry_month"]) == 2 && intval($_POST["expiry_year"]) >= intval(date('Y')) || (intval($_POST["expiry_year"]) == intval(date('Y')) && intval($_POST["expiry_month"]) >= intval(date('m')))){
            update_post_meta($order_id, 'expiry_month', $_POST["expiry_month"]);
            update_post_meta($order_id, 'expiry_year', $_POST["expiry_year"]);
        }

        if(isset($_POST["cvc"]) && !empty($_POST["cvc"])){
            update_post_meta($order_id, 'cvc', $_POST["cvc"]);    
        }
    }*/
?>