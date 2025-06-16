<?php
require_once __DIR__ . '/db/migration.php';

if (php_sapi_name() === 'cli') {
    createUsersTable();
}