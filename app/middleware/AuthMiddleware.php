<?php

class AuthMiddleware
{
    public static function check()
    {
        if (!isset($_SESSION['user_id'])) {

            header('Location: ?page=login');

            exit;
        }
    }
}