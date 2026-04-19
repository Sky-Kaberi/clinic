<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/layout.php';

require_login();

$allowed = ['dashboard','patients','appointments','visits','diagnostics','lab','radiology','billing','inventory','communications','reports','users'];
$module = $_GET['module'] ?? 'dashboard';
if (!in_array($module, $allowed, true)) {
    $module = 'dashboard';
}

render_header('ClinicMS');
module_nav();
require __DIR__ . '/../modules/' . $module . '.php';
render_footer();
