<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): void
{
    if (!current_user()) {
        header('Location: ' . app_url('/public/login.php'));
        exit;
    }
}

function has_role(array $roles): bool
{
    $u = current_user();
    return $u && in_array($u['role'], $roles, true);
}

function login(string $email, string $password): bool
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND is_active = 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    audit($pdo, (int)$user['id'], 'login', 'auth');
    return true;
}

function logout_user(): void
{
    if (current_user()) {
        audit(db(), (int)$_SESSION['user']['id'], 'logout', 'auth');
    }

    $_SESSION = [];
    session_destroy();
}
