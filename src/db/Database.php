<?php
// Define project root if not already defined
if (!defined('__ROOT__')) {
    define('__ROOT__', dirname(__DIR__, 2));
}

// Dotenv manual autoload (order matters!)
require_once __ROOT__ . '/src/lib/Dotenv/Repository/Adapter/AdapterInterface.php';
require_once __ROOT__ . '/src/lib/Dotenv/Repository/Adapter/PutenvAdapter.php';
require_once __ROOT__ . '/src/lib/Dotenv/Repository/RepositoryBuilder.php';
require_once __ROOT__ . '/src/lib/Dotenv/Store/StoreBuilder.php';
require_once __ROOT__ . '/src/lib/Dotenv/Parser/Parser.php';
require_once __ROOT__ . '/src/lib/Dotenv/Loader/Loader.php';
require_once __ROOT__ . '/src/lib/Dotenv/Dotenv.php';

// Load .env variables
$dotenv = Dotenv\Dotenv::createImmutable(__ROOT__);
$dotenv->load();

class Database
{
    private static $pdo = null;

    public static function connect()
    {
        if (self::$pdo)
            return;

        $host = getenv('DB_HOST') ?: 'localhost';
        $db = getenv('DB_NAME') ?: 'fittrack';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $charset = 'utf8mb4';
        $db_uri = getenv('DB_URI') ?: "mysql:host={$host};dbname={$db};charset={$charset}";

        // Debug: show the DSN being used
        // echo "DB_URI:". $db_uri . PHP_EOL;

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        self::$pdo = new PDO($db_uri, $user, $pass, $options);
    }

    public static function getInstance()
    {
        if (self::$pdo === null) {
            self::connect();
        }
        return self::$pdo;
    }
}