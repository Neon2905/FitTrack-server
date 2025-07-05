<?php

require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../utils/httpHelper.php';

class ActivityController
{
    public static function getActivities($userId)
    {
        $cookie = getJwtCookie();
        if ($cookie) {
            $token = $cookie;
            error_log("JWT Token: " . $token);
        }

        // $pdo = Database::getInstance();
        // $stmt = $pdo->prepare('SELECT * FROM activity WHERE user_id = ? ORDER BY date DESC');
        // $stmt->execute([$userId]);
        // $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        respond([
            'success' => true,
            // 'activities' => $activities
        ]);
    }

    public static function addActivity($userId, $activityData)
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare('INSERT INTO activity (user_id, type, description, date) VALUES (?, ?, ?, ?)');

        $date = date('Y-m-d H:i:s');
        $stmt->execute([$userId, $activityData['type'], $activityData['description'], $date]);

        respond([
            'success' => true,
            'newActivity' => 'Activity added successfully'
        ]);
    }
}