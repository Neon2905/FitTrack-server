<?php

require_once __DIR__ . '/../lib/jwt.php';
require_once __DIR__ . '/../db/Database.php';

class AuthController
{
    public static function login($body)
    {
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            return;
        }
        
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare('SELECT id, password_hash FROM user WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            return;
        }

        $payload = [
            'sub' => $user['id'],
            'username' => $username,
            'iat' => time(),
            'exp' => time() + 3600 * 24 * 7 // 7 day expiration
        ];

        $token = createJWT(
            $payload
        );

        echo json_encode([
            'success' => true,
            'user_id' => $user['id'],
            'token' => $token
        ]);
    }

    public static function register($body)
    {
        $username = $body['username'] ?? '';
        $password = $body['password'] ?? '';

        if (!$username || !$password) {
            echo json_encode(['success' => false, 'message' => 'Username and password required']);
            return;
        }

        $pdo = Database::getInstance();

        // Check if username already exists
        $stmt = $pdo->prepare('SELECT id FROM user WHERE username = ?');
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username already exists']);
            return;
        }

        // Hash the password
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);

        // Insert new user
        $stmt = $pdo->prepare('INSERT INTO user (username, password_hash) VALUES (?, ?)');
        if ($stmt->execute([$username, $passwordHash])) {
            echo json_encode(['success' => true, 'message' => 'User registered successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Registration failed']);
        }
    }
}