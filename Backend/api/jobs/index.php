<?php
// /api/jobs/index.php (Router for Jobs)

$sub = $segments[1] ?? null; // jobs/{sub}

// GET /jobs
if ($method === 'GET' && $sub === null) {
    $search = trim($_GET['search'] ?? '');
    $location = trim($_GET['location'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $type = trim($_GET['type'] ?? '');

    $sql = "SELECT * FROM jobs WHERE status='active'";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (title LIKE ? OR company LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    if ($location !== '') {
        $sql .= " AND location LIKE ?";
        $params[] = "%{$location}%";
    }
    if ($category !== '') {
        $sql .= " AND category = ?";
        $params[] = $category;
    }
    if ($type !== '') {
        $sql .= " AND type = ?";
        $params[] = $type;
    }

    $sql .= ' ORDER BY created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = array_map(fn($r) => jobToApi($pdo, $r), $stmt->fetchAll());
    jsonResponse($jobs);
}

// GET /jobs/recruiter/my-jobs
if ($method === 'GET' && $sub === 'recruiter' && (($segments[2] ?? '') === 'my-jobs')) {
    $user = requireAuthUser();
    requireRole($user, ['recruiter']);

    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE recruiter_id=? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $jobs = array_map(fn($r) => jobToApi($pdo, $r, true), $stmt->fetchAll());
    jsonResponse($jobs);
}

// POST /jobs
if ($method === 'POST' && $sub === null) {
    $user = requireAuthUser();
    requireRole($user, ['recruiter']);

    // must be approved and not blocked
    $u = $pdo->prepare('SELECT approved, blocked FROM users WHERE id=?');
    $u->execute([$user['id']]);
    $urow = $u->fetch();
    if (!$urow) jsonResponse(['message' => 'User not found'], 404);
    if ((int)$urow['blocked'] === 1) jsonResponse(['message' => 'Account is blocked'], 403);
    if ((int)$urow['approved'] !== 1) jsonResponse(['message' => 'Recruiter not approved yet'], 403);

    $data = jsonBody();
    $jobId = newId();
    $reqs = $data['requirements'] ?? [];
    if (!is_array($reqs) || count($reqs) === 0) {
        jsonResponse(['message' => 'requirements must be a non-empty array'], 422);
    }

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO jobs (id, recruiter_id, title, company, location, type, category, salary_min, salary_max, description, openings, application_start, application_end, contact_email, contact_website, status, created_at, updated_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())')
            ->execute([
                $jobId,
                $user['id'],
                $data['title'] ?? '',
                $data['company'] ?? '',
                $data['location'] ?? '',
                $data['type'] ?? 'Internship',
                $data['category'] ?? 'Software Development',
                (isset($data['salaryMin']) && $data['salaryMin'] !== '') ? $data['salaryMin'] : null,
                (isset($data['salaryMax']) && $data['salaryMax'] !== '') ? $data['salaryMax'] : null,
                $data['description'] ?? '',
                (int)($data['openings'] ?? 1),
                !empty($data['applicationStart']) ? $data['applicationStart'] : null,
                !empty($data['applicationEnd']) ? $data['applicationEnd'] : null,
                !empty($data['contactEmail']) ? $data['contactEmail'] : null,
                !empty($data['contactWebsite']) ? $data['contactWebsite'] : null,
                'active',
            ]);

        $ins = $pdo->prepare('INSERT INTO job_requirements (job_id, requirement) VALUES (?, ?)');
        foreach ($reqs as $req) {
            $req = trim((string)$req);
            if ($req !== '') {
                $ins->execute([$jobId, $req]);
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['message' => 'Failed to create job', 'error' => $e->getMessage()], 500);
    }

    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id=?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    jsonResponse(jobToApi($pdo, $job, true), 201);
}

// GET /jobs/{id}
if ($method === 'GET' && $sub !== null && $sub !== 'recruiter') {
    $jobId = $sub;
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id=?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    if (!$job) jsonResponse(['message' => 'Job not found'], 404);
    jsonResponse(jobToApi($pdo, $job, true));
}

// PUT /jobs/{id}
if ($method === 'PUT' && $sub !== null) {
    $user = requireAuthUser();
    requireRole($user, ['recruiter']);

    $jobId = $sub;
    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id=?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    if (!$job) jsonResponse(['message' => 'Job not found'], 404);
    if ($job['recruiter_id'] !== $user['id']) jsonResponse(['message' => 'Forbidden'], 403);

    $data = jsonBody();
    $reqs = $data['requirements'] ?? null;

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE jobs SET title=?, company=?, location=?, type=?, category=?, salary_min=?, salary_max=?, description=?, openings=?, application_start=?, application_end=?, contact_email=?, contact_website=?, status=?, updated_at=NOW() WHERE id=?')
            ->execute([
                $data['title'] ?? $job['title'],
                $data['company'] ?? $job['company'],
                $data['location'] ?? $job['location'],
                $data['type'] ?? $job['type'],
                $data['category'] ?? $job['category'],
                (array_key_exists('salaryMin', $data) && $data['salaryMin'] !== '') ? $data['salaryMin'] : (array_key_exists('salaryMin', $data) ? null : $job['salary_min']),
                (array_key_exists('salaryMax', $data) && $data['salaryMax'] !== '') ? $data['salaryMax'] : (array_key_exists('salaryMax', $data) ? null : $job['salary_max']),
                $data['description'] ?? $job['description'],
                (int)($data['openings'] ?? $job['openings']),
                (array_key_exists('applicationStart', $data) && $data['applicationStart'] !== '') ? $data['applicationStart'] : (array_key_exists('applicationStart', $data) ? null : $job['application_start']),
                (array_key_exists('applicationEnd', $data) && $data['applicationEnd'] !== '') ? $data['applicationEnd'] : (array_key_exists('applicationEnd', $data) ? null : $job['application_end']),
                (array_key_exists('contactEmail', $data) && $data['contactEmail'] !== '') ? $data['contactEmail'] : (array_key_exists('contactEmail', $data) ? null : $job['contact_email']),
                (array_key_exists('contactWebsite', $data) && $data['contactWebsite'] !== '') ? $data['contactWebsite'] : (array_key_exists('contactWebsite', $data) ? null : $job['contact_website']),
                $data['status'] ?? $job['status'],
                $jobId,
            ]);

        if (is_array($reqs)) {
            $pdo->prepare('DELETE FROM job_requirements WHERE job_id=?')->execute([$jobId]);
            $ins = $pdo->prepare('INSERT INTO job_requirements (job_id, requirement) VALUES (?, ?)');
            foreach ($reqs as $req) {
                $req = trim((string)$req);
                if ($req !== '') {
                    $ins->execute([$jobId, $req]);
                }
            }
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        jsonResponse(['message' => 'Failed to update job', 'error' => $e->getMessage()], 500);
    }

    $stmt = $pdo->prepare('SELECT * FROM jobs WHERE id=?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    jsonResponse(jobToApi($pdo, $job, true));
}

// DELETE /jobs/{id}
if ($method === 'DELETE' && $sub !== null) {
    $user = requireAuthUser();
    requireRole($user, ['recruiter']);

    $jobId = $sub;
    $stmt = $pdo->prepare('SELECT recruiter_id FROM jobs WHERE id=?');
    $stmt->execute([$jobId]);
    $job = $stmt->fetch();
    if (!$job) jsonResponse(['message' => 'Job not found'], 404);
    if ($job['recruiter_id'] !== $user['id']) jsonResponse(['message' => 'Forbidden'], 403);

    $pdo->prepare('DELETE FROM jobs WHERE id=?')->execute([$jobId]);
    jsonResponse(['message' => 'Deleted']);
}

jsonResponse(['message' => 'Not Found'], 404);
