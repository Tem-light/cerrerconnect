<?php
require_once __DIR__ . '/../_cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../helpers/response.php';

$userId = $_REQUEST['user']['id'];

$stmt = $pdo->prepare('SELECT id,name,email,role,approved,blocked FROM users WHERE id=?');
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    jsonResponse(['message' => 'User not found'], 404);
}

$profile = null;
if ($user['role'] === 'student') {
    $p = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id=?');
    $p->execute([$userId]);
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
    $p->execute([$userId]);
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
    'id' => $user['id'],
    '_id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'approved' => (int)$user['approved'] === 1,
    'blocked' => (int)$user['blocked'] === 1,
    'profile' => $profile,
]);
