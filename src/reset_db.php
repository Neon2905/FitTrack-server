<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenvPath = __DIR__ . "/../";
if (file_exists($dotenvPath . ".env")) {
    $dotenv = Dotenv::createImmutable($dotenvPath);
    $dotenv->load();
}
require_once __DIR__ . '/db/Database.php';

// Get PDO instance
$pdo = Database::getInstance();

// Read schema file
$schemaFile = __DIR__ . '/../db_schema.sql';
$schemaSql = file_get_contents($schemaFile);

if ($schemaSql === false) {
    die("Could not read db_schema.sql\n");
}

// Drop all tables (order matters due to foreign keys)
$tables = [
    'analytics_cache',
    'daily_summary',
    'sync_settings',
    'sleep_sessions',
    'activity',
    'challenge',
    'user'
];

foreach ($tables as $table) {
    try {
        $pdo->exec("DROP TABLE IF EXISTS `$table`;");
        echo "Dropped table: $table\n";
    } catch (PDOException $e) {
        echo "Error dropping $table: " . $e->getMessage() . "\n";
    }
}

// Run schema
try {
    $pdo->exec($schemaSql);
    echo "Database reset and schema applied successfully.\n";
} catch (PDOException $e) {
    echo "Error applying schema: " . $e->getMessage() . "\n";
}