<?php

require_once "config.php";

const FIELDS = ["order_id", "g-recaptcha-response"];

//------------- VALIDATE REQUEST -------------
if($_SERVER["REQUEST_METHOD"] !== "POST")
    exit_response(405, c_error("invalid_request_method"));

foreach(FIELDS as $key) {
    if(!isset($_POST[$key]))
        exit_response(400, c_error("missing_field", $key));
    if(!is_string($_POST[$key]))
        exit_response(400, c_error("invalid_field_value", $key));
}

if(preg_match("/[0-9a-f]{8}-(?:[0-9a-f]{4}-){3}[0-9a-f]{12}/i", $_POST["order_id"]) === 0)
    exit_response(400, c_error("invalid_field_value", "order_id"));

list($order_id, $captcha) = array_map(function($k) {
    return $_POST[$k];
}, FIELDS);



//------------ VALIDATE CAPTCHA -------------
$res = fetch(
    "https://www.google.com/recaptcha/api/siteverify",
    "POST",
    [
        "secret" => $cfg_recaptcha_secret,
        "response" => $captcha
    ]
);

if($res === false)
    exit_response(500, c_error("captcha_verification_failed"));

if(json_decode($res)->success === false)
    exit_response(403, c_error("captcha_verification_failed"));



//----------- GET ORDER INFORMATION ----------
$dbh = new PDO($cfg_pgsql_dsn);
$query = $dbh->prepare("SELECT amount FROM payments WHERE order_id = :order_id");

if(!$query->execute([":order_id" => $order_id]))
    exit_response(500, c_error("database_error", "payments"));

$total = 0;
while(($row = $query->fetch()) !== false)
    $total += intval(floatval($row["amount"]) * 100);

$query = $dbh->prepare("SELECT amount FROM orders WHERE id = :order_id");

if(!$query->execute([":order_id" => $order_id]))
    exit_response(500, c_error("database_error", "orders"));


$paid = false;
if($total >= intval(floatval($query->fetch()["amount"]) * 100))
    $paid = true;

$query = $dbh->prepare("SELECT shipping_company, shipment_id FROM shipments WHERE order_id = :order_id");
if(!$query->execute([":order_id" => $order_id]))
    exit_response(500, c_error("database_error", "shipments"));

$shipments = [];
while(($shipment = $query->fetch()) !== false)
    array_push($shipments, [
        "shipping_company" => $shipment["shipping_company"],
        "shipment_id" => $shipment["shipment_id"]
    ]);

exit_response(200, null, [
    "paid" => $paid,
    "shipments" => $shipments
]);
