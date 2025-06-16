<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../src/Router.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';

$router = new Router();

$router->add('POST', '/api/register', [AuthController::class, 'register']);
$router->add('POST', '/api/login', [AuthController::class, 'login']);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Parse JSON body
$body = json_decode(file_get_contents('php://input'), true);
if ($body === null && $_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded') {
    $body = $_POST;
}

$router->dispatch($method, $uri, $body);