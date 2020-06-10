<?php


/**
 * WHMCS SSLCommerz Payment Gateway Module
 * @see https://developer.sslcommerz.com/doc/v4/
 * @author: Prabal Mallick
 * @copyright Copyright (c) SSL Wireless 2020
 * @license http://www.whmcs.com/license/ WHMCS Eula
**/

    if (!defined("WHMCS")) {
        die("This file cannot be accessed directly");
    }

    /**
     * Define module related meta data.
     * @return array
     */

    function sslcommerz_MetaData()
    {
        return array(
            'DisplayName' => 'SSLCommerz Payment Gateway',
            'APIVersion' => '1.1'
        );
    }


    function sslcommerz_config() {
        $configarray = array(
            "FriendlyName" => array("Type" => "System", "Value"=>"SSLCommerz Payment Gateway"),
            "button_text" => array("FriendlyName" => "Payment Button Label", "Type" => "text", "Size" => "1000", ),
            "store_id" => array("FriendlyName" => "Store ID", "Type" => "text", "Size" => "1000", ),
            "store_password" => array("FriendlyName" => "Store Password", "Type" => "text", "Size" => "100", ),
            "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Enable for Sandbox", ),
            "gateway_type" => array("FriendlyName" => "easyCheckout", "Type" => "yesno", "Description" => "Enable for easyCheckout Popup", )
        );
    	return $configarray;
    }
     


    function sslcommerz_link($params) {
    	# Gateway Specific Variables
    	$gatewaystore_id        = trim($params['store_id']);
    	$gatewaystore_password  = trim($params['store_password']);
    	$gatewaybutton_text     = trim($params['button_text']);
    	$gatewaytestmode        = $params['testmode'];
    	$gatewaytype            = $params['gateway_type'];
    	
    	if ($gatewaytestmode == "on") {
            $url ='https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
            $easy_url = 'https://sandbox.sslcommerz.com/embed.min.js';
        }
        else 
        {
            $url ='https://securepay.sslcommerz.com/gwprocess/v4/api.php';
            $easy_url = 'https://seamless-epay.sslcommerz.com/embed.min.js';
        }

    	# Invoice Variables
    	$invoiceid              = $params['invoiceid'];
    	$description            = $params["description"];
        $amount                 = $params['amount']; # Format: ##.##
        $currency               = $params['currency']; # Currency Code
        $product                = $params['type'];

    	# Client Variables
    	$firstname              = $params['clientdetails']['firstname'];
    	$lastname               = $params['clientdetails']['lastname'];
    	$email                  = $params['clientdetails']['email'];
    	$address1               = $params['clientdetails']['address1'];
    	$address2               = $params['clientdetails']['address2'];
    	$city                   = $params['clientdetails']['city'];
    	$state                  = $params['clientdetails']['state'];
    	$postcode               = $params['clientdetails']['postcode'];
    	$country                = $params['clientdetails']['country'];
    	$phone                  = $params['clientdetails']['phonenumber'];
    	$uuid                   = $params['clientdetails']['uuid'];

    	# System Variables
    	$companyname            = $params['companyname'];
    	$systemurl              = $params['systemurl'];
    	$currency               = $params['currency'];
    	$returnurl              = $params['returnurl'];
    	
    	$url_last_slash = substr($systemurl, strrpos($systemurl, '/') + 0);
    	
    	if($url_last_slash == "/")
    	{
    	    $success_url            = $systemurl.'modules/gateways/callback/sslcommerz.php';
            $fail_url               = $systemurl.'modules/gateways/callback/sslcommerz.php';
            $cancel_url             = $systemurl.'modules/gateways/callback/sslcommerz.php';
            $ipn_url                = $systemurl.'modules/gateways/callback/sslcommerz_ipn.php';
            $easy_end_point         = $systemurl.'modules/gateways/callback/sslcommerz_checkout.php';  
    	}
    	else
    	{
    	    $success_url            = $systemurl.'/modules/gateways/callback/sslcommerz.php';
            $fail_url               = $systemurl.'/modules/gateways/callback/sslcommerz.php';
            $cancel_url             = $systemurl.'/modules/gateways/callback/sslcommerz.php';
            $ipn_url                = $systemurl.'/modules/gateways/callback/sslcommerz_ipn.php';
            $easy_end_point         = $systemurl.'/modules/gateways/callback/sslcommerz_checkout.php';
    	}
        
        $api_endpoint               = $url;

    	$post_data = array();
        $post_data['store_id']      = $gatewaystore_id;
        $post_data['store_passwd']  = $gatewaystore_password;

        $post_data['total_amount']  = $amount;
        $post_data['currency']      = $currency;
        $post_data['tran_id']       = $invoiceid;
        $post_data['success_url']   = $success_url;
        $post_data['fail_url']      = $fail_url;
        $post_data['cancel_url']    = $cancel_url;
        $post_data['ipn_url']       = $ipn_url;
        $post_data['cus_name']      = $firstname.' '.$lastname;
        $post_data['cus_email']     = $email;
        $post_data['cus_phone']     = $phone;
        $post_data['cus_add1']      = $address1;
        $post_data['cus_city']      = $city;
        $post_data['cus_state']     = $state;
        $post_data['cus_postcode']  = $postcode;
        $post_data['cus_country']   = $country;
        $post_data['value_a']       = $description;
        $post_data['value_b']       = $returnurl;
        
        $post_data['shipping_method'] = 'NO';
        $post_data['num_of_item'] = '1';
        $post_data['product_name'] = $product;
        $post_data['product_profile'] = 'general';
        $post_data['product_category'] = 'Domain-Hosting';

        
        if($gatewaytype == "on")
        {
        ?>
        <script type="text/javascript">
            (function (window, document) {
            	var loader = function () {
            	    var script = document.createElement("script"), tag = document.getElementsByTagName("script")[0];
            	    script.src = "<?php echo $easy_url; ?>?" + Math.random().toString(36).substring(7);
            	    tag.parentNode.insertBefore(script, tag);
            	};
            
            	window.addEventListener ? window.addEventListener("load", loader, false) : window.attachEvent("onload", loader);
            })(window, document);
        </script>
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
        <?php
                
        return '<button class="btn btn-success" id="sslczPayBtn"
            token="'.$uuid.'"
            postdata=""
            order="'.$invoiceid.'"
            endpoint="'.$easy_end_point.'">'.$gatewaybutton_text.'</button>
            
            <script>
                function changeObj() {
                    var obj = {};
                    var obj = { store_id: "'.$gatewaystore_id.'", tran_id: "'.$invoiceid.'", total_amount: "'.$amount.'", success_url: "'.$success_url.'", fail_url: "'.$fail_url.'", cancel_url: "'.$cancel_url.'", ipn_url: "'.$ipn_url.'", currency: "'.$currency.'", cus_name: "'.$firstname.' '.$lastname.'", cus_add1: "'.$address1.'", cus_add2: "'.$address2.'", cus_city: "'.$city.'", cus_state: "'.$state.'", cus_postcode: "'.$postcode.'", cus_country: "'.$country.'", cus_phone: "'.$phone.'", cus_email: "'.$email.'", value_a: "'.$description.'", value_b: "'.$returnurl.'", product_name: "'.$product.'"};
                    $("#sslczPayBtn").prop("postdata", obj);
                }
                changeObj();
            </script>';

        }
        else
        {
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $api_endpoint );
            curl_setopt($handle, CURLOPT_TIMEOUT, 30);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
            curl_setopt($handle, CURLOPT_POST, 1 );
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC
            
            
            $content = curl_exec($handle);
            $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            if($code == 200 && !( curl_errno($handle))) {
                curl_close( $handle);
                $sslcommerzResponse = $content;
            } 
            else {
                curl_close( $handle);
                echo "FAILED TO CONNECT WITH SSLCOMMERZ API";
                exit;
            }
            
            # PARSE THE JSON RESPONSE
            $sslcz = json_decode($sslcommerzResponse, true );
            
            if(isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL']!="") 
            {
        		$code = '<form method="POST" action="'.$sslcz['GatewayPageURL'].'">
        		<input type="hidden" name="store_id" value="'.$gatewaystore_id.'" />
        		<input type="hidden" name="tran_id" value="'.$invoiceid.'" />
        		<input type="hidden" name="total_amount" value="'.$amount.'" />
        		<input type="hidden" name="success_url" value="'.$success_url.'" />
        		<input type="hidden" name="fail_url" value="'.$fail_url.'" />
        		<input type="hidden" name="cancel_url" value="'.$cancel_url.'" />
        		<input type="hidden" name="ipn_url" value="'.$ipn_url.'" />
        		<input type="hidden" name="currency" value="'.$currency.'" />
        		<input type="hidden" name="cus_name" value="'.$firstname.' '.$lastname.'" />
        		<input type="hidden" name="cus_add1" value="'.$address1.'" />
        		<input type="hidden" name="cus_add2" value="'.$address2.'" />
        		<input type="hidden" name="cus_city" value="'.$city.'" />
        		<input type="hidden" name="cus_state" value="'.$state.'" />
        		<input type="hidden" name="cus_postcode" value="'.$postcode.'" />
        
        		<input type="hidden" name="cus_country" value="'.$country.'" />
        		<input type="hidden" name="cus_phone" value="'.$phone.'" />
        		<input type="hidden" name="cus_email" value="'.$email.'" />
        
        		<input type="submit" class="btn btn-success" value="'.$gatewaybutton_text.'" />
        		</form>';
        		return $code;
            } 
            else {
              echo "Failed Reason: ".$sslcz['failedreason'];
            }
        }

    }


?>
