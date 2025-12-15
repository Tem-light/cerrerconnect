<?php
// /api/applications/index.php

$user = requireAuthUser();
$sub = $segments[1] ?? null;

// POST /applications/job/{jobId}
if ($method === 'POST' && ($segments[1] ?? '') === 'job' && isset($segments[2])) {
    requireRole($user, ['student']);

    $jobId = $segments[2];
    $data = jsonBody();
    $cover = trim($data['coverLetter'] ?? '');
    if ($cover === '') jsonResponse(['message' => 'coverLetter is required'], 422);

    $j = $pdo->prepare('SELECT id, recruiter_id, title FROM jobs WHERE id=? AND status="active"');
    $j->execute([$jobId]);
    $job = $j->fetch();
    if (!$job) jsonResponse(['message' => 'Job not found'], 404);

    $appId = newId();
    try {
        $pdo->prepare('INSERT INTO applications (id, job_id, student_id, cover_letter, status, created_at, updated_at)
                       VALUES (?, ?, ?, ?, "pending", NOW(), NOW())')
            ->execute([$appId, $jobId, $user['id'], $cover]);
    } catch (Throwable $e) {
        if (str_contains($e->getMessage(), 'uq_app_job_student')) {
            jsonResponse(['message' => 'You already applied for this job'], 409);
        }
        jsonResponse(['message' => 'Failed to apply', 'error' => $e->getMessage()], 500);
    }

    createNotification($pdo, $job['recruiter_id'], 'new_application', 'New application for: ' . $job['title']);

    jsonResponse(['message' => 'Application submitted', 'id' => $appId, '_id' => $appId], 201);
}

// GET /applications/student/my-applications
if ($method === 'GET' && ($segments[1] ?? '') === 'student' && (($segments[2] ?? '') === 'my-applications')) {
    requireRole($user, ['student']);

    $stmt = $pdo->prepare('SELECT a.*, j.*,
                                  a.id AS app_id, a.created_at AS app_created_at, a.updated_at AS app_updated_at,
                                  j.id AS job_id
                           FROM applications a
                           JOIN jobs j ON j.id = a.job_id
                           WHERE a.student_id=?
                           ORDER BY a.created_at DESC');
    $stmt->execute([$user['id']]);

    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $job = [
            'id' => $row['job_id'],
            '_id' => $row['job_id'],
            'title' => $row['title'],
            'company' => $row['company'],
            'location' => $row['location'],
            'type' => $row['type'],
            'salaryMin' => $row['salary_min'] !== null ? (int)$row['salary_min'] : null,
            'salaryMax' => $row['salary_max'] !== null ? (int)$row['salary_max'] : null,
            'salary' => ($row['salary_min'] !== null && $row['salary_max'] !== null) ? ((int)$row['salary_min'] . ' - ' . (int)$row['salary_max']) : null,
            'createdAt' => $row['created_at'],
        ];

        $out[] = [
            'id' => $row['app_id'],
            '_id' => $row['app_id'],
            'job' => $job,
            'status' => $row['status'],
            'coverLetter' => $row['cover_letter'],
            'createdAt' => $row['app_created_at'],
            'updatedAt' => $row['app_updated_at'],
        ];
    }

    jsonResponse($out);
}

// GET /applications/job/{jobId}/applicants
if ($method === 'GET' && ($segments[1] ?? '') === 'job' && isset($segments[2]) && (($segments[3] ?? '') === 'applicants')) {
    requireRole($user, ['recruiter']);

    $jobId = $segments[2];
    $j = $pdo->prepare('SELECT recruiter_id FROM jobs WHERE id=?');
    $j->execute([$jobId]);
    $job = $j->fetch();
    if (!$job) jsonResponse(['message' => 'Job not found'], 404);
    if ($job['recruiter_id'] !== $user['id']) jsonResponse(['message' => 'Forbidden'], 403);

    $stmt = $pdo->prepare('SELECT a.id AS app_id, a.cover_letter, a.status, a.created_at AS app_created_at,
                                  u.id AS student_id, u.name, u.email,
                                  sp.university, sp.degree, sp.graduation_year, sp.avatar_url, sp.resume_url, sp.github_url, sp.linkedin_url, sp.skills_json
                           FROM applications a
                           JOIN users u ON u.id = a.student_id
                           LEFT JOIN student_profiles sp ON sp.user_id = u.id
                           WHERE a.job_id=?
                           ORDER BY a.created_at DESC');
    $stmt->execute([$jobId]);

    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[] = [
            'id' => $row['app_id'],
            '_id' => $row['app_id'],
            'status' => $row['status'],
            'coverLetter' => $row['cover_letter'],
            'createdAt' => $row['app_created_at'],
            'student' => [
                'id' => $row['student_id'],
                '_id' => $row['student_id'],
                'name' => $row['name'],
                'email' => $row['email'],
                'university' => $row['university'],
                'degree' => $row['degree'],
                'graduationYear' => $row['graduation_year'],
                'avatarUrl' => $row['avatar_url'],
                'resumeUrl' => $row['resume_url'],
                'githubUrl' => $row['github_url'],
                'linkedinUrl' => $row['linkedin_url'],
                'skills' => $row['skills_json'] ? json_decode($row['skills_json'], true) : [],
            ],
        ];
    }

    jsonResponse($out);
}

// PUT /applications/{id}/status
if ($method === 'PUT' && isset($segments[1]) && (($segments[2] ?? '') === 'status')) {
    requireRole($user, ['recruiter']);

    $appId = $segments[1];
    $data = jsonBody();
    $status = $data['status'] ?? '';
    if (!in_array($status, ['accepted', 'rejected'], true)) {
        jsonResponse(['message' => 'Invalid status'], 422);
    }

    $stmt = $pdo->prepare('SELECT a.student_id, a.job_id, j.recruiter_id, j.title
                           FROM applications a
                           JOIN jobs j ON j.id = a.job_id
                           WHERE a.id=?');
    $stmt->execute([$appId]);
    $row = $stmt->fetch();
    if (!$row) jsonResponse(['message' => 'Application not found'], 404);
    if ($row['recruiter_id'] !== $user['id']) jsonResponse(['message' => 'Forbidden'], 403);

    $pdo->prepare('UPDATE applications SET status=?, updated_at=NOW() WHERE id=?')->execute([$status, $appId]);

    createNotification($pdo, $row['student_id'], 'application_status', 'Your application for "' . $row['title'] . '" was ' . $status . '.');

    jsonResponse(['message' => 'Updated']);
}

jsonResponse(['message' => 'Not Found'], 404);
