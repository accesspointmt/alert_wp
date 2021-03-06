
<?php 
    require_once __DIR__ .'/../../vendor/autoload.php';

    use APG\ResultTypes;
    use APG\RemotePost;

    $response = sanitize_text_field($_POST['cres']);
    $objCheckoutRequest = unserialize(str_replace("|",'"',htmlspecialchars_decode($_POST['threeDSSessionData'])));

    try{
        if($response != NULL && $objCheckoutRequest != NULL){	
            $xml = $APGClient->Process3DSChallengeResult($response);
            $objCheckoutRequest->TransactionReferenceID = (string)$xml->TransactionReferenceID;

            if ($xml->Result == ResultTypes::Captured) {
                /*the redirect need to be outside the iframe*/
                $objRemotePost = new RemotePost();
                $objRemotePost->URL = "postcheckout.php";
                $objRemotePost->Target = _top;
                $objRemotePost->Params["PostCheckoutRequest"] = htmlspecialchars(serialize($objCheckoutRequest));
                $objRemotePost->Post();

            }else{
                //Handle result
            }
        }
        
    }catch(Exception $ex){
    }
?>