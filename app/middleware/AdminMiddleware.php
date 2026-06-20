<?php

require_once __DIR__ . '/AuthMiddleware.php';

class AdminMiddleware
{
    public static function check()
    {
        // First ensure user is logged in
        AuthMiddleware::check();

        // Check if the user role is admin
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            abort_403();
        }
    }
}
