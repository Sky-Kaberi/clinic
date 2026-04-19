<?php

declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function render_header(string $title): void
{
    $f = flash();
    $user = $_SESSION['user'] ?? null;
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($title) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-3">
  <div class="container-fluid">
    <a class="navbar-brand" href="/public/index.php">ClinicMS</a>
    <?php if ($user): ?>
    <div class="text-white small">Logged in as <?= e($user['name']) ?> (<?= e($user['role']) ?>)</div>
    <a class="btn btn-sm btn-outline-light ms-3" href="/public/logout.php">Logout</a>
    <?php endif; ?>
  </div>
</nav>
<div class="container-fluid px-3">
<?php if ($f): ?>
<div class="alert alert-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
<?php endif; ?>
<?php
}

function render_footer(): void
{
    ?>
</div>
</body>
</html>
<?php
}

function module_nav(): void
{
    $links = [
        'dashboard' => 'Dashboard',
        'patients' => 'Patients',
        'appointments' => 'Appointments',
        'visits' => 'OPD Visits',
        'diagnostics' => 'Diagnostics',
        'lab' => 'Laboratory',
        'radiology' => 'Radiology',
        'billing' => 'Billing',
        'inventory' => 'Inventory',
        'communications' => 'Communication',
        'reports' => 'MIS Reports',
        'users' => 'Users',
    ];
    echo '<div class="mb-3">';
    foreach ($links as $m => $label) {
        echo '<a class="btn btn-sm btn-outline-primary me-2 mb-2" href="/public/index.php?module=' . e($m) . '">' . e($label) . '</a>';
    }
    echo '</div>';
}
