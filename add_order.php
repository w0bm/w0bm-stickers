<?php

require_once "config.php";

#exit_response(403, c_error("ordering_disabled"));

//string trim and filter function
function validate_and_trim_string($val) {
    if(is_string($val)) {
        $val = trim($val);
        if(!empty($val))
            return $val;
    }
    return false;
}



//------------- VALIDATE REQUEST -------------
if($_SERVER["REQUEST_METHOD"] !== "POST")
    exit_response(405, c_error("invalid_request_method"));

$dbh = new PDO($cfg_pgsql_dsn);
$query = $dbh->prepare("SELECT SUM(count) FROM orders");
if(!$query->execute())
    exit_response(500, c_error("database_error"));

$def = [
    "name" => [
        "filter" => FILTER_VALIDATE_REGEXP,
        "options" => ["regexp" => "/.+ .+/"]
    ],
    "street" => [
        "filter" => FILTER_CALLBACK,
        "options" => "validate_and_trim_string"
    ],
    "house_number" => [
        "filter" => FILTER_VALIDATE_REGEXP,
        "options" => ["regexp" => "/\w{1,5}/"]
    ],
    "postal_code" => [
        "filter" => FILTER_VALIDATE_REGEXP,
        "options" => ["regexp" => "/[\w ]{4,10}/"]
    ],
    "city" => [
        "filter" => FILTER_CALLBACK,
        "options" => "validate_and_trim_string"
    ],
    "country_code" => [
        "filter" => FILTER_VALIDATE_REGEXP,
        "options" => ["regexp" => "/[A-Z]{2}/"]
    ],
    "count" => [
        "filter" => FILTER_VALIDATE_INT,
        "options" => [
            "min_range" => 1,
            "max_range" => 93 - $query->fetch()[0]
        ]
    ],
    "remark" => FILTER_DEFAULT,
    "g-recaptcha-response" => FILTER_DEFAULT
];

$filtered = filter_input_array(INPUT_POST, $def);

foreach($filtered as $key => $val) {
    if($val === false)
        exit_response(400, c_error("invalid_field_value", $key));
    if($val === null)
        exit_response(400, c_error("missing_field", $key));
}

$filtered["remark"] = !empty(trim($filtered["remark"])) ? $filtered["remark"] : null;



//------------ VALIDATE CAPTCHA -------------
$res = fetch(
    "https://www.google.com/recaptcha/api/siteverify",
    "POST",
    [
        "secret" => $cfg_recaptcha_secret,
        "response" => $filtered["g-recaptcha-response"]
    ]
);

if($res === false)
    exit_response(500, c_error("captcha_verification_failed"));

if(json_decode($res)->success === false)
    exit_response(403, c_error("captcha_verification_failed"));



//------------- INSERT ORDER -------------
$query = $dbh->prepare("INSERT INTO orders (name, street, house_number, postal_code, city, country_code, count, remark, amount) VALUES (:name, :street, :house_number, :postal_code, :city, :country_code, :count, :remark, :amount) RETURNING id");

//remove unneeded key from parameter array
unset($filtered["g-recaptcha-response"]);

$price_per_item = 100;
$item_weight = 2;
$packaging_cost = 100;
$shipping_cost = 0;

if($filtered["country_code"] === "DE") {
    if($filtered["count"] * $item_weight <= 20)
        $shipping_cost = 70;
    else if($filtered["count"] * $item_weight <= 50)
        $shipping_cost = 85;
    else if($filtered["count"] * $item_weight <= 500)
        $shipping_cost = 145;
    $shipping_cost += 90;
}
else
    $shipping_cost = 320;

$total = $price_per_item * $filtered["count"] + $packaging_cost + $shipping_cost;

/*
 * pp_fee = 0.19 * great_total + 0.35
 * great_total = total + pp_fee
 */
$pp_fee = ceil(($total * 0.019 + 35) / 0.981);

$query->bindValue(":amount", ($total + $pp_fee) / 100);

//bind values
foreach($filtered as $key => $value)
    $query->bindValue(":" . $key, $value);

//execute query with parameters
if(!$query->execute())
    exit_response(500, c_error("insertion_failed"));

exit_response(200, null, ["order_id" => $query->fetch()["id"]]);

?>
