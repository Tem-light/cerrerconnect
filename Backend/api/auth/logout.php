<?php
require_once __DIR__ . '/../_cors.php';
require_once __DIR__ . '/../../helpers/response.php';

// Stateless JWT: client just deletes the token.
jsonResponse(['message' => 'Logged out']);
