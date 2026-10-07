<?php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| Require Login
|--------------------------------------------------------------------------
*/

function require_login(): void
{
    if (empty($_SESSION['user_id'])) {

        header(
            'Location: /Smart-Maintenance-System/index.php'
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Require Specific Role
|--------------------------------------------------------------------------
*/

function require_role(array $roles): void
{
    require_login();

    $user_role = $_SESSION['role'] ?? null;

    if (
        !$user_role ||
        !in_array($user_role, $roles, true)
    ) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}


/*
|--------------------------------------------------------------------------
| Get Current User
|--------------------------------------------------------------------------
*/

function current_user(): array
{
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['name'] ?? 'Guest',
        'role' => $_SESSION['role'] ?? null,
        'email' => $_SESSION['email'] ?? null
    ];
}