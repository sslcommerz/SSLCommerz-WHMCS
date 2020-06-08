<?php

function sslcommerz_config() {
    $configarray = array("FriendlyName" => array("Type" => "System", "Value"=>"sslcommerz"),
     "store_id" => array("FriendlyName" => "Store ID", "Type" => "text", "Size" => "1000", ),
     "store_password" => array("FriendlyName" => "Store Password", "Type" => "text", "Size" => "100", ),
     "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to test", ),
      "chargemode" => array("FriendlyName" => "Gateway Charge to Customer", "Type" => "yesno", "Description" => "Customer will bear gateway charge", ),
    );
	return $configarray;
}
 


function sslcommerz_link($params) {

	# Gateway Specific Variables
	$gatewaystore_id = trim($params['store_id']);
	$gatewaystore_password = trim($params['store_password']);
	$gatewaytestmode = $params['testmode'];
	$gatewaychargemode = $params['chargemode'];

	# Invoice Variables
	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
    $amount = $params['amount']; # Format: ##.##
    $currency = $params['currency']; # Currency Code

	# Client Variables
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$address1 = $params['clientdetails']['address1'];
	$address2 = $params['clientdetails']['address2'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	$phone = $params['clientdetails']['phonenumber'];

	# System Variables
	$companyname = $params['companyname'];
	#$systemurl = $params['systemurl'] . "client/";
	$params['systemurl'] = $params['systemurl'];
	$currency = $params['currency'];
        
	 $total=$amount;
	 
	 
	 
        
        //$total=100;
	$results = array();
    if ($gatewaytestmode == "on") {

    	 if($gatewaychargemode == "on") 
    	 {
        	$url ='https://sandbox.sslcommerz.com/gwprocess/v3/api_convenient_fee.php';
         }
         else
         {
        	$url ='https://sandbox.sslcommerz.com/gwprocess/v3/api.php';
         }

    } 
    else 
    {
         if($gatewaychargemode == "on") 
    	 {
        	$url ='https://securepay.sslcommerz.com/gwprocess/v3/api_convenient_fee.php';
         }
         else
         {
        	$url ='https://securepay.sslcommerz.com/gwprocess/v3/api.php';
         }
    }


$success_url=$params['systemurl'].'/modules/gateways/callback/sslcommerz.php';
$fail_url=$params['systemurl'].'/modules/gateways/callback/sslcommerz.php';
$cancel_url=$params['systemurl'].'/modules/gateways/callback/sslcommerz.php';
	# Enter your code submit to the gateway...

	




	$post_data = array();
 $post_data['store_id'] = $gatewaystore_id;
 $post_data['store_passwd'] = $gatewaystore_password;
$direct_api_url = $url;

$post_data['total_amount'] = $total;
$post_data['currency'] = $currency;
$post_data['tran_id'] = $invoiceid;
$post_data['success_url'] = $success_url;
$post_data['fail_url'] = $fail_url;
$post_data['cancel_url'] = $cancel_url;
$post_data['cus_name'] = $firstname.' '.$lastname;
$post_data['cus_email'] = $email;
$post_data['cus_phone'] = $phone;
$post_data['cus_add1'] = $address1;
$post_data['cus_add2'] = $address2;
$post_data['cus_city'] = $city;
$post_data['cus_state'] = $state;
$post_data['cus_postcode'] = $postcode;
$post_data['cus_country'] = $country;
$post_data['value_b'] = $total;



$handle = curl_init();
curl_setopt($handle, CURLOPT_URL, $direct_api_url );
curl_setopt($handle, CURLOPT_TIMEOUT, 30);
curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($handle, CURLOPT_POST, 1 );
curl_setopt($handle, CURLOPT_POSTFIELDS, $post_data);
curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); # KEEP IT FALSE IF YOU RUN FROM LOCAL PC


$content = curl_exec($handle );
$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
if($code == 200 && !( curl_errno($handle))) {
  curl_close( $handle);
  $sslcommerzResponse = $content;
} else {
  curl_close( $handle);
  echo "FAILED TO CONNECT WITH SSLCOMMERZ API";
  exit;
}
# PARSE THE JSON RESPONSE
$sslcz = json_decode($sslcommerzResponse, true );
//var_dump($sslcz); exit;
if(isset($sslcz['GatewayPageURL']) && $sslcz['GatewayPageURL']!="") {
        # THERE ARE MANY WAYS TO REDIRECT - Javascript, Meta Tag or Php Header Redirect or Other
        # echo "<script>window.location.href = '". $sslcz['GatewayPageURL'] ."';</script>";
        //echo "<meta http-equiv='refresh' content='0;url=".$sslcz['GatewayPageURL']."'>";
        # header("Location: ". $sslcz['GatewayPageURL']);
        //exit;

		$code = '<form method="POST" action="'.$sslcz['GatewayPageURL'].'">
		<input type="hidden" name="store_id" value="'.$gatewaystore_id.'" />
		<input type="hidden" name="tran_id" value="'.$invoiceid.'" />
		<input type="hidden" name="total_amount" value="'.$total.'" />
		<input type="hidden" name="success_url" value="'.$success_url.'" />
		<input type="hidden" name="fail_url" value="'.$fail_url.'" />
		<input type="hidden" name="cancel_url" value="'.$cancel_url.'" />

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

		<input type="submit" class="btn btn-success" value="Pay with Credit/Debit Card" />
		</form>';
	        ///print_r($code);exit;
		return $code;

} else {
  echo "JSON Data parsing error!";
}






}


?>
