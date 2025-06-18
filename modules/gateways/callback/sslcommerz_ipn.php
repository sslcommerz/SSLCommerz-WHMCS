<?php
    # Required File Includes
    include("../../../init.php");
    include("../../../includes/functions.php");
    include("../../../includes/gatewayfunctions.php");
    include("../../../includes/invoicefunctions.php");
    
    $gatewaymodule = "sslcommerz"; # Enter your gateway module name here replacing template


    $GATEWAY = getGatewayVariables($gatewaymodule);

    if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
    if (empty($_POST)) die("No Post Data To Validate!");
    
    $invoiceid = $_POST["value_c"];
	$tran_id = $_POST["tran_id"];
	$val_id = $_POST["val_id"];
  
    $store_id = $GATEWAY["store_id"];
    $store_passwd = $GATEWAY["store_password"];
    $systemurl = $GATEWAY['systemurl'];

	if($_POST['status']=='VALID' && !empty($_POST['val_id']) && !empty($_POST['tran_id']))
	{
		$subdomain = $GATEWAY["testmode"] == "on" ? "sandbox" : "securepay";
        $validation_url = "https://" .$subdomain. ".sslcommerz.com/validator/api/validationserverAPI.php?val_id=".$val_id."&store_id=".$store_id."&store_passwd=".$store_passwd."&v=1&format=json";
		
		$handle = curl_init();
		curl_setopt_array($handle, [
			CURLOPT_URL => $validation_url,
			CURLOPT_RETURNTRANSFER=> true,
			CURLOPT_SSL_VERIFYHOST=> true,
			CURLOPT_SSL_VERIFYPEER=> true,
		]);
		$result = curl_exec($handle);
		$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		if($code == 200 && !( curl_errno($handle)))
		{
			$result = json_decode($result);
			
			$status = $result->status;	
			$tran_id = $result->tran_id;
			$amount = $result->amount;
			$bank_tran_id = $result->bank_tran_id;
			$currency_amount = $result->currency_amount;
			$risk_level = $result->risk_level;
			
			if(($status=='VALID' || $status=='VALIDATED') && $risk_level == 0)
			{
				 $status = 'success';
			}
			else
			{
				 $status = 'failed';
			}
		}
		
		checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
		checkCbTransID($tran_id); # Checks transaction number isn't already in the database and ends processing if it does
		
		if ($status=="success") {
			$fee = 0;
		    addInvoicePayment($invoiceid, $tran_id, $currency_amount, $fee, $gatewaymodule);
		    logTransaction($GATEWAY["name"], array("Gateway Response" => $_POST, "IPN Response" => "Succeed By IPN"), "Successful"); # Save to Gateway Log: name, data array, status
		    exit();
		    
		} 
		else {
		   	logTransaction($GATEWAY["name"], array("Gateway Response" => $_POST, "IPN Response" => "Failed By IPN"), "Unsuccessful"); # Save to Gateway Log: name, data array, status
		    exit();
		}

	}
	else
	{
		logTransaction($GATEWAY["name"], array("Gateway Response" => $_POST, "IPN Response" => "Invalid"), "Unsuccessful"); # Save to Gateway Log: name, data array, status
	    exit();
	}

 
?>
