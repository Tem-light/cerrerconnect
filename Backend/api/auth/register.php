<?php
require_once __DIR__ . '/../_cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/jwt.php';
require_once __DIR__ . '/../../config/jwt.php';

$data = json_decode(file_get_contents('php://input'), true) ?: [];

$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$passwordPlain = $data['password'] ?? '';
$role = $data['role'] ?? 'student';

if ($name === '' || $email === '' || $passwordPlain === '') {
    jsonResponse(['message' => 'name, email, and password are required'], 422);
}

if (!in_array($role, ['student', 'recruiter', 'admin'], true)) {
    jsonResponse(['message' => 'Invalid role'], 422);
}

$id = bin2hex(random_bytes(12));
$password = password_hash($passwordPlain, PASSWORD_BCRYPT);

try {
    $pdo->beginTransaction();

    $approved = $role === 'recruiter' ? 0 : 1;

    $pdo->prepare('
      INSERT INTO users (id, name, email, password_hash, role, approved, blocked, created_at, updated_at)
      VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
    ')->execute([
        $id,
        $name,
        $email,
        $password,
        $role,
        $approved,
    ]);

    $profile = null;

    if ($role === 'student') {
        $pdo->prepare('
          INSERT INTO student_profiles (user_id, university, degree, graduation_year, skills_json, created_at, updated_at)
          VALUES (?, ?, ?, ?, JSON_ARRAY(), NOW(), NOW())
        ')->execute([
            $id,
            $data['university'] ?? null,
            $data['degree'] ?? null,
            $data['graduationYear'] ?? null,
        ]);

        $profile = [
            'university' => $data['university'] ?? null,
            'degree' => $data['degree'] ?? null,
            'graduationYear' => $data['graduationYear'] ?? null,
            'skills' => [],
        ];
    }

    if ($role === 'recruiter') {
        $pdo->prepare('
          INSERT INTO recruiter_profiles (user_id, company, company_description, website, created_at, updated_at)
          VALUES (?, ?, ?, ?, NOW(), NOW())
        ')->execute([
            $id,
            $data['company'] ?? null,
            $data['companyDescription'] ?? null,
            $data['website'] ?? null,
        ]);

        $profile = [
            'company' => $data['company'] ?? null,
            'companyDescription' => $data['companyDescription'] ?? null,
            'website' => $data['website'] ?? null,
        ];
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();

    // Duplicate email
    if (str_contains($e->getMessage(), 'uq_users_email')) {
        jsonResponse(['message' => 'Email is already registered'], 409);
    }

    jsonResponse(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
}

$payload = [
    'id' => $id,
    'role' => $role,
    'exp' => time() + JWT_EXPIRES,
];
$token = jwt_create($payload);

jsonResponse([
    'token' => $token,
    'id' => $id,
    '_id' => $id,
    'name' => $name,
    'email' => $email,
    'role' => $role,
    'approved' => $role === 'recruiter' ? false : true,
    'blocked' => false,
    'profile' => $profile,
]);
