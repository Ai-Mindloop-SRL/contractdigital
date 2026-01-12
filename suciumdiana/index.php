<?php
/**
 * Client Site Home
 * 
 * Purpose: Entry point - redirect to login if not authenticated, otherwise to templates
 */

session_start();

// Get site slug from current directory
$site_slug = basename(__DIR__);

require_once __DIR__ . '/../config/app.php';

// Check if already logged in to THIS site
if (isset($_SESSION['client_logged_in']) && 
    $_SESSION['client_logged_in'] === true &&
    $_SESSION['site_slug'] === $site_slug) {
    // Already logged in - go to templates
    redirect($site_slug . '/templates.php');
} else {
    // Not logged in - go to login page
    redirect($site_slug . '/login.php');
}
?>
