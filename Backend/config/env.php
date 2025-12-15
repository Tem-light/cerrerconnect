<?php
// Simple .env loader (no dependencies).
// Usage:
//   require_once __DIR__ . '/env.php';
//   $value = env('DB_HOST', '127.0.0.1');

$__ENV_CACHE = null;

function loadEnvFile($path) {
    $vars = [];

    if (!file_exists($path)) {
        return $vars;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }

        $key = trim(substr($line, 0, $pos));
        $value = trim(substr($line, $pos + 1));

        // Remove surrounding quotes
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }

        $vars[$key] = $value;
    }

    return $vars;
}

function env($key, $default = null) {
    global $__ENV_CACHE;

    if ($__ENV_CACHE === null) {
        // First priority: real environment (e.g. Apache SetEnv / Windows env vars)
        $vars = [];

        // Second: Backend/.env
        $dotenv = __DIR__ . '/../.env';
        $vars = array_merge($vars, loadEnvFile($dotenv));

        $__ENV_CACHE = $vars;
    }

    $sys = getenv($key);
    if ($sys !== false && $sys !== '') {
        return $sys;
    }

    if (array_key_exists($key, $__ENV_CACHE)) {
        return $__ENV_CACHE[$key];
    }

    return $default;
}
