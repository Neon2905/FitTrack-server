<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__."/../");
$dotenv->load();

header('Content-Type: application/json');

require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/db/Database.php';

Database::connect();
$router = new Router();

$router->add('POST', '/api/auth/register', [AuthController::class, 'register']);
$router->add('POST', '/api/auth/login', [AuthController::class, 'login']);

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

$router->dispatch($method, $uri, $body);