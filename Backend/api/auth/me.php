<?php
// /api/auth/me.php
if ($method !== 'GET') {
    jsonResponse(['message' => 'Method not allowed'], 405);
}

$user = requireAuthUser();
$userId = $user['id'];

$stmt = $pdo->query("SELECT u.*, sp.university, sp.degree, sp.graduation_year, sp.avatar_url, sp.resume_url, sp.github_url, sp.linkedin_url, sp.skills_json,
                            rp.company, rp.company_description, rp.website, rp.logo_url
                     FROM users u
                     LEFT JOIN student_profiles sp ON sp.user_id = u.id
                     LEFT JOIN recruiter_profiles rp ON rp.user_id = u.id
                     WHERE u.id = '$userId'");

$row = $stmt->fetch();
if (!$row) {
    jsonResponse(['message' => 'User not found'], 404);
}

$userData = [
    'id' => $row['id'],
    '_id' => $row['id'],
    'name' => $row['name'],
    'email' => $row['email'],
    'role' => $row['role'],
    'approved' => (int)$row['approved'] === 1,
    'blocked' => (int)$row['blocked'] === 1,
    'createdAt' => $row['created_at'],
];

if ($row['role'] === 'student') {
    $userData['profile'] = [
        'university' => $row['university'],
        'degree' => $row['degree'],
        'graduationYear' => $row['graduation_year'],
        'avatarUrl' => $row['avatar_url'],
        'resumeUrl' => $row['resume_url'],
        'githubUrl' => $row['github_url'],
        'linkedinUrl' => $row['linkedin_url'],
        'skills' => $row['skills_json'] ? json_decode($row['skills_json'], true) : [],
    ];
} else if ($row['role'] === 'recruiter') {
    $userData['profile'] = [
        'company' => $row['company'],
        'companyDescription' => $row['company_description'],
        'website' => $row['website'],
        'logoUrl' => $row['logo_url'],
    ];
}

jsonResponse($userData);
