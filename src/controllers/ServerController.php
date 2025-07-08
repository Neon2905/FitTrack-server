<?php

require_once __DIR__ . "/../utils/httpHelper.php";

class ServerController
{
    public static function test($body)
    {
        respond([], 200);
    }

    public static function check($body)
    {
        respond([], 200);
    }

    public static function testCookie($body)
    {
        generateTokenAndSetCookie("someone", "someone");
        respond([], 200);
    }
}