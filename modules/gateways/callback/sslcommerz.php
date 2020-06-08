<?php
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
$gatewaymodule = "sslcommerz"; # Enter your gateway module name here replacing template
//echo 'hi';exit;
$GATEWAY = getGatewayVariables($gatewaymodule);
//print_r($GATEWAY);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback
$store_id = $GATEWAY["store_id"];
$store_passwd = $GATEWAY["store_password"];
//echo 'hi';exit;

// echo "<pre>";
// print_r($_POST);

if (isset($_POST['tran_id'])) 
{
	$order_id = $_POST['tran_id'];
                                                                     
} else 
{
	$order_id = 0;
}
if (isset($_POST['amount'])) 
{
	$total=$_POST['amount'];	
	$val_id = $_POST['val_id']; 
}else
{
	$total='';	
	$val_id = ''; 
}

if($_POST['status']=='VALID' || $_POST['status']=='VALIDATED')
{
    


		if(SSLCOMMERZ_hash_varify($store_passwd, $_POST))
		{

			if ($GATEWAY["testmode"] == "on") 
			{
		        $requested_url = ("https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id=".$val_id."&Store_Id=".$store_id."&Store_Passwd=".$store_passwd."&v=1&format=json");
		    } 
		    else 
		    {
		         $requested_url = ("https://securepay.sslcommerz.com/validator/api/validationserverAPI.php?val_id=".$val_id."&Store_Id=".$store_id."&Store_Passwd=".$store_passwd."&v=1&format=json");  
		    }
			# Get Returned Variables - Adjust for Post Variable Names from your Gateway's Documentation
			$invoiceid = $_POST["tran_id"];
			$transid = $_POST["tran_id"];
				
			// Code added by JM Redwan
			$orderData = mysql_fetch_assoc(select_query('tblinvoices', 'total', array("id" => $invoiceid)));	
				
			//echo $requested_url;
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
				$tran_id = trim(strstr($tran_id, '_',true));
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

				if($GATEWAY["chargemode"] == "on") 
				{
					$paid_amount=$base_fair;
				}
				else
				{
					$paid_amount=$base_amount;
				}
				
				if(($status=='VALID' || $status=='VALIDATED')&&($orderData['total'] <= $paid_amount) && $risk_level == 0)
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
			    # Successful
			    #$fee=$amount-$store_amount;
				$fee = 0;
			    addInvoicePayment($invoiceid,$transid,$value_total,$fee,$gatewaymodule);
			   // addInvoicePayment($invoiceid,$transid,$paid_amount,$fee,$gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
			    logTransaction($GATEWAY["name"],$_POST,"Successful"); # Save to Gateway Log: name, data array, status
			    header("Location: /clientarea.php?action=services"); /* Redirect browser */
			    exit();
			    
			} else {
				# Unsuccessful
			    logTransaction($GATEWAY["name"],$_POST,"Unsuccessful"); # Save to Gateway Log: name, data array, status
			   header("Location: /clientarea.php?action=services"); /* Redirect browser */
			    exit();
			}

		}
		else
		{
				# Unsuccessful
			   logTransaction($GATEWAY["name"],$_POST.": HASH validation Mismatched","Unsuccessful"); # Save to Gateway Log: name, data array, status
			   header("Location: /clientarea.php?action=services"); /* Redirect browser */
			    exit();


		}

}
else
{
   // echo " Transaction Status is ". $_POST['status'];
    logTransaction($GATEWAY["name"],$_POST." Transaction is Cancelled or Falied","Unsuccessful"); # Save to Gateway Log: name, data array, status
	 header("Location: /clientarea.php?action=services"); /* Redirect browser */
   // die();
}

  



 function SSLCOMMERZ_hash_varify($store_passwd, $post_data)
    {

        if (isset($post_data) && isset($post_data['verify_sign']) && isset($post_data['verify_key'])) {
            # NEW ARRAY DECLARED TO TAKE VALUE OF ALL POST
            $pre_define_key = explode(',', $post_data['verify_key']);

            $new_data = array();
            if (!empty($pre_define_key)) {
                foreach ($pre_define_key as $value) {
                    if (isset($post_data[$value])) {
                        $new_data[$value] = ($post_data[$value]);
                    }
                }
            }
            # ADD MD5 OF STORE PASSWORD
            $new_data['store_passwd'] = md5($store_passwd);

            # SORT THE KEY AS BEFORE
            ksort($new_data);

            $hash_string = "";
            foreach ($new_data as $key => $value) {
                $hash_string .= $key . '=' . ($value) . '&';
            }
            $hash_string = rtrim($hash_string, '&');

            if (md5($hash_string) == $post_data['verify_sign']) {

                return true;

            } else {
                $this->error = "Verification signature not matched";
                return false;
            }
        } else {
            $this->error = 'Required data mission. ex: verify_key, verify_sign';
            return false;
        }
    }
?>
