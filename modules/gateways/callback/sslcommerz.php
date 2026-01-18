<?php
# Required File Includes
include("../../../init.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");

$gatewaymodule = "sslcommerz";
$GATEWAY = getGatewayVariables($gatewaymodule);

if (!$GATEWAY["type"]) {
	die("Module Not Activated"); # Checks gateway module is active before accepting callback
}

$invoiceid = $_POST["value_c"];
$tran_id = $_POST["tran_id"];
$val_id = $_POST["val_id"];

$store_id = $GATEWAY["store_id"];
$store_passwd = $GATEWAY["store_password"];
$systemurl = rtrim($GATEWAY['systemurl'], '/');

if (empty($_POST)) {
	header("Location: " . $systemurl . "/clientarea.php?action=services");
	exit();
}

if ($_POST['status'] == 'VALID' && !empty($_POST['val_id']) && !empty($_POST['tran_id'])) {
	if ($GATEWAY["testmode"] == "on") {
		$validation_url = "https://sandbox.sslcommerz.com/validator/api/validationserverAPI.php?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json";
	} else {
		$validation_url = "https://securepay.sslcommerz.com/validator/api/validationserverAPI.php?val_id=" . $val_id . "&store_id=" . $store_id . "&store_passwd=" . $store_passwd . "&v=1&format=json";
	}

	$handle = curl_init();
	curl_setopt_array($handle, [
		CURLOPT_URL => $validation_url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_SSL_VERIFYHOST => true,
		CURLOPT_SSL_VERIFYPEER => true,
	]);
	$results = curl_exec($handle);
	$code = curl_getinfo($handle, CURLINFO_HTTP_CODE);

	if ($code == 200 && !(curl_errno($handle))) {
		$result = json_decode($results);

		$status = $result->status;
		$tran_id = $result->tran_id;
		$base_amount = $result->currency_amount;
		$risk_level = $result->risk_level;
		$base_fair = $result->base_fair;
		$value_total = $result->value_b;
	}

	if ($result->status == "VALIDATED") {
		logTransaction($GATEWAY["name"], array("Gateway Response" => $_POST, "Validation Response" => json_decode($results, true), "Response" => "Already Succeed By IPN"), "Successful"); # Save to Gateway Log: name, data array, status
		header("Location: " . $systemurl . "/clientarea.php?action=services"); /* Redirect browser */
		exit();
	} elseif ($result->status == "VALID") {
		checkCbInvoiceID($invoiceid, $GATEWAY["name"]); # Checks invoice ID is a valid invoice number or ends processing
		checkCbTransID($tran_id);
		$fee = 0;
		addInvoicePayment($invoiceid, $tran_id, $base_amount, $fee, $gatewaymodule);
		logTransaction($GATEWAY["name"], $_POST, "Successful"); # Save to Gateway Log: name, data array, status
		header("Location: " . $systemurl . "/clientarea.php?action=services"); /* Redirect browser */
		exit();

	} else {
		logTransaction($GATEWAY["name"], $_POST, "Unsuccessful"); # Save to Gateway Log: name, data array, status
		header("Location: " . $systemurl . "/clientarea.php?action=services"); /* Redirect browser */
		exit();
	}

} else if (in_array($_POST['status'], ['FAILED', 'CANCELLED', 'UNATTEMPTED', 'EXPIRED']) && !empty($_POST['tran_id']) && !empty($_POST['value_c'])) {
	logTransaction($GATEWAY["name"], $_POST, "Unsuccessful"); # Save to Gateway Log: name, data array, status
	header("Location: " . $systemurl . "/clientarea.php?action=services"); /* Redirect browser */
	exit();
} else {
	logTransaction($GATEWAY["name"], $_POST, "Unsuccessful"); # Save to Gateway Log: name, data array, status
	header("Location: " . $systemurl . "/clientarea.php?action=services"); /* Redirect browser */
	exit();
}
