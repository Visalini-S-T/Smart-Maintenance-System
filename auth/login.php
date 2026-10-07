<?php

require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {

    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| Only Allow POST Requests
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header('Location: ../index.php');

    exit;
}


/*
|--------------------------------------------------------------------------
| Get Login Data
|--------------------------------------------------------------------------
*/

$email =
    trim($_POST['email'] ?? '');

$password =
    $_POST['password'] ?? '';


/*
|--------------------------------------------------------------------------
| Validate Input
|--------------------------------------------------------------------------
*/

if (
    !filter_var(
        $email,
        FILTER_VALIDATE_EMAIL
    )
    ||
    strlen($password) < 8
) {

    header(
        'Location: ../index.php?error=Invalid credentials'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Find User
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare("
    SELECT
        id,
        name,
        email,
        password_hash,
        role,
        is_active
    FROM users
    WHERE email = ?
    LIMIT 1
");

$stmt->execute([$email]);

$user = $stmt->fetch();


/*
|--------------------------------------------------------------------------
| Verify User
|--------------------------------------------------------------------------
*/

if (
    !$user
    ||
    !$user['is_active']
    ||
    !password_verify(
        $password,
        $user['password_hash']
    )
) {

    header(
        'Location: ../index.php?error=Invalid email or password'
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Regenerate Session ID
|--------------------------------------------------------------------------
*/

session_regenerate_id(true);


/*
|--------------------------------------------------------------------------
| Store User Session
|--------------------------------------------------------------------------
*/

$_SESSION['user_id'] =
    $user['id'];

$_SESSION['name'] =
    $user['name'];

$_SESSION['email'] =
    $user['email'];

$_SESSION['role'] =
    $user['role'];


/*
|--------------------------------------------------------------------------
| Redirect To Dashboard
|--------------------------------------------------------------------------
*/

header(
    'Location: ../dashboard.php'
);

exit;