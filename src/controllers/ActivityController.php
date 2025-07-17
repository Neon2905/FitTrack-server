<?php
require_once __DIR__ . '/../db/Database.php';
require_once __DIR__ . '/../utils/httpHelper.php';

class ActivityController
{
    public static function getActivities($body)
    {
        $userId = $body['user_id'];
        $exceptions = [];

        if (isset($_GET['exceptions']) && $_GET['exceptions'] != "") {
            isset($_GET['exceptions']) ? (array) $_GET['exceptions'] : [];
        }

        // Cast exceptions to strings to match uuid column type
        $exceptions = array_map('strval', $exceptions);

        $pdo = Database::getInstance();

        $query = 'SELECT * FROM activity WHERE user_id = ?';
        $params = [$userId];

        if (!empty($exceptions)) {
            $placeholders = implode(',', $exceptions);
            $query .= " AND uuid NOT IN ($placeholders)";
            $params = array_merge($params);
        }

        $query .= ' ORDER BY start_time DESC';

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse to client known body
        foreach ($activities as &$activity) {
            if (isset($activity['tracks'])) {
                $decodedTracks = json_decode($activity['tracks'], true);
                $activity['tracks'] = is_array($decodedTracks) ? $decodedTracks : [];
            }
            $activity = (array)Activity::parse($activity);
        }

        if (empty($activities)) {
            respond([
                'success' => false,
                'message' => 'No activities found'
            ], 404);
            return;
        }

        respond(
            $activities,
            200
        );
    }

    public static function add($body)
    {
        $userId = $body['user_id'];

        $pdo = Database::getInstance();

        // Extract fields from $activityData with defaults
        $uuid = $body['id'];

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

        $type = $body['type'] ?: '';
        // Convert ISO 8601 to MySQL datetime format
        $startTimeRaw = $body['startTime'] ?? date('Y-m-d H:i:s');
        $startTime = date('Y-m-d H:i:s', strtotime($startTimeRaw));
        $endTimeRaw = $body['endTime'] ?? null;
        $endTime = $endTimeRaw ? date('Y-m-d H:i:s', strtotime($endTimeRaw)) : null;
        $steps = $body['steps'] ?? 0;
        $distance = $body['distance'] ?? 0.0;
        $duration = $body['duration'] ?? 0;
        $calories = $body['calories'] ?? 0.0;
        $reps = $body['reps'] ?? 0;
        $challengeId = $body['challengeId'] ?? null;
        $tracks = isset($body['tracks']) ? json_encode($body['tracks']) : json_encode([]);

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

        $stmt->execute(params: [
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

        respond(true, 201);
    }

    public static function batchAdd($body)
    {
        $userId = $body['user_id'];
        $activities = $body['activities'];

        $pdo = Database::getInstance();

        // Prepare bulk insert for all activities that do not already exist
        $toInsert = [];
        foreach ($activities as $activity) {
            $uuid = $activity['id'];

            // This is making timeout!
            // // Check if already exists
            // $stmt = $pdo->prepare('SELECT COUNT(*) FROM activity WHERE uuid = ?');
            // $stmt->execute([$uuid]);
            // $exists = $stmt->fetchColumn();
            // if ($exists) {
            //     continue;
            // }

            $type = $activity['type'] ?? '';
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

            $toInsert[] = [
                $uuid,
                $userId,
                $type,
                $startTime,
                $endTime,
                $duration,
                $distance,
                $calories,
                $steps,
                $reps,
                $challengeId,
                $tracks
            ];
        }

        if (!empty($toInsert)) {
            // Build bulk insert query
            $placeholders = implode(',', array_fill(0, count($toInsert), '(?,?,?,?,?,?,?,?,?,?,?,?)'));
            $query = "INSERT IGNORE INTO activity (
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
            ) VALUES " . $placeholders;

            // Flatten values
            $values = [];
            foreach ($toInsert as $row) {
                foreach ($row as $val) {
                    $values[] = $val;
                }
            }
            $stmt = $pdo->prepare($query);
            $stmt->execute($values);
            $inserted = $stmt->rowCount();
            error_log("Responded with inserted = " . $inserted);
            respond($inserted, $inserted > 0 ? 201 : 409);
        } else {
            respond(["message" => "No activity found to add"], 400);
        }
    }
}

class Activity
{
    public $id;
    public $type;
    public $startTime;
    public $endTime;
    public $steps;
    public $distance;
    public $duration;
    public $calories;
    public $reps;
    public $challengeId;
    public $tracks;

    public function __construct(
        $id = 0,
        $type = '',
        $startTime = null,
        $endTime = null,
        $steps = 0,
        $distance = 0.0,
        $duration = 0,
        $calories = 0.0,
        $reps = 0,
        $challengeId = null,
        $tracks = []
    ) {
        $this->id = $id;
        $this->type = $type;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->steps = $steps;
        $this->distance = $distance;
        $this->duration = $duration;
        $this->calories = $calories;
        $this->reps = $reps;
        $this->challengeId = $challengeId;
        $this->tracks = $tracks;
    }

    public static function parse($sqlObject)
    {
        return new Activity(
            $sqlObject['uuid'] ?? 0,
            $sqlObject['type'] ?? '',
            $sqlObject['start_time'] ?? null,
            $sqlObject['end_time'] ?? null,
            $sqlObject['steps'] ?? 0,
            $sqlObject['distance'] ?? 0.0,
            $sqlObject['duration'] ?? 0,
            $sqlObject['calories'] ?? 0.0,
            $sqlObject['reps'] ?? 0,
            $sqlObject['challenge_id'] ?? null,
            $sqlObject['tracks'] ?? []
        );
    }
}