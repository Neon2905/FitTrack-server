<?php

require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../utils/httpHelper.php';

class AuthController
{
    // Helper: Validate username and password
    private static function validateLoginCredentials($body)
    {
        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');
        $email = trim($body['email'] ?? '');
        if (!$password || !($username || $email)) {
            respond(['message' => 'Username or Email and Password required'], 400);
            return false;
        }
        return [$username, $password, $email];
    }
    private static function validateRegistrationCredentials($body)
    {
        $username = trim($body['username'] ?? '');
        $password = trim($body['password'] ?? '');
        $email = trim($body['email'] ?? '');
        if (!$username || !$password || !$email) {
            respond(['message' => 'Username, password and email required'], 400);
            return false;
        }
        return [$username, $password, $email];
    }

    public static function checkAuth($body)
    {
        $userId = $body['user_id'];
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT username, email FROM user WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            generateTokenAndSetCookie($userId, $user["username"]);
            respond(
                [
                    'username' => $user['username'],
                    'email' => $user['email'] ?? null, // Optional email field
                ],
                200
            );
        } else {
            setcookie('jwt', '', time() - 3600, '/'); // Revoke cookie
            respond(['message' => 'User not found'], 404);
        }
    }

    public static function login($body)
    {
        $creds = self::validateLoginCredentials($body);
        if (!$creds)
            return;
        list($username, $password, $email) = $creds;

        $pdo = Database::getInstance();

        $stmt = $pdo->prepare('SELECT * FROM user WHERE ' . ($username ? 'username' : 'email') . ' = ?');
        $stmt->execute([$username ?? $email]); // Check by username or email
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            respond(['message' => 'Invalid credentials'], 401);
            return;
        }

        generateTokenAndSetCookie($user["id"], $user["username"]);

        respond(
            [
                'username' => $user['username'],
                'email' => $user['email'] ?? null, // Optional email field
            ],
            200
        );
    }

    public static function register($body)
    {
        $creds = self::validateRegistrationCredentials($body);
        if (!$creds)
            return;
        list($username, $password, $email) = $creds;

        $pdo = Database::getInstance();

        // Check if username already exists
        $stmt = $pdo->prepare('SELECT id FROM user WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            respond(['success' => false, 'message' => 'Username already exists'], 400);
            return;
        }

        // Check if email already exists
        $stmt = $pdo->prepare('SELECT id FROM user WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            respond(['success' => false, 'message' => 'Email already exists'], 400);
            return;
        }

        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert new user
        $stmt = $pdo->prepare('INSERT INTO user (username, email, password_hash) VALUES (?, ?, ?)');
        $success = $stmt->execute([$username, $email, $passwordHash]);

        if ($success) {
            $userId = $pdo->lastInsertId();
            generateTokenAndSetCookie($userId, $username);
            respond(
                [
                    'username' => $username,
                    'email' => $email,
                ],
                201
            );
        } else {
            respond(['message' => 'Registration failed'], 500);
        }
    }

    public static function logout()
    {
        revokeToken();
        respond(
            [
                'message' => 'Logged out successfully'
            ],
            200
        );
    }

    // New: Get user profile by ID
    public static function getProfile($body)
    {
        $userId = $body['user_id'] ?? null;
        if (!$userId) {
            respond(['message' => 'User ID required'], 400);
            return;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id, username FROM user WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            respond(['success' => true, 'user' => $user]);
        } else {
            respond(['message' => 'User not found'], 404);
        }
    }

    public static function changePassword($body)
    {
        $userId = $body['user_id'] ?? null;
        $oldPassword = $body['old_password'] ?? '';
        $newPassword = $body['new_password'] ?? '';

        if (!$userId || !$oldPassword || !$newPassword) {
            respond(['message' => 'All fields required'], 400);
            return;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('SELECT password_hash FROM user WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            respond(['message' => 'Invalid credentials'], 401);
            return;
        }

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('UPDATE user SET password_hash = ? WHERE id = ?');
        if ($stmt->execute([$newHash, $userId])) {
            respond(['success' => true, 'message' => 'Password changed']);
        } else {
            respond(['message' => 'Failed to change password'], 500);
        }
    }
}