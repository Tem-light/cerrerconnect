<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/jwt.php';

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) {
        $data .= str_repeat('=', 4 - $remainder);
    }
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_sign($headerB64, $payloadB64, $secret) {
    return base64url_encode(hash_hmac('sha256', $headerB64 . '.' . $payloadB64, $secret, true));
}

function jwt_create(array $payload) {
    $secret = JWT_SECRET;

    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $headerB64 = base64url_encode(json_encode($header));
    $payloadB64 = base64url_encode(json_encode($payload));

    $sig = jwt_sign($headerB64, $payloadB64, $secret);
    return $headerB64 . '.' . $payloadB64 . '.' . $sig;
}

function jwt_verify($token) {
    $secret = JWT_SECRET;

    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }

    [$h, $p, $s] = $parts;

    $expected = jwt_sign($h, $p, $secret);
    if (!hash_equals($expected, $s)) {
        return null;
    }

    $payload = json_decode(base64url_decode($p), true);
    if (!$payload) {
        return null;
    }

    if (isset($payload['exp']) && $payload['exp'] < time()) {
        return null;
    }

    return $payload;
}
