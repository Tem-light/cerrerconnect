<?php
require_once __DIR__ . '/env.php';

// IMPORTANT: set this in Backend/.env in production.
define('JWT_SECRET', env('JWT_SECRET', 'dev-secret-change-me'));

// 30 days
define('JWT_EXPIRES', 60 * 60 * 24 * 30);
