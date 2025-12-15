<?php
// /api/notifications/index.php

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
