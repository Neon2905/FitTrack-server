<?php
class AuthController {
    public static function register($body) {
        echo json_encode(['received' => $body]);
    }

    public static function login($body) {
        echo json_encode(['received' => $body]);
    }
}