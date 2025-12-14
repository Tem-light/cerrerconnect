<?php
require_once __DIR__ . "/../_cors.php";
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../helpers/response.php";

$data = json_decode(file_get_contents("php://input"), true);

$id = bin2hex(random_bytes(12));
$password = password_hash($data["password"], PASSWORD_BCRYPT);

$pdo->prepare("
INSERT INTO users (id, name, email, password_hash, role, created_at, updated_at)
VALUES (?, ?, ?, ?, ?, NOW(), NOW())
")->execute([
    $id,
    $data["name"],
    $data["email"],
    $password,
    $data["role"]
]);

// student profile
if ($data["role"] === "student") {
    $pdo->prepare("
      INSERT INTO student_profiles (user_id, university, degree, graduation_year, created_at, updated_at)
      VALUES (?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $id,
        $data["university"],
        $data["degree"],
        $data["graduationYear"]
    ]);
}

// recruiter profile
if ($data["role"] === "recruiter") {
    $pdo->prepare("
      INSERT INTO recruiter_profiles (user_id, company, company_description, website, created_at, updated_at)
      VALUES (?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $id,
        $data["company"],
        $data["companyDescription"],
        $data["website"]
    ]);
}

jsonResponse([
    "id" => $id,
    "name" => $data["name"],
    "email" => $data["email"],
    "role" => $data["role"]
]);
