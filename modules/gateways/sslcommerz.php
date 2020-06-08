<?php

function sslcommerz_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"sslcommerz"),
     "username" => array("FriendlyName" => "Store ID", "Type" => "text", "Size" => "1000", ),
     "password" => array("FriendlyName" => "Validation Password", "Type" => "text", "Size" => "100", ),
     "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to test", ),
    );
	return $configarray;
}
 


function sslcommerz_link($params) {

	# Gateway Specific Variables
	$gatewayusername = $params['username'];
	$gatewaytestmode = $params['testmode'];

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
	 
	 
	 ////Hash Key Gernarate For SSL ======START========
	    $data_hash['store_id'] = $gatewayusername;
		$data_hash['tran_id'] = $invoiceid;
		$data_hash['total_amount'] = $amount;
		
		$data_hash['cus_name'] = $firstname . ' ' . $lastname;
		$data_hash['cus_add1'] = $address1;
		$data_hash['cus_add2'] = $address2;
		$data_hash['cus_city'] = $city;
		$data_hash['cus_state'] = $state;
		$data_hash['cus_postcode'] = $postcode;
		$data_hash['cus_country'] = $country;
		$data_hash['cus_phone'] = $phone;
		$data_hash['cus_email'] = $email;
		
		$data_hash['currency'] = $currency;
		$data_hash['success_url'] = $params['systemurl']."/modules/gateways/callback/sslcommerz.php";
        $data_hash['fail_url'] = $params['systemurl']."/modules/gateways/callback/sslcommerz.php";
        $data_hash['cancel_url'] = $params['systemurl']."/modules/gateways/callback/sslcommerz.php";
	    
		$security_key = sslcommerz_hash_key($params['password'], $data_hash);
		
		$verify_sign = $security_key['verify_sign'];
        $verify_key = $security_key['verify_key'];
	 
	 ////Hash Key Gernarate For SSL ======END========
	 
	 
        
        //$total=100;
	$results = array();
    if ($gatewaytestmode == "on") {
        $url ='https://sandbox.sslcommerz.com/gwprocess/v3/process.php';
    } else {
        $url ='https://securepay.sslcommerz.com/gwprocess/v3/process.php';
    }

	# Enter your code submit to the gateway...

	$code = '<form method="POST" action="'.$url.'">
<input type="hidden" name="store_id" value="'.$gatewayusername.'" />
<input type="hidden" name="tran_id" value="'.$invoiceid.'" />
<input type="hidden" name="total_amount" value="'.$total.'" />
<input type="hidden" name="success_url" value="'.$params['systemurl'].'/modules/gateways/callback/sslcommerz.php" />
<input type="hidden" name="fail_url" value="'.$params['systemurl'].'/modules/gateways/callback/sslcommerz.php" />
<input type="hidden" name="cancel_url" value="'.$params['systemurl'].'/modules/gateways/callback/sslcommerz.php" />

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

<input type="hidden" name="verify_sign" value="'.$verify_sign.'"/>
<input type="hidden" name="verify_key" value="'.$verify_key.'"/>
<input type="submit" class="btn btn-success" value="Pay with Credit/Debit Card" />
</form>';
        ///print_r($code);exit;
	return $code;
}




// Hash Key Gernate For SSL Commerz
	   function sslcommerz_hash_key($store_passwd="", $parameters=array()) {
	
			$return_key = array(
				"verify_sign"	=>	"",
				"verify_key"	=>	""
			);
			if(!empty($parameters)) {
				# ADD THE PASSWORD
		
				$parameters['store_passwd'] = md5($store_passwd);
		
				# SORTING THE ARRAY KEY
		
				ksort($parameters);	
		
				# CREATE HASH DATA
			
				$hash_string="";
				$verify_key = "";	# VARIFY SIGN
				foreach($parameters as $key=>$value) {
					$hash_string .= $key.'='.($value).'&'; 
					if($key!='store_passwd') {
						$verify_key .= "{$key},";
					}
				}
				$hash_string = rtrim($hash_string,'&');	
				$verify_key = rtrim($verify_key,',');
		
				# THAN MD5 TO VALIDATE THE DATA
		
				$verify_sign = md5($hash_string);
				$return_key['verify_sign'] = $verify_sign;
				$return_key['verify_key'] = $verify_key;
			}
			return $return_key;
		}
		/// END

?>
