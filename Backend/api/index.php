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
    
    // Fallback for Apache/FastCGI where getallheaders() might be missing or Authorization header might be in $_SERVER
    // Also support X-Authorization which bypasses typical stripping issues
    if (!$auth && isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_AUTHORIZATION'];
    }
    if (!$auth && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $auth = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if (!$auth && isset($_SERVER['HTTP_X_AUTHORIZATION'])) {
        $auth = $_SERVER['HTTP_X_AUTHORIZATION'];
    }
    // Also check headers array for X-Authorization case-insensitive
    if (!$auth) {
        $auth = $headers['X-Authorization'] ?? $headers['x-authorization'] ?? null;
    }

    if (!$auth) {
        // Debug info if needed, or just 401
        jsonResponse(['message' => 'Unauthorized - No token provided'], 401);
    }

    $token = preg_replace('/^Bearer\s+/i', '', $auth);
    $payload = jwt_verify($token);
    if (!$payload) {
        jsonResponse(['message' => 'Invalid or expired token'], 401);
    }
    
    // Normalize ID
    if (!isset($payload['id']) && isset($payload['sub'])) {
        $payload['id'] = $payload['sub'];
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

// ROUTER
// Redirect to module based on first segment

$module = $segments[0] ?? '';

switch ($module) {
    case 'auth':
        if (isset($segments[1])) {
            $file = __DIR__ . '/auth/' . $segments[1] . '.php';
            if (file_exists($file)) {
                require $file;
                exit;
            }
        }
        break;

    case 'jobs':
        require __DIR__ . '/jobs/index.php';
        exit;

    case 'applications':
        require __DIR__ . '/applications/index.php';
        exit;

    case 'users':
        require __DIR__ . '/users/index.php';
        exit;

    case 'notifications':
        require __DIR__ . '/notifications/index.php';
        exit;

    case 'stats':
        require __DIR__ . '/stats/index.php';
        exit;
}

jsonResponse(['message' => 'Not Found'], 404);
