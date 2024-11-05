<?php


/**
 * WHMCS SSLCommerz Payment Gateway Module
 * @see https://developer.sslcommerz.com/
 * @author: SSLCommerz Integration Team
 * @copyright Copyright (c) SSLCommerz 2020-2024
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
            "button_text" => array("FriendlyName" => "Payment Button Label", "Type" => "text", "Size" => "50", 'Default' => 'Proceed to Payment',),
            "store_id" => array("FriendlyName" => "Store ID", "Type" => "text", "Size" => "100", ),
            "store_password" => array("FriendlyName" => "Store Password", "Type" => "password", "Size" => "100", ),
            "testmode" => array("FriendlyName" => "Enable Sandbox / Testmode?", "Type" => "yesno", "Description" => "Choose 'NO' in live environment.", ),
            "gateway_type" => array("FriendlyName" => "easyCheckout", "Type" => "yesno", "Description" => "Use easycheckout popup?", )
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
            $api_endpoint ='https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
            $easy_url = 'https://sandbox.sslcommerz.com/embed.min.js';
        }
        else 
        {
            $api_endpoint ='https://securepay.sslcommerz.com/gwprocess/v4/api.php';
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
        $success_url = $fail_url = $cancel_url = rtrim($systemurl, '/').'/modules/gateways/callback/sslcommerz.php';
        $ipn_url                = rtrim($systemurl, '/').'/modules/gateways/callback/sslcommerz_ipn.php';
        $easy_end_point         = rtrim($systemurl, '/').'/modules/gateways/callback/sslcommerz_checkout.php';

    	$post_data = array();
        $post_data['store_id']      = $gatewaystore_id;
        $post_data['store_passwd']  = $gatewaystore_password;

        $post_data['total_amount']  = $amount;
        $post_data['currency']      = $currency;
        $post_data['tran_id']       = uniqid();
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
        $post_data['value_c']       = $invoiceid;
        
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
                    var obj = { total_amount: "'.$amount.'", success_url: "'.$success_url.'", fail_url: "'.$fail_url.'", cancel_url: "'.$cancel_url.'", ipn_url: "'.$ipn_url.'", currency: "'.$currency.'", cus_name: "'.$firstname.' '.$lastname.'", cus_add1: "'.$address1.'", cus_add2: "'.$address2.'", cus_city: "'.$city.'", cus_state: "'.$state.'", cus_postcode: "'.$postcode.'", cus_country: "'.$country.'", cus_phone: "'.$phone.'", cus_email: "'.$email.'", value_a: "'.$description.'", value_b: "'.$returnurl.'",value_c: "'.$invoiceid.'", product_name: "'.$product.'"};
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
