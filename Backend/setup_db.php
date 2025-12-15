<?php
require_once __DIR__ . '/config/env.php';

echo "Setting up database...\n";

$host = env('DB_HOST', '127.0.0.1');
$port = env('DB_PORT', '3306');
$db   = env('DB_NAME', 'careerconnect');
$user = env('DB_USER', 'root');
$pass = env('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to MySQL. Creating database '$db' if not exists...\n";
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database created/verified.\n";
} catch (Throwable $e) {
    die("DB Setup Failed: " . $e->getMessage() . "\n");
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Importing schema...\n";
    $sql = file_get_contents(__DIR__ . '/sql/schema.sql');
    $pdo->exec($sql);
    echo "Schema imported successfully.\n";
} catch (Throwable $e) {
    die("Schema Import Failed: " . $e->getMessage() . "\n");
}
