<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'] ?: 'localhost';
$db = $_ENV['DB_NAME'] ?: 'fittrack';
$user = $_ENV['DB_USER'] ?: 'root';
$pass = $_ENV['DB_PASS'] ?: '';
$charset = 'utf8mb4';
$db_uri = $_ENV['DB_URI'] ?: "mysql:host={$host};dbname={$db};charset={$charset}";

echo "DB_HOST: $host\n";
echo "DB_NAME: $db\n";
echo "DB_USER: $user\n";
echo "DB_PASS: $pass\n";
echo "DB_URI: $db_uri\n";