<?php

	# Required File Includes
    include("../../../init.php");
    include("../../../includes/functions.php");
    include("../../../includes/gatewayfunctions.php");
    include("../../../includes/invoicefunctions.php");
    $gatewaymodule = "sslcommerz"; # Enter your gateway module name here replacing template

    $gateway = getGatewayVariables($gatewaymodule);
    
    $gatewaytestmode        = $gateway['testmode'];
	$gatewaytype            = $gateway['gateway_type'];
	
	if ($gatewaytestmode == "on") {
        $url ='https://sandbox.sslcommerz.com/gwprocess/v4/api.php';
    }
    else 
    {
        $url ='https://securepay.sslcommerz.com/gwprocess/v4/api.php';
    }
    
    $tran_id = $_REQUEST['order'];
	$json_data = json_decode(html_entity_decode($_REQUEST['cart_json']), true);

	$post_data = array();
    $post_data['store_id']      = $gateway['store_id'];
    $post_data['store_passwd']  = $gateway['store_password'];
    $post_data['tran_id']       = $tran_id;
    $post_data['total_amount']  = $json_data['total_amount'];
    $post_data['currency']      = $json_data['currency'];
    $post_data['success_url']   = $json_data['success_url'];
    $post_data['fail_url']      = $json_data['fail_url'];
    $post_data['cancel_url']    = $json_data['cancel_url'];
    $post_data['ipn_url']       = $json_data['ipn_url'];
    $post_data['cus_name']      = $json_data['cus_name'];
    $post_data['cus_email']     = $json_data['cus_email'];
    $post_data['cus_phone']     = $json_data['cus_phone'];
    $post_data['cus_add1']      = $json_data['cus_add1'];
    $post_data['cus_city']      = $json_data['cus_city'];
    $post_data['cus_state']     = $json_data['cus_state'];
    $post_data['cus_postcode']  = $json_data['cus_postcode'];
    $post_data['cus_country']   = $json_data['cus_country'];
    $post_data['value_a']       = $json_data['value_a'];
    $post_data['value_b']       = $json_data['value_b'];
    $post_data['value_c']       = $json_data['value_c'];
    $post_data['shipping_method'] = 'NO';
    $post_data['num_of_item'] = '1';
    $post_data['product_name'] = $json_data['product_name'];
    $post_data['product_profile'] = 'general';
    $post_data['product_category'] = 'Domain-Hosting';
    
    $handle = curl_init();
    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_POST, 1);
    curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

    $content = curl_exec($handle);

    $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

    if ($code == 200 && !(curl_errno($handle))) 
    {
        curl_close($handle);
        $sslcommerzResponse = $content;

        # PARSE THE JSON RESPONSE
        if($sslcResponse = json_decode($sslcommerzResponse, true))
        {
        	if (isset($sslcResponse['status']) && $sslcResponse['status'] == 'SUCCESS') 
            {
                if(isset($sslcResponse['GatewayPageURL']) && $sslcResponse['GatewayPageURL']!="") 
                {
     	          	if($gatewaytestmode == "on")
                	{
                		echo json_encode(['status' => 'success', 'data' => $sslcResponse['GatewayPageURL'], 'logo' => $sslcResponse['storeLogo'] ]);
                	}
                	else
                	{
                		echo json_encode(['status' => 'SUCCESS', 'data' => $sslcResponse['GatewayPageURL'], 'logo' => $sslcResponse['storeLogo'] ]);
                	}
                	exit;
				} 
				else 
				{
				   echo json_encode(['status' => 'FAILED', 'data' => null, 'message' => $sslcResponse['failedreason'] ]);
				}
            }
            else 
            {
                echo "API Response: ".$sslcResponse['failedreason'];
            }
		} 
		else 
		{
            echo "Connectivity Issue With API";
        }
    }

?>