<?php

require_once("config.php");

function exit_response($status, $error = NULL, $subject = NULL) {
    header("Content-type: application/json");
    http_response_code($status);
    exit(json_encode([
        "success" => $status === 200,
        "error" => $error ? [
            "msg" => $error,
            "subject" => $subject
        ] : NULL
    ]));
}

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
    exit_response(400, "invalid_request_method");

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
            "max_range" => 100
        ]
    ],
    "remark" => FILTER_DEFAULT,
    "g-recaptcha-response" => FILTER_DEFAULT
];

$filtered = filter_input_array(INPUT_POST, $def);

foreach($filtered as $key => $val) {
    if($val === false)
        exit_response(400, "invalid_field_value", $key);
    if($val === NULL)
        exit_response(400, "missing_field", $key);
}

$filtered["remark"] = !empty(trim($filtered["remark"])) ? $filtered["remark"] : null;



//------------ VALIDATE CAPTCHA -------------
$url = "https://www.google.com/recaptcha/api/siteverify";
$data = [
    "secret" => $cfg_recaptcha_secret,
    "response" => $filtered["g-recaptcha-response"]
];

$res = file_get_contents($url, false, stream_context_create([
    "http" => [
        "method" => "POST",
        "content" => http_build_query($data),
        "header" => "Content-Type: application/x-www-form-urlencoded"
    ]
]));

if(json_decode($res, true)["success"] === false)
    exit_response(403, "captcha_verification_failed");



//------------- INSERT ORDER -------------
$dbh = new PDO("pgsql:dbname=w0bm-stickers", "w0bm-stickers");
$query = $dbh->prepare("INSERT INTO orders (name, street, house_number, postal_code, city, country_code, count, remark, amount) VALUES (:name, :street, :house_number, :postal_code, :city, :country_code, :count, :remark, :amount)");

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

$total = ($price_per_item * $filtered["count"] + $packaging_cost + $shipping_cost) / 100;

$query->bindValue(":amount", $total);

//bind values
foreach($filtered as $key => $value)
    $query->bindValue(":" . $key, $value);

//execute query with parameters
if(!$query->execute())
    exit_response(500, "insertion_failed");

exit_response(200);

?>
