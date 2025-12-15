<?php
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt.php';

$headers = function_exists('getallheaders') ? getallheaders() : [];
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;

if (!$auth) {
    jsonResponse(['message' => 'Unauthorized'], 401);
}

$token = preg_replace('/^Bearer\s+/i', '', $auth);
$payload = jwt_verify($token);

if (!$payload) {
    jsonResponse(['message' => 'Invalid or expired token'], 401);
}

$_REQUEST['user'] = $payload;
