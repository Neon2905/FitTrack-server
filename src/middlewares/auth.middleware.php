<?php
require_once __DIR__ . '/../utils/httpHelper.php';

$authMiddleware = function ($body) {

    // Exclude for routes of [POST] /auth/login and /auth/register
    $excludedRoutes = [
        'POST' => [
            '/api/auth/login',
            '/api/auth/register'
        ]
    ];

    $requestMethod = $_SERVER['REQUEST_METHOD'];
    $requestUri = $_SERVER['REQUEST_URI'];

    if (in_array($requestUri, $excludedRoutes[$requestMethod] ?? [])) {
        // Skip authentication for these routes
        return $body;
    }

    $token = $_COOKIE['jwt'] ?? '';
    if (!$token) {
        setcookie('jwt', '', time() - 3600, '/'); // Revoke cookie
        respond(['message' => 'Unauthorized'], 401);
        exit;
    }

    // Verify token
    $userId = verifyToken($token);
    if (!$userId) {
        setcookie('jwt', '', time() - 3600, '/'); // Revoke cookie
        respond(['message' => 'Invalid token'], 401);
        exit;
    }

    // Check if user_id exists in the database
    require_once __DIR__ . '/../db/Database.php';

    $pdo = Database::getInstance();
    $stmt = $pdo->prepare('SELECT id FROM user WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $user = $stmt->fetch(mode: PDO::FETCH_ASSOC);

    if (!$user) {
        setcookie('jwt', '', time() - 3600, '/'); // Revoke cookie
        respond(['message' => 'Invalid token'], 401);
        exit;
    }

    // Attach user ID to main request body
    $body['user_id'] = $userId;
    return $body;
};