<?php
// /api/auth/login.php
if ($method !== 'POST') {
    jsonResponse(['message' => 'Method not allowed'], 405);
}

$data = jsonBody();
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM users WHERE email=?');
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['message' => 'Invalid credentials'], 401);
}

if ((int)$user['blocked'] === 1) {
    jsonResponse(['message' => 'Account is blocked'], 403);
}

$token = jwt_create(['sub' => $user['id'], 'role' => $user['role']]);

jsonResponse([
    'token' => $token,
    'user' => [
        'id' => $user['id'],
        '_id' => $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
        'approved' => (int)$user['approved'] === 1,
        'blocked' => (int)$user['blocked'] === 1,
    ]
]);
