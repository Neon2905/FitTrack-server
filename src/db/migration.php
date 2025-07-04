<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Database.php';
Database::connect();

function createUserTable()
{
    $pdo = Database::getInstance();
    $sql =
        "CREATE TABLE IF NOT EXISTS user (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(100),
            age INT,
            gender ENUM('male', 'female', 'other'),
            height_cm FLOAT,
            weight_kg FLOAT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
    $pdo->exec($sql);
    echo 'User table created.' . PHP_EOL;
}