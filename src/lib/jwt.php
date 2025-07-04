<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function createJWT($payload)
{
    // TODO: $secret = getenv('JWT_SECRET');
    $secret = 'jwt_secret_key';

    if ($secret === false) {
        throw new Exception('JWT_SECRET environment variable not set');
    }

    // You can set the algorithm as needed, default is HS256
    return JWT::encode($payload, $secret, 'HS256');
}

function decodeJWT($jwt)
{
    // TODO: $secret = getenv('JWT_SECRET');
    $secret = 'jwt_secret_key';
    if ($secret === false) {
        throw new Exception('JWT_SECRET environment variable not set');
    }

    // Returns the payload as an array
    return (array) JWT::decode($jwt, new Key($secret, 'HS256'));
}
