<?php
/**
 * Public entry — auto-detects install state.
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/install/migrate.php';

$installed = false;
if (db_config_exists()) {
    $pdo = db();
    if ($pdo) {
        $check = check_required_tables();
        if ($check['ok']) $installed = true;
    }
}

if (!$installed) {
    if (strpos($_SERVER['REQUEST_URI'] ?? '', '/install/') === false) {
        header('Location: ' . base_url('install/'));
        exit;
    }
    require __DIR__ . '/install/index.php';
    exit;
}

$bodyClass = 'page-storefront';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/homepage.php';
require __DIR__ . '/includes/footer.php';
