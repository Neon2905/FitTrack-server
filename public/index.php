<?php
require_once __DIR__ . '/../vendor/autoload.php';

require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/ActivityController.php';
require_once __DIR__ . '/../src/db/Database.php';

// Prevent logging errors on client console
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/../error.log');

use Dotenv\Dotenv;

$dotenvPath = __DIR__ . "/../.env";
if (file_exists($dotenvPath)) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}


header('Content-Type: application/json');

Database::connect();
$router = new Router();

// Route registration
$router->group('/api/auth', function ($router) {
    $router->add('POST', '/register', [AuthController::class, 'register']);
    $router->add('POST', '/login', [AuthController::class, 'login']);
});

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
    $router->dispatch($method, $uri, $body);
} catch (Throwable $e) {
    respond(['message' => 'Internal Server Error'], 500); // TODO: not sure if code 501 is correct
}