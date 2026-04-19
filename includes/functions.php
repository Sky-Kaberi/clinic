<?php

declare(strict_types=1);

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function flash(?string $message = null, string $type = 'success'): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}


function app_base_path(): string
{
    static $basePath = null;

    if ($basePath !== null) {
        return $basePath;
    }

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptName === '') {
        $basePath = '';
        return $basePath;
    }

    $base = (string) preg_replace('#/public/[^/]+\.php$#', '', $scriptName);
    if ($base === $scriptName) {
        $base = (string) preg_replace('#/[^/]+\.php$#', '', $scriptName);
    }

    $basePath = rtrim($base, '/');
    return $basePath;
}

function app_url(string $path = ''): string
{
    $base = app_base_path();
    $normalizedPath = '/' . ltrim($path, '/');

    return ($base === '' ? '' : $base) . $normalizedPath;
}

function module_url(string $module, array $query = []): string
{
    $params = array_merge(['module' => $module], $query);
    return app_url('/public/index.php') . '?' . http_build_query($params);
}

function uhid(): string
{
    return 'UHID' . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));
}

function money(float $value): string
{
    return number_format($value, 2);
}

function audit(PDO $pdo, int $userId, string $action, string $module, ?string $entityType = null, ?int $entityId = null): void
{
    $stmt = $pdo->prepare('INSERT INTO audit_logs(user_id, action, module, entity_type, entity_id) VALUES (?,?,?,?,?)');
    $stmt->execute([$userId, $action, $module, $entityType, $entityId]);
}

function normalize_optional(?string $value): ?string
{
    $value = trim((string) $value);
    return $value === '' ? null : $value;
}

function is_valid_mobile(string $mobile): bool
{
    return (bool) preg_match('/^[0-9]{10,15}$/', $mobile);
}

function is_valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function require_roles(array $roles): void
{
    if (!has_role($roles)) {
        flash('You are not authorized to access this module.', 'danger');
        header('Location: ' . module_url('dashboard'));
        exit;
    }
}
