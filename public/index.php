<?php
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/ActivityController.php';
require_once __DIR__ . '/../src/controllers/ServerController.php';
require_once __DIR__ . '/../src/controllers/UserController.php';
require_once __DIR__ . '/../src/controllers/ChallengeController.php';
require_once __DIR__ . '/../src/db/Database.php';
require_once __DIR__ . '/../src/middlewares/auth.middleware.php';

use Dotenv\Dotenv;

// Prevent logging errors on client console
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

header('Content-Type: application/json');

$dotenvPath = __DIR__ . "/../";
if (file_exists($dotenvPath . ".env")) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}

Database::connect();
$router = new Router();


// Route registration

// Auth route
$router->group('/api/auth', function ($router) {
    $router->add('POST', '/register', [AuthController::class, 'register']);
    $router->add('POST', '/login', [AuthController::class, 'login']);
    $router->add('POST', '/logout', [AuthController::class, 'logout']);
    $router->add('POST', '/google-login', [AuthController::class, 'googleLogin']);
    $router->add('POST', '/refresh-token', [AuthController::class, 'refreshToken']);
}, $authMiddleware);

// User route
$router->group('/api/user', function ($router) {
    $router->add('GET', '/me', [UserController::class, 'getUser']);
    $router->add('PATCH', '/update', [UserController::class, 'updateProfile']);
    //$router->add('PATCH', '/preferences', [UserController::class, 'updatePreferences']);
    $router->add('DELETE', '/delete', [UserController::class, 'deleteAccount']);
}, $authMiddleware);

$router->group('/api/activity', function ($router) {
    $router->add('POST', '/log', [ActivityController::class, 'addActivity']); // Create a new activity log
    $router->add('POST', '/batch-log', [ActivityController::class, 'batchSync']); // Bulk upload offline logs
    $router->add('GET', '/list', [ActivityController::class, 'getActivities']); // Get all user activities
    $router->add('GET', '/daily-summary', [ActivityController::class, 'dailySummary']); // Daily stats summary

    $router->add('GET', '/:id', [ActivityController::class, 'getActivity']); // Get single activity detail
    $router->add('PATCH', '/:id', [ActivityController::class, 'updateActivity']); // Update activity
    $router->add('DELETE', '/:id', [ActivityController::class, 'deleteActivity']); // Delete activity
}, $authMiddleware);

$router->group('/api/challenge', function ($router) {
    $router->add('POST', '/create', [ChallengeController::class, 'createChallenge']);
    $router->add('GET', '/list', [ChallengeController::class, 'listChallenges']);
    $router->add('GET', '/:id', [ChallengeController::class, 'getChallenge']); // Get challenge details
    $router->add('GET', '/:id/progress', [ChallengeController::class, 'getChallengeProgress']); // Get user's progress
    $router->add('PATCH', '/:id', [ChallengeController::class, 'updateChallenge']); // Update challenge
    $router->add('DELETE', '/:id', [ChallengeController::class, 'deleteChallenge']); // Delete challenge
}, $authMiddleware);

$router->group('/api/sync', function ($router) {
    $router->add('POST', '/local-to-server', [ServerController::class, 'syncLocalToServer']); // Sync local activity/challenge data to user account
    $router->add('GET', '/server-to-local', [ServerController::class, 'syncServerToLocal']); // Pull server-side activity/challenge data to device
    $router->add('POST', '/merge-confirm', [ServerController::class, 'mergeConfirm']); // Confirm merging guest/local data after login
    $router->add('GET', '/status', [ServerController::class, 'syncStatus']); // Return sync status, conflicts, last sync timestamp
}, $authMiddleware);

$router->group('/api/stats', function ($router) {
    $router->add('GET', '/weekly', [UserController::class, 'weeklyStats']); // Weekly stats for steps, duration, calories
    $router->add('GET', '/monthly', [UserController::class, 'monthlyStats']); // Monthly summary
    $router->add('GET', '/progress', [UserController::class, 'progressStats']); // User goal tracking
}, $authMiddleware);

$router->group('/api/test', function ($router) {
    $router->add('GET', '/check', [ServerController::class, 'check']);
    $router->add('GET', '/cookie', [ServerController::class, 'testCookie']);
}, $authMiddleware);


$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Parse request body safely
$rawInput = file_get_contents('php://input');
$body = null;

if (!empty($rawInput) && isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $body = json_decode($rawInput, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $body = null;
    }
} elseif (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false) {
    $body = $_POST;
}

// Centralized error handling
try {
    // Execute
    $router->dispatch($method, $uri, $body);
} catch (Throwable $e) {
    $trace = $e->getTrace();
    $caller = $trace[0]['class'] ?? '';
    if ($caller !== '') {
        $caller .= '::' . ($trace[0]['function'] ?? 'unknown');
    } else {
        $caller = $trace[0]['function'] ?? 'unknown';
    }

    respond(
        [
            "error" => "Internal server error"
        ],
        500
    );

    error_log("Error in " . $caller . ": " . $e->getMessage());
}