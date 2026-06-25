<?php

require_once __DIR__ . '/AuthMiddleware.php';

class TempPasswordMiddleware
{
    /**
     * Redirect users with a temporary password to the forced change-password page.
     */
    public static function enforce(array $user, ?string $currentPage = null): void
    {
        if (empty($user['is_temp_password'])) {
            return;
        }

        $currentPage = $currentPage ?? ($_GET['page'] ?? '');
        $allowedPages = ['auth-change-password', 'logout'];

        if (!in_array($currentPage, $allowedPages, true)) {
            redirect('auth-change-password');
        }
    }
}
