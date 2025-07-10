<?php
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/ActivityController.php';
require_once __DIR__ . '/../src/controllers/ServerController.php';
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
$router->group('/api/auth', function ($router) {
    $router->add('POST', '/register', [AuthController::class, 'register']);
    $router->add('POST', '/login', [AuthController::class, 'login']);
    $router->add('POST', '/logout', [AuthController::class, 'logout']);
}, $authMiddleware);


$router->group('/api/test', function ($router) {
    $router->add('GET', '/check', [ServerController::class, 'check']);
    $router->add('GET', '/cookie', [ServerController::class, 'testCookie']);
},$authMiddleware);


$router->group('/api/activity', function ($router) {
    $router->add('GET', '/get/all', [ActivityController::class, 'getActivities']);
    // TODO: Add more activity routes here
});


$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
if (
    $body === null &&
    isset($_SERVER['CONTENT_TYPE']) &&
    $_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded'
) {
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