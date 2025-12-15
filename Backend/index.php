<?php
// Main entry point for the PHP built-in server

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Route API requests
if (str_starts_with($uri, '/api')) {
    require __DIR__ . '/api/index.php';
    exit;
}

// Serve static files if they exist
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

echo json_encode(['message' => 'Career Connect Backend Running. Use /api endpoints.']);
