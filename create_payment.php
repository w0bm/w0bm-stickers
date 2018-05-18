<?php

require_once "config.php";

//------------- VALIDATE REQUEST -------------
if($_SERVER["REQUEST_METHOD"] !== "POST")
    exit_response(405, c_error("invalid_request_method"));

if(!isset($_POST["order_id"]))
    exit_response(400, c_error("missing_field", "order_id"));

$order_id = $_POST["order_id"];

if(!is_string($order_id))
    exit_response(400, c_error("invalid_field_value", "order_id"));



//---------------- GET AMOUNT ----------------
$dbh = new PDO("pgsql:dbname=w0bm-stickers", "w0bm-stickers");
$query = $dbh->prepare("SELECT amount FROM orders WHERE id = :order_id");

if(!$query->execute([":order_id" => $order_id]))
    exit_response(404, c_error("order_not_found", "order_id"));

$amount = $query->fetch()["amount"];



//----------- PAYPAL AUTHORIZATION -----------
$data = [
    "grant_type" => "client_credentials"
];
$auth = base64_encode($cfg_paypal_clientid . ":" . $cfg_paypal_secret);
$res = fetch(
    "https://api.paypal.com/v1/oauth2/token",
    "POST",
    $data,
    "application/x-www-form-urlencoded",
    "Authorization: Basic " . $auth
);

if($res === false)
    exit_response(500, c_error("paypal_authentication_failed"));

$token = json_decode($res)->access_token;



//-------------- CREATE PAYMENT --------------
$payment = [
    "intent" => "sale",
    "payer" => [
        "payment_method" => "paypal"
    ],
    "redirect_urls" => [
        "return_url" => "https://" . $_SERVER["HTTP_HOST"],
        "cancel_url" => "https://" . $_SERVER["HTTP_HOST"]
    ],
    "transactions" => [[
        "amount" => [
            "total" => strval($amount),
            "currency" => "EUR"
        ]
    ]],
    "application_context" => [
        "shipping_preference" => "NO_SHIPPING",
        "user_action" => "commit"
    ]
];

$res = fetch(
    "https://api.paypal.com/v1/payments/payment",
    "POST",
    $payment,
    "application/json",
    "Authorization: Bearer " . $token
);

if($res === false)
    exit_response(500, c_error("payment_creation_failed"));

exit_response(200, null, ["payment_id" => json_decode($res)->id]);
