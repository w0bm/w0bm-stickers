<?php

require_once "config.php";

const FIELDS = ["order_id", "payment_id", "payer_id"];

//------------- VALIDATE REQUEST -------------
if($_SERVER["REQUEST_METHOD"] !== "POST")
    exit_response(405, c_error("invalid_request_method"));

foreach(FIELDS as $key) {
    if(!isset($_POST[$key]))
        exit_response(400, c_error("missing_field", $key));
    if(!is_string($_POST[$key]))
        exit_response(400, c_error("invalid_field_value", $key));
}

list($order_id, $payment_id, $payer_id) = array_map(function($k) {
    return $_POST[$k];
}, FIELDS);



//----------- PAYPAL AUTHROZIATION -----------
$res = fetch(
    "https://api.paypal.com/v1/oauth2/token",
    "POST",
    ["grant_type" => "client_credentials"],
    "application/x-www-form-urlencoded",
    "Authorization: Basic " . base64_encode($cfg_paypal_clientid . ":" . $cfg_paypal_secret)
);

if($res === false)
    exit_response(500, c_error("paypal_authentication_failed"));

$token = json_decode($res)->access_token;



//------------- EXECUTE PAYMENT --------------
$res = fetch(
    "https://api.paypal.com/v1/payments/payment/" . $payment_id . "/execute",
    "POST",
    ["payer_id" => $payer_id],
    "application/json",
    "Authorization: Bearer " . $token
);

if($res === false)
    exit_response(500, c_error("payment_execution_failed"));

$json = json_decode($res);

if($json->state !== "approved")
    exit_response(500, c_error("payment_not_approved"));



//-------------- INSERT PAYMENT --------------
$dbh = new PDO($cfg_pgsql_dsn);
$fail = false;
foreach($json->transactions as $t) {
    $query = $dbh->prepare("INSERT INTO payments (order_id, amount, transaction, payment_method) VALUES (:order_id, :amount, :transaction, 'PayPal')");

    if(!$query->execute([
        ":order_id" => $order_id,
        ":amount" => $t->amount->total,
        ":transaction" => $t->related_resources[0]->sale->id
    ])) $fail = true;
}

if($fail)
    exit_response(200, c_error("payment_insertion_failed"));
exit_response(200);
