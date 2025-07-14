<?php
require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../utils/httpHelper.php';

class ActivityController
{
    public static function getActivities($body)
    {
        $userId = $body['user_id'];
        $exclude = isset($body['exclude']) && is_array($body['exclude']) ? $body['exclude'] : [];

        $pdo = Database::getInstance();
        // Build query to exclude activities with UUIDs in $excepts
        $query = 'SELECT * FROM activity WHERE user_id = ?';
        $params = [$userId];

        if (!empty($exclude)) {
            $placeholders = implode(',', array_fill(0, count($exclude), '?'));
            $query .= " AND uuid NOT IN ($placeholders)";
            $params = array_merge($params, $exclude);
        }

        $query .= ' ORDER BY start_time DESC';

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Decode tracks JSON for each activity
        foreach ($activities as &$activity) {
            if (isset($activity['tracks'])) {
                $decodedTracks = json_decode($activity['tracks'], true);
                $activity['tracks'] = is_array($decodedTracks) ? $decodedTracks : [];
            }
        }
        unset($activity); // break reference

        respond([
            'activities' => $activities
        ], 200);
    }

    public static function addActivity($body)
    {
        $userId = $body['user_id'];
        $activity = $body['activity'];

        $pdo = Database::getInstance();

        error_log("Body: " . print_r($body, true));

        // Extract fields from $activityData with defaults
        $uuid = $activity['id'];

        // Check if already exists
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM activity WHERE uuid = ?');
        $stmt->execute([$uuid]);
        $exists = $stmt->fetchColumn();
        if ($exists) {
            return respond([
                'success' => false,
                'message' => 'Activity with this ID already exists'
            ], 409);
        }

        $type = $activity['type'] ?: '';
        // Convert ISO 8601 to MySQL datetime format if necessary
        $startTimeRaw = $activity['startTime'] ?? date('Y-m-d H:i:s');
        $startTime = date('Y-m-d H:i:s', strtotime($startTimeRaw));
        $endTimeRaw = $activity['endTime'] ?? null;
        $endTime = $endTimeRaw ? date('Y-m-d H:i:s', strtotime($endTimeRaw)) : null;
        $steps = $activity['steps'] ?? 0;
        $distance = $activity['distance'] ?? 0.0;
        $duration = $activity['duration'] ?? 0;
        $calories = $activity['calories'] ?? 0.0;
        $reps = $activity['reps'] ?? 0;
        $challengeId = $activity['challengeId'] ?? null;
        $tracks = isset($activity['tracks']) ? json_encode($activity['tracks']) : json_encode([]);

        $stmt = $pdo->prepare('
            INSERT INTO activity (
            uuid,
            user_id,
            type,
            start_time,
            end_time,
            duration,
            distance,
            calories,
            steps,
            reps,
            challenge_id,
            tracks
            ) VALUES (
                :uuid,
                :user_id,
                :type,
                :start_time,
                :end_time,
                :duration,
                :distance,
                :calories,
                :steps,
                :reps,
                :challenge_id,
                :tracks
            )
        ');

        $stmt->execute([
            ':uuid' => $uuid,
            ':user_id' => $userId,
            ':type' => $type,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
            ':duration' => $duration,
            ':distance' => $distance,
            ':calories' => $calories,
            ':steps' => $steps,
            ':reps' => $reps,
            ':challenge_id' => $challengeId,
            ':tracks' => $tracks
        ]);

        respond([
            'message' => 'Activity added successfully'
        ], 201);
    }
}