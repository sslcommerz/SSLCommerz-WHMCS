<?php
    # Required File Includes
    include("../../../init.php");
    include("../../../includes/functions.php");
    include("../../../includes/gatewayfunctions.php");
    include("../../../includes/invoicefunctions.php");
    
    $gatewaymodule = "sslcommerz"; # Enter your gateway module name here replacing template


    $GATEWAY = getGatewayVariables($gatewaymodule);

    if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
    if (!isset($_POST)) die("No Post Data To Validate!");
    
    $invoiceid = $_POST["tran_id"];
	$transid = $_POST["tran_id"];
  
    $store_id = $GATEWAY["store_id"];
    $store_passwd = $GATEWAY["store_password"];
    $systemurl = $GATEWAY['systemurl'];
    $url_last_slash = substr($systemurl, strrpos($systemurl, '/') + 0);

	if($_POST['status']=='VALID' && (isset($_POST['val_id']) && $_POST['val_id'] != "") && (isset($_POST['tran_id']) && $_POST['tran_id'] != ""))
	{
		if ($GATEWAY["testmode"] == "on") 
		{
            $requested_url = ("https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id=".$val_id."&Store_Id=".$store_id."&Store_Passwd=".$store_passwd."&v=1&format=json");
	    } 
	    else 
	    {
	        $requested_url = ("https://securepay.sslcommerz.com/validator/api/validationserverAPI.php?val_id=".$val_id."&Store_Id=".$store_id."&Store_Passwd=".$store_passwd."&v=1&format=json");  
	    }

		$orderData = mysql_fetch_assoc(select_query('tblinvoices', 'total', array("id" => $invoiceid)));

		$order_amount = $orderData['total'];	
			
		$handle = curl_init();
		curl_setopt($handle, CURLOPT_URL, $requested_url);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($handle);
		$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		if($code == 200 && !( curl_errno($handle)))
		{
			$result = json_decode($result);
			
			$status = $result->status;	
			$tran_date = $result->tran_date;
			$tran_id = $result->tran_id;
			$val_id = $result->val_id;
			$amount = intval($result->amount);
			$store_amount = $result->store_amount;
			$amount = intval($result->amount);
			$bank_tran_id = $result->bank_tran_id;
			$card_type = $result->card_type;
			$base_amount = $result->currency_amount;
			$risk_level = $result->risk_level;
			$base_fair=$result->base_fair;
			$value_total=$result->value_b;
			
			if(($status=='VALID' || $status=='VALIDATED') && ($order_amount == $base_amount) && $risk_level == 0)
			{
				 $status = 'success';
			}
			else
			{
				 $status = 'failed';
			}
		}
		
		$invoiceid = checkCbInvoiceID($invoiceid,$GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
		checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does
		
		if ($status=="success") {
			$fee = 0;
		    addInvoicePayment($invoiceid, $transid, $base_amount, $fee, $gatewaymodule);
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
