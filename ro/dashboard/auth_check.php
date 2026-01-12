<?php
/**
 * Admin Authentication Check
 * 
 * Purpose: Verify admin is logged in before accessing dashboard pages
 * Usage: Include at the top of every admin dashboard page
 */

// Load app config for url() helper

// Start session if not already started
require_once __DIR__ . '/../includes/helpers.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Not logged in - redirect to login page
    redirect('dashboard/index.php');
}

// Check for session timeout (30 minutes)
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    
    if ($inactive_time > SESSION_TIMEOUT) {
        // Session expired
        session_unset();
        session_destroy();
        redirect('dashboard/index.php?timeout=1');
    }
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Admin is authenticated
if (!isset($_SESSION['admin_username'])) {
    // Session is corrupt - force logout
    redirect('dashboard/logout.php');
}

?>
