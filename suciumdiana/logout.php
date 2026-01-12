<?php
session_start();

// Get site slug from directory name
$site_slug = basename(__DIR__);

// Clear session
session_unset();
session_destroy();

// Load config for SUBFOLDER constant
require_once __DIR__ . '/../config/app.php';

// Redirect to login
header('Location: ' . SUBFOLDER . '/' . $site_slug . '/login.php?logout=1');
exit;
?>