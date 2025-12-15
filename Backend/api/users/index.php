<?php
// /api/users/index.php

$user = requireAuthUser();

// GET /users (admin)
if ($method === 'GET' && count($segments) === 1) {
    requireRole($user, ['admin']);

    $stmt = $pdo->query('SELECT u.*, sp.university, sp.degree, sp.graduation_year, sp.avatar_url, sp.resume_url, sp.github_url, sp.linkedin_url, sp.skills_json,
                                rp.company, rp.company_description, rp.website, rp.logo_url
                         FROM users u
                         LEFT JOIN student_profiles sp ON sp.user_id = u.id
                         LEFT JOIN recruiter_profiles rp ON rp.user_id = u.id
                         ORDER BY u.created_at DESC');

    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[] = [
            'id' => $row['id'],
            '_id' => $row['id'],
            'name' => $row['name'],
            'email' => $row['email'],
            'role' => $row['role'],
            'approved' => (int)$row['approved'] === 1,
            'blocked' => (int)$row['blocked'] === 1,

            // student fields (flat, because UI reads them directly)
            'university' => $row['university'],
            'degree' => $row['degree'],
            'graduationYear' => $row['graduation_year'],
            'avatarUrl' => $row['avatar_url'],
            'resumeUrl' => $row['resume_url'],
            'githubUrl' => $row['github_url'],
            'linkedinUrl' => $row['linkedin_url'],
            'skills' => $row['skills_json'] ? json_decode($row['skills_json'], true) : [],

            // recruiter fields
            'company' => $row['company'],
            'companyDescription' => $row['company_description'],
            'website' => $row['website'],
            'logoUrl' => $row['logo_url'],
        ];
    }

    jsonResponse($out);
}

// PUT /users/{userId} (self)
if ($method === 'PUT' && isset($segments[1]) && count($segments) === 2) {
    $userId = $segments[1];
    // Only allow admin or the user themselves
    if ($user['role'] !== 'admin' && $user['id'] !== $userId) {
        jsonResponse(['message' => 'Forbidden'], 403);
    }

    $data = jsonBody();
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    if ($name === '' || $email === '') {
        jsonResponse(['message' => 'name and email are required'], 422);
    }

    $pdo->prepare('UPDATE users SET name=?, email=?, updated_at=NOW() WHERE id=?')->execute([$name, $email, $userId]);
    jsonResponse(['id' => $userId, '_id' => $userId, 'name' => $name, 'email' => $email]);
}

// PUT /users/{userId}/student-profile
if ($method === 'PUT' && isset($segments[1]) && (($segments[2] ?? '') === 'student-profile') && count($segments) === 3) {
    $userId = $segments[1];
    if ($user['id'] !== $userId) jsonResponse(['message' => 'Forbidden'], 403);

    $data = jsonBody();
    $skills = $data['skills'] ?? [];
    if (!is_array($skills)) $skills = [];

    $pdo->prepare('UPDATE student_profiles SET university=?, degree=?, graduation_year=?, phone=?, github_url=?, linkedin_url=?, avatar_url=?, resume_url=?, skills_json=?, updated_at=NOW() WHERE user_id=?')
        ->execute([
            $data['university'] ?? null,
            $data['degree'] ?? null,
            $data['graduationYear'] ?? null,
            $data['phone'] ?? null,
            $data['githubUrl'] ?? null,
            $data['linkedinUrl'] ?? null,
            $data['avatarUrl'] ?? null,
            $data['resumeUrl'] ?? null,
            json_encode(array_values($skills)),
            $userId,
        ]);

    $p = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id=?');
    $p->execute([$userId]);
    $row = $p->fetch();

    jsonResponse([
        'university' => $row['university'],
        'degree' => $row['degree'],
        'graduationYear' => $row['graduation_year'],
        'phone' => $row['phone'],
        'githubUrl' => $row['github_url'],
        'linkedinUrl' => $row['linkedin_url'],
        'avatarUrl' => $row['avatar_url'],
        'resumeUrl' => $row['resume_url'],
        'skills' => $row['skills_json'] ? json_decode($row['skills_json'], true) : [],
    ]);
}

// POST /users/{userId}/student-profile/avatar
if ($method === 'POST' && isset($segments[1]) && (($segments[2] ?? '') === 'student-profile') && (($segments[3] ?? '') === 'avatar')) {
    $userId = $segments[1];
    if ($user['id'] !== $userId) jsonResponse(['message' => 'Forbidden'], 403);

    if (!isset($_FILES['avatar'])) {
        jsonResponse(['message' => 'avatar file is required'], 422);
    }

    $dir = __DIR__ . '/../../uploads/avatars';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $file = $_FILES['avatar'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = $userId . '_' . time() . '.' . ($ext ?: 'png');
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['message' => 'Upload failed'], 500);
    }

    $url = '/uploads/avatars/' . $name;
    $pdo->prepare('UPDATE student_profiles SET avatar_url=?, updated_at=NOW() WHERE user_id=?')->execute([$url, $userId]);

    $p = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id=?');
    $p->execute([$userId]);
    $row = $p->fetch();

    jsonResponse([
        'avatarUrl' => $row['avatar_url'],
        'profile' => [
            'avatarUrl' => $row['avatar_url'],
            'resumeUrl' => $row['resume_url'],
            'skills' => $row['skills_json'] ? json_decode($row['skills_json'], true) : [],
            'university' => $row['university'],
            'degree' => $row['degree'],
            'graduationYear' => $row['graduation_year'],
            'phone' => $row['phone'],
            'githubUrl' => $row['github_url'],
            'linkedinUrl' => $row['linkedin_url'],
        ],
    ]);
}

// POST /users/{userId}/student-profile/resume
if ($method === 'POST' && isset($segments[1]) && (($segments[2] ?? '') === 'student-profile') && (($segments[3] ?? '') === 'resume')) {
    $userId = $segments[1];
    if ($user['id'] !== $userId) jsonResponse(['message' => 'Forbidden'], 403);

    if (!isset($_FILES['resume'])) {
        jsonResponse(['message' => 'resume file is required'], 422);
    }

    $dir = __DIR__ . '/../../uploads/resumes';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    $file = $_FILES['resume'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $name = $userId . '_' . time() . '.' . ($ext ?: 'pdf');
    $dest = $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        jsonResponse(['message' => 'Upload failed'], 500);
    }

    $url = '/uploads/resumes/' . $name;
    $pdo->prepare('UPDATE student_profiles SET resume_url=?, updated_at=NOW() WHERE user_id=?')->execute([$url, $userId]);

    $p = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id=?');
    $p->execute([$userId]);
    $row = $p->fetch();

    jsonResponse([
        'resumeUrl' => $row['resume_url'],
        'profile' => [
            'avatarUrl' => $row['avatar_url'],
            'resumeUrl' => $row['resume_url'],
            'skills' => $row['skills_json'] ? json_decode($row['skills_json'], true) : [],
            'university' => $row['university'],
            'degree' => $row['degree'],
            'graduationYear' => $row['graduation_year'],
            'phone' => $row['phone'],
            'githubUrl' => $row['github_url'],
            'linkedinUrl' => $row['linkedin_url'],
        ],
    ]);
}

// PUT /users/{userId}/recruiter-profile
if ($method === 'PUT' && isset($segments[1]) && (($segments[2] ?? '') === 'recruiter-profile')) {
    $userId = $segments[1];
    if ($user['id'] !== $userId) jsonResponse(['message' => 'Forbidden'], 403);

    $data = jsonBody();
    $pdo->prepare('UPDATE recruiter_profiles SET company=?, company_description=?, website=?, logo_url=?, updated_at=NOW() WHERE user_id=?')
        ->execute([
            $data['company'] ?? null,
            $data['companyDescription'] ?? null,
            $data['website'] ?? null,
            $data['logoUrl'] ?? null,
            $userId,
        ]);

    $p = $pdo->prepare('SELECT * FROM recruiter_profiles WHERE user_id=?');
    $p->execute([$userId]);
    $row = $p->fetch();

    jsonResponse([
        'company' => $row['company'],
        'companyDescription' => $row['company_description'],
        'website' => $row['website'],
        'logoUrl' => $row['logo_url'],
    ]);
}

// PUT /users/{recruiterId}/approve
if ($method === 'PUT' && isset($segments[1]) && (($segments[2] ?? '') === 'approve')) {
    requireRole($user, ['admin']);

    $recruiterId = $segments[1];
    $pdo->prepare('UPDATE users SET approved=1, updated_at=NOW() WHERE id=? AND role="recruiter"')->execute([$recruiterId]);
    createNotification($pdo, $recruiterId, 'account_approved', 'Your recruiter account has been approved.');
    jsonResponse(['message' => 'Approved']);
}

// PUT /users/{userId}/block
if ($method === 'PUT' && isset($segments[1]) && (($segments[2] ?? '') === 'block')) {
    requireRole($user, ['admin']);

    $userId = $segments[1];
    $pdo->prepare('UPDATE users SET blocked=1, updated_at=NOW() WHERE id=?')->execute([$userId]);
    createNotification($pdo, $userId, 'account_blocked', 'Your account has been blocked.');
    jsonResponse(['message' => 'Blocked']);
}

jsonResponse(['message' => 'Not Found'], 404);
