<?php
require_once "../config/database.php";
require_once "../helpers/response.php";
require_once "../config/jwt.php";

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $pdo->prepare("SELECT * FROM users WHERE email=?");
$stmt->execute([$data["email"]]);
$user = $stmt->fetch();

if (!$user || !password_verify($data["password"], $user["password_hash"])) {
    jsonResponse(["message" => "Invalid credentials"], 401);
}

$payload = [
    "id" => $user["id"],
    "role" => $user["role"],
    "exp" => time() + JWT_EXPIRES
];

$token = base64_encode(json_encode($payload)) . "." .
         hash_hmac("sha256", json_encode($payload), JWT_SECRET);

jsonResponse([
    "token" => $token,
    "id" => $user["id"],
    "name" => $user["name"],
    "email" => $user["email"],
    "role" => $user["role"]
]);
