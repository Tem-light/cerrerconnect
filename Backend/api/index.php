<?php
require_once __DIR__ . '/_cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';
require_once __DIR__ . '/../helpers/jwt.php';
require_once __DIR__ . '/../config/jwt.php';

function requestPathAfterApi() {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
    $pos = strpos($path, '/api');
    if ($pos === false) {
        return '';
    }
    $after = substr($path, $pos + 4);
    return trim($after, '/');
}

function jsonBody() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function requireAuthUser() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;
    if (!$auth) {
        jsonResponse(['message' => 'Unauthorized'], 401);
    }

    $token = preg_replace('/^Bearer\s+/i', '', $auth);
    $payload = jwt_verify($token);
    if (!$payload) {
        jsonResponse(['message' => 'Invalid or expired token'], 401);
    }

    return $payload;
}

function requireRole($user, $roles) {
    if (!in_array($user['role'], $roles, true)) {
        jsonResponse(['message' => 'Forbidden'], 403);
    }
}

function newId() {
    return bin2hex(random_bytes(12));
}

function jobRequirements($pdo, $jobId) {
    $s = $pdo->prepare('SELECT requirement FROM job_requirements WHERE job_id=? ORDER BY id ASC');
    $s->execute([$jobId]);
    return array_map(fn($r) => $r['requirement'], $s->fetchAll());
}

function jobToApi($pdo, $jobRow, $includeApplicantsCount = false) {
    $jobId = $jobRow['id'];
    $reqs = jobRequirements($pdo, $jobId);

    $out = [
        'id' => $jobId,
        '_id' => $jobId,
        'recruiterId' => $jobRow['recruiter_id'],
        'title' => $jobRow['title'],
        'company' => $jobRow['company'],
        'location' => $jobRow['location'],
        'type' => $jobRow['type'],
        'category' => $jobRow['category'],
        'salaryMin' => $jobRow['salary_min'] !== null ? (int)$jobRow['salary_min'] : null,
        'salaryMax' => $jobRow['salary_max'] !== null ? (int)$jobRow['salary_max'] : null,
        'description' => $jobRow['description'],
        'openings' => (int)$jobRow['openings'],
        'applicationStart' => $jobRow['application_start'],
        'applicationEnd' => $jobRow['application_end'],
        'contactEmail' => $jobRow['contact_email'],
        'contactWebsite' => $jobRow['contact_website'],
        'status' => $jobRow['status'],
        'createdAt' => $jobRow['created_at'],
        'updatedAt' => $jobRow['updated_at'],
        'requirements' => $reqs,
    ];

    if ($includeApplicantsCount) {
        $c = $pdo->prepare('SELECT COUNT(*) AS c FROM applications WHERE job_id=?');
        $c->execute([$jobId]);
        $out['applicantsCount'] = (int)($c->fetch()['c'] ?? 0);
    }

    return $out;
}

function createNotification($pdo, $userId, $type, $message) {
    $pdo->prepare('INSERT INTO notifications (id, user_id, type, message, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())')
        ->execute([newId(), $userId, $type, $message]);
}

$route = requestPathAfterApi();
$method = $_SERVER['REQUEST_METHOD'];
$segments = $route === '' ? [] : explode('/', $route);

// Health check
if ($route === '') {
    jsonResponse(['message' => 'CareerConnect API is running']);
}

// AUTH ROUTES
if ($segments[0] === 'auth') {
    $action = $segments[1] ?? '';

    if ($action === 'register' && $method === 'POST') {
        require_once __DIR__ . '/auth/register.php';
        exit;
    }

    if ($action === 'login' && $method === 'POST') {
        require_once __DIR__ . '/auth/login.php';
        exit;
    }

    if ($action === 'logout' && $method === 'POST') {
        require_once __DIR__ . '/auth/logout.php';
        exit;
    }

    if ($action === 'me' && $method === 'GET') {
        require_once __DIR__ . '/auth/me.php';
        exit;
    }

    jsonResponse(['message' => 'Not Found'], 404);
}

// JOBS
if ($segments[0] === 'jobs') {
    $user = null;
    $sub = $segments[1] ?? null;

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

    // POST /jobs (create)
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
                    $data['salaryMin'] !== '' ? ($data['salaryMin'] ?? null) : null,
                    $data['salaryMax'] !== '' ? ($data['salaryMax'] ?? null) : null,
                    $data['description'] ?? '',
                    (int)($data['openings'] ?? 1),
                    ($data['applicationStart'] ?? null) ?: null,
                    ($data['applicationEnd'] ?? null) ?: null,
                    ($data['contactEmail'] ?? null) ?: null,
                    ($data['contactWebsite'] ?? null) ?: null,
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
                    array_key_exists('salaryMin', $data) ? $data['salaryMin'] : $job['salary_min'],
                    array_key_exists('salaryMax', $data) ? $data['salaryMax'] : $job['salary_max'],
                    $data['description'] ?? $job['description'],
                    (int)($data['openings'] ?? $job['openings']),
                    array_key_exists('applicationStart', $data) ? (($data['applicationStart'] ?: null)) : $job['application_start'],
                    array_key_exists('applicationEnd', $data) ? (($data['applicationEnd'] ?: null)) : $job['application_end'],
                    array_key_exists('contactEmail', $data) ? (($data['contactEmail'] ?: null)) : $job['contact_email'],
                    array_key_exists('contactWebsite', $data) ? (($data['contactWebsite'] ?: null)) : $job['contact_website'],
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
}

// APPLICATIONS
if ($segments[0] === 'applications') {
    $user = requireAuthUser();

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
}

// USERS
if ($segments[0] === 'users') {
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

        $dir = __DIR__ . '/../uploads/avatars';
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

        $dir = __DIR__ . '/../uploads/resumes';
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
}

// STATS
if ($segments[0] === 'stats') {
    $user = requireAuthUser();
    $scope = $segments[1] ?? '';

    if ($scope === 'admin') {
        requireRole($user, ['admin']);
        $totalStudents = (int)($pdo->query("SELECT COUNT(*) c FROM users WHERE role='student'")->fetch()['c'] ?? 0);
        $totalRecruiters = (int)($pdo->query("SELECT COUNT(*) c FROM users WHERE role='recruiter'")->fetch()['c'] ?? 0);
        $pendingRecruiters = (int)($pdo->query("SELECT COUNT(*) c FROM users WHERE role='recruiter' AND approved=0")->fetch()['c'] ?? 0);
        $totalJobs = (int)($pdo->query("SELECT COUNT(*) c FROM jobs")->fetch()['c'] ?? 0);
        $activeJobs = (int)($pdo->query("SELECT COUNT(*) c FROM jobs WHERE status='active'")->fetch()['c'] ?? 0);
        $totalApplications = (int)($pdo->query("SELECT COUNT(*) c FROM applications")->fetch()['c'] ?? 0);

        jsonResponse([
            'totalStudents' => $totalStudents,
            'totalRecruiters' => $totalRecruiters,
            'pendingRecruiters' => $pendingRecruiters,
            'totalJobs' => $totalJobs,
            'activeJobs' => $activeJobs,
            'totalApplications' => $totalApplications,
        ]);
    }

    if ($scope === 'recruiter') {
        requireRole($user, ['recruiter']);

        $stmt = $pdo->prepare('SELECT COUNT(*) c FROM jobs WHERE recruiter_id=?');
        $stmt->execute([$user['id']]);
        $totalJobs = (int)($stmt->fetch()['c'] ?? 0);

        $stmt = $pdo->prepare("SELECT COUNT(*) c FROM jobs WHERE recruiter_id=? AND status='active'");
        $stmt->execute([$user['id']]);
        $activeJobs = (int)($stmt->fetch()['c'] ?? 0);

        $stmt = $pdo->prepare('SELECT COUNT(*) c FROM applications a JOIN jobs j ON j.id=a.job_id WHERE j.recruiter_id=?');
        $stmt->execute([$user['id']]);
        $totalApplicants = (int)($stmt->fetch()['c'] ?? 0);

        jsonResponse([
            'totalJobs' => $totalJobs,
            'activeJobs' => $activeJobs,
            'totalApplicants' => $totalApplicants,
        ]);
    }

    if ($scope === 'student') {
        requireRole($user, ['student']);

        $stmt = $pdo->prepare('SELECT COUNT(*) c FROM applications WHERE student_id=?');
        $stmt->execute([$user['id']]);
        $appliedJobs = (int)($stmt->fetch()['c'] ?? 0);

        $stmt = $pdo->prepare("SELECT COUNT(*) c FROM applications WHERE student_id=? AND status='pending'");
        $stmt->execute([$user['id']]);
        $pendingApplications = (int)($stmt->fetch()['c'] ?? 0);

        $stmt = $pdo->prepare("SELECT COUNT(*) c FROM applications WHERE student_id=? AND status='accepted'");
        $stmt->execute([$user['id']]);
        $acceptedApplications = (int)($stmt->fetch()['c'] ?? 0);

        $totalJobs = (int)($pdo->query("SELECT COUNT(*) c FROM jobs WHERE status='active'")->fetch()['c'] ?? 0);

        jsonResponse([
            'totalJobs' => $totalJobs,
            'appliedJobs' => $appliedJobs,
            'pendingApplications' => $pendingApplications,
            'acceptedApplications' => $acceptedApplications,
        ]);
    }

    jsonResponse(['message' => 'Not Found'], 404);
}

// NOTIFICATIONS
if ($segments[0] === 'notifications') {
    $user = requireAuthUser();

    if ($method === 'GET' && count($segments) === 1) {
        $stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 50');
        $stmt->execute([$user['id']]);
        $rows = $stmt->fetchAll();
        $out = array_map(fn($n) => [
            'id' => $n['id'],
            '_id' => $n['id'],
            'type' => $n['type'],
            'message' => $n['message'],
            'isRead' => (int)$n['is_read'] === 1,
            'createdAt' => $n['created_at'],
        ], $rows);
        jsonResponse($out);
    }

    if ($method === 'GET' && (($segments[1] ?? '') === 'unread-count')) {
        $stmt = $pdo->prepare('SELECT COUNT(*) c FROM notifications WHERE user_id=? AND is_read=0');
        $stmt->execute([$user['id']]);
        jsonResponse(['count' => (int)($stmt->fetch()['c'] ?? 0)]);
    }

    if ($method === 'PUT' && isset($segments[1]) && (($segments[2] ?? '') === 'read')) {
        $id = $segments[1];
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?')->execute([$id, $user['id']]);
        jsonResponse(['message' => 'OK']);
    }

    if ($method === 'PUT' && (($segments[1] ?? '') === 'mark-all-read')) {
        $pdo->prepare('UPDATE notifications SET is_read=1 WHERE user_id=?')->execute([$user['id']]);
        jsonResponse(['message' => 'OK']);
    }

    jsonResponse(['message' => 'Not Found'], 404);
}

jsonResponse(['message' => 'Not Found'], 404);
