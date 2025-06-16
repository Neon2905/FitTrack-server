<?php
require_once __DIR__ . '/Database.php';

function createUsersTable() {
    $pdo = Database::getInstance();
    $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            age INT,
            gender ENUM('male', 'female', 'other'),
            height_cm FLOAT,
            weight_kg FLOAT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ";
    $pdo->exec($sql);
    echo 'Users table created.' . PHP_EOL;
}