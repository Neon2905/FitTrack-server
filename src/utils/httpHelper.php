<?php
require_once __DIR__ . '/../lib/jwt.php';

function respond($data, $status = 200)
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
}

function verifyToken($token)
{
    if (!$token) {
        return null;
    }

    try {
        $decoded = decodeJWT($token);
        return $decoded['user_id'] ?? null;
    } catch (Exception $e) {
        revokeToken();
        return null; // Invalid token
    }
}

function revokeToken()
{
    setcookie('jwt', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
    ]);
}
;

function generateTokenAndSetCookie($userId, $username)
{
    if ($userId == null)
        throw new Exception('CREATING JWT: $userId payload cannot be null');

    if ($username == null)
        throw new Exception('CREATING JWT: $username payload cannot be null');

    $token = createJWT(
        [
            "user_id" => $userId,
            "username" => $username
        ]
    );

    $expiration = $_ENV["JWT_EXPIRATION"] ?: 3600 * 24 * 30; //default 30 days
    $expireTimestamp = time() + (int) $expiration;

    setcookie('jwt', $token, [
        'expires' => $expireTimestamp,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'
    ]);
}

function getJwtCookie()
{
    return $_COOKIE['jwt'] ?? null;
}