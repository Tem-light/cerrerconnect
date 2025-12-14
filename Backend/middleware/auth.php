<?php
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/jwt.php";

$headers = getallheaders();

if (!isset($headers["Authorization"])) {
    jsonResponse(["message" => "Unauthorized"], 401);
}

$token = str_replace("Bearer ", "", $headers["Authorization"]);
$parts = explode(".", $token);

if (count($parts) !== 2) {
    jsonResponse(["message" => "Invalid token"], 401);
}

$payload = json_decode(base64_decode($parts[0]), true);

if (!$payload || $payload["exp"] < time()) {
    jsonResponse(["message" => "Token expired"], 401);
}

$_REQUEST["user"] = $payload;
