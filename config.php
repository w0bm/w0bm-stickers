<?php

$cfg_recaptcha_secret = '';
$cfg_paypal_clientid = '';
$cfg_paypal_secret = '';

function exit_response($status, $error = null, $data = null) {
    header("Content-type: application/json");
    http_response_code($status);
    exit(json_encode([
        "success" => $status === 200,
        "error" => $error,
        "data" => $data
    ]));
}

function c_error($msg, $subject = null) {
    return [
        "msg" => $msg,
        "subject" => $subject
    ];
}

function fetch($url, $method = "GET", $data = null, $content_type = "application/x-www-form-urlencoded") {
    $context = [
        "method" => $method,
        "protocol_version" => "1.1"
    ];
    $headers = [
        "Content-Type: " . $content_type,
        "Connection: close"
    ];
    if($data) {
        $context["content"]
            = $content_type === "application/json"
            ? json_encode($data)
            : http_build_query($data);
        array_push($headers, "Content-Length: " . strlen($context["content"]));
    }
    for($i = 4; $i < func_num_args(); $i++)
        array_push($headers, func_get_arg($i));
    if($headers)
        $context["header"] = $headers;
    return file_get_contents($url, false, stream_context_create(["http" => $context]));
}
