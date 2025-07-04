<?php
require_once __DIR__ . '/src/utils/Env.php';

$dbHost = Env::get('DB_HOST');
$jwtSecret = Env::get('JWT_SECRET');

echo "DB_HOST: " . $dbHost . "\n";
echo "JWT_SECRET: " . $jwtSecret . "\n";