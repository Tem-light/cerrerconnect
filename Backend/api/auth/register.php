<?php
// /api/auth/register.php
if ($method !== 'POST') {
    jsonResponse(['message' => 'Method not allowed'], 405);
}

$data = jsonBody();
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = $data['role'] ?? 'student';

if (!$name || !$email || !$password) {
    jsonResponse(['message' => 'Missing required fields'], 400);
}

if (!in_array($role, ['student', 'recruiter'], true)) {
    jsonResponse(['message' => 'Invalid role'], 400);
}

// Check if email exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email=?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(['message' => 'Email already exists'], 409);
}

$id = newId();
$hash = password_hash($password, PASSWORD_BCRYPT);
// Recruiters need approval (approved=0), Students approved by default (approved=1)
$approved = ($role === 'student') ? 1 : 0; 

$pdo->beginTransaction();
try {
    $pdo->prepare('INSERT INTO users (id, name, email, password_hash, role, approved, blocked, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, 0, NOW(), NOW())')
        ->execute([$id, $name, $email, $hash, $role, $approved]);

    if ($role === 'student') {
        $pdo->prepare('INSERT INTO student_profiles (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())')
            ->execute([$id]);
    } else {
        $pdo->prepare('INSERT INTO recruiter_profiles (user_id, created_at, updated_at) VALUES (?, NOW(), NOW())')
            ->execute([$id]);
    }
    
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    jsonResponse(['message' => 'Registration failed', 'error' => $e->getMessage()], 500);
}

// Auto-login
$token = jwt_create(['sub' => $id, 'role' => $role]);

$userOut = [
    'id' => $id,
    '_id' => $id,
    'name' => $name,
    'email' => $email,
    'role' => $role,
    'approved' => (bool)$approved,
    'blocked' => false,
];

jsonResponse([
    'token' => $token,
    'user' => $userOut,
], 201);
