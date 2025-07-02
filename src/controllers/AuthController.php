<?php

require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../db/Database.php';

class AuthController
{
    // Helper: Send JSON response with status code
    private static function respond($data, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // Helper: Validate username and password
    private static function validateCredentials($body)
    {
        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');
        if (!$username || !$password) {
            self::respond(['success' => false, 'message' => 'Username and password required'], 400);
            return false;
        }
        return [$username, $password];
    }

    public static function login($body)
    {
        $creds = self::validateCredentials($body);
        if (!$creds)
            return;
        list($username, $password) = $creds;

        $pdo = Database::getInstance();

        $stmt = $pdo->prepare('SELECT * FROM user WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            self::respond(['message' => 'Invalid credentials'], 401);
            return;
        }

        $payload = [
            'sub' => $user['id'],
            'username' => $username,
            'iat' => time(),
            'exp' => time() + 3600 * 24 * 30 // 30 day expiration
        ];

        $token = createJWT($payload);

        self::respond([
            'username' => $user['username'],
            'email' => $user['email'] ?? null, // Optional email field
            'token' => $token,
        ]);
    }

    public static function register($body)
    {
        $creds = self::validateCredentials($body);
        if (!$creds)
            return;
        list($username, $password) = $creds;

        $pdo = Database::getInstance();

        // Check if username already exists
        $stmt = $pdo->prepare('SELECT id FROM user WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            self::respond(['success' => false, 'message' => 'Username already exists'], 400);
            return;
        }

        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert new user
        $stmt = $pdo->prepare('INSERT INTO user (username, password_hash) VALUES (?, ?)');
        if ($stmt->execute([$username, $passwordHash])) {
            self::respond(['success' => true, 'message' => 'User registered successfully']);
        } else {
            self::respond(['success' => false, 'message' => 'Registration failed'], 500);
        }
    }

    // New: Get user profile by ID
    public static function getProfile($body)
    {
        $userId = $body['user_id'] ?? null;
        if (!$userId) {
            self::respond(['success' => false, 'message' => 'User ID required'], 400);
            return;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, username FROM user WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            self::respond(['success' => true, 'user' => $user]);
        } else {
            self::respond(['success' => false, 'message' => 'User not found'], 404);
        }
    }

    public static function changePassword($body)
    {
        $userId = $body['user_id'] ?? null;
        $oldPassword = $body['old_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';

        if (!$userId || !$oldPassword || !$newPassword) {
            self::respond(['success' => false, 'message' => 'All fields required'], 400);
            return;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT password_hash FROM user WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            self::respond(['success' => false, 'message' => 'Invalid credentials'], 401);
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE user SET password_hash = ? WHERE id = ?');
        if ($stmt->execute([$newHash, $userId])) {
            self::respond(['success' => true, 'message' => 'Password changed']);
        } else {
            self::respond(['success' => false, 'message' => 'Failed to change password'], 500);
        }
    }
}