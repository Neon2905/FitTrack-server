<?php
require_once __DIR__ . '/../../vendor/autoload.php';


// Normally, it's set in the index.php. Only needed if in case there's manual migration, etc.
// use Dotenv\Dotenv;

// $dotenvPath = __DIR__ . "/../../.env";
// if (file_exists($dotenvPath)) {
//     $dotenv = Dotenv::createImmutable($dotenvPath);
//     $dotenv->load();
// }

class Database
{
    private static $pdo = null;

    public static function connect()
    {
        if (self::$pdo)
            return;

        $db_uri = $_ENV['DB_URI'] ?? null;

        if ($db_uri && str_starts_with($db_uri, 'mysql://')) {
            $parts = parse_url($db_uri);
            $host = $parts['host'] ?? 'localhost';
            $db = ltrim($parts['path'], '/');
            $user = $parts['user'] ?? 'root';
            $pass = $parts['pass'] ?? '';
            $port = $parts['port'] ?? 3306;
            $charset = 'utf8mb4';
            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
        } else {
            $host = $_ENV['DB_HOST'] ?? 'localhost';
            $db = $_ENV['DB_NAME'] ?? 'fittrack';
            $user = $_ENV['DB_USER'] ?? 'root';
            $pass = $_ENV['DB_PASS'] ?? '';
            $charset = 'utf8mb4';
            $dsn = "mysql:host={$host};dbname={$db};charset={$charset}";
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        self::$pdo = new PDO($dsn, $user, $pass, $options);
    }

    public static function getInstance()
    {
        if (self::$pdo === null) {
            self::connect();
        }
        return self::$pdo;
    }
}