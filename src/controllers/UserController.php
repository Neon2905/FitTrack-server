<?php

require_once __DIR__ . "/../utils/httpHelper.php";
require_once __DIR__ . "/../db/Database.php";

class UserController
{
    public static function updateProfile($body)
    {
        if (!isset($body['user_id']) || !isset($body['profile'])) {
            return respond(['error' => 'Missing required fields'], 400);
        }

        $userId = $body['user_id'];
        $profile = $body['profile'];

        $pdo = Database::getInstance();

        // Only allow certain fields to be updated
        $allowedFields = ['name', 'date_of_birth', 'age', 'gender', 'weight'];
        $profile = array_intersect_key($profile, array_flip($allowedFields));
        if (empty($profile)) {
            return respond(['error' => 'No valid fields to update'], 400);
        }
        // Build query to update profile
        $fields = [];
        $params = [];
        foreach ($profile as $key => $value) {
            $fields[] = "`$key` = ?";
            $params[] = $value;
        }
        $params[] = $userId;
        $query = "UPDATE user SET " . implode(', ', $fields) . " WHERE id = ?";

        $stmt = $pdo->prepare($query);
        if ($stmt && $stmt->execute($params)) {
            if ($stmt->rowCount() > 0) {
                return respond(['message' => 'Profile updated successfully'], 200);
            } else {
                return respond(['error' => 'User not found or no changes made'], 404);
            }
        } else {
            return respond(['error' => 'Failed to update profile'], 500);
        }
    }
}