<?php
require_once __DIR__ . '/env.php';

// Usage:
//   require_once __DIR__ . '/database.php';
//   // $pdo available

$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$db   = env('DB_NAME', 'careerconnect');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

try {
    $pdo = new PDO(
        $dsn,
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    // Best-effort: ensure required tables/columns exist for local dev.
    // (If you prefer manual migrations only, remove these lines and import Backend/sql/schema.sql.)
    require_once __DIR__ . '/../sql/ensure_schema.php';
    ensureSchema($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'message' => 'Database connection failed',
        'error' => $e->getMessage(),
    ]);
    exit;
}
