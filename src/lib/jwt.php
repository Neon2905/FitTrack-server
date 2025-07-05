<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function createJWT($payload)
{
    $secret = $_ENV['JWT_SECRET'];

    $expire = $_ENV['JWT_EXPIRATION'] ?: 3600 * 24 * 30; // Default 30 days

    if ($secret === false) {
        throw new Exception('JWT_SECRET environment variable not set');
    }

    $payload['iat'] = time();
    $payload['exp'] = time() + (int)$expire; // Token expires in 1 hour

    // You can set the algorithm as needed, default is HS256
    return JWT::encode($payload, $secret, 'HS256');
}

function decodeJWT($jwt)
{
    $secret = $_ENV['JWT_SECRET'];

    if ($secret === false) {
        throw new Exception('JWT_SECRET environment variable not set');
    }

    // Returns the payload as an array
    return (array) JWT::decode($jwt, new Key($secret, 'HS256'));
}
