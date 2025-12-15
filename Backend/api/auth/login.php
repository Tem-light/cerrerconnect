<?php
require_once __DIR__ . '/../_cors.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';
require_once __DIR__ . '/../../helpers/jwt.php';
require_once __DIR__ . '/../../config/jwt.php';

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

if ($email === '' || $password === '') {
    jsonResponse(['message' => 'Email and password are required'], 422);
}

$stmt = $pdo->prepare('SELECT * FROM users WHERE email=?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['message' => 'Invalid credentials'], 401);
}

if ((int)$user['blocked'] === 1) {
    jsonResponse(['message' => 'Account is blocked'], 403);
}

$payload = [
    'id' => $user['id'],
    'role' => $user['role'],
    'exp' => time() + JWT_EXPIRES,
];

$token = jwt_create($payload);

// Attach profile
$profile = null;
if ($user['role'] === 'student') {
    $p = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id=?');
    $p->execute([$user['id']]);
    $row = $p->fetch();
    if ($row) {
        $profile = [
            'university' => $row['university'],
            'degree' => $row['degree'],
            'graduationYear' => $row['graduation_year'],
            'phone' => $row['phone'],
            'githubUrl' => $row['github_url'],
            'linkedinUrl' => $row['linkedin_url'],
            'avatarUrl' => $row['avatar_url'],
            'resumeUrl' => $row['resume_url'],
            'skills' => $row['skills_json'] ? json_decode($row['skills_json'], true) : [],
        ];
    }
}
if ($user['role'] === 'recruiter') {
    $p = $pdo->prepare('SELECT * FROM recruiter_profiles WHERE user_id=?');
    $p->execute([$user['id']]);
    $row = $p->fetch();
    if ($row) {
        $profile = [
            'company' => $row['company'],
            'companyDescription' => $row['company_description'],
            'website' => $row['website'],
            'logoUrl' => $row['logo_url'],
        ];
    }
}

jsonResponse([
    'token' => $token,
    'id' => $user['id'],
    '_id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'approved' => (int)$user['approved'] === 1,
    'blocked' => (int)$user['blocked'] === 1,
    'profile' => $profile,
]);
