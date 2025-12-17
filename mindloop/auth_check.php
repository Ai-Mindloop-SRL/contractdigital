<?php
/**
 * Authentication Check for Client Portal
 * Include this at the top of every protected client page
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

// Get site slug from directory name
$site_slug = basename(__DIR__);

// Get site details
$db = getDBConnection();
$stmt = $db->prepare("SELECT * FROM sites WHERE site_slug = ? AND is_active = 1");
$stmt->bind_param("s", $site_slug);
$stmt->execute();
$site = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$site) {
    die('Site not found or inactive');
}

// Store site variables for use in pages
$site_id = $site['id'];
$site_name = $site['site_name'];
$primary_color = $site['primary_color'];
$logo_path = $site['logo_path'];
$admin_email = $site['admin_email'];

// Check if user is logged in
if (!isset($_SESSION['site_user_id']) || $_SESSION['site_slug'] !== $site_slug) {
    header('Location: ' . SUBFOLDER . '/' . $site_slug . '/login.php');
    exit;
}

// Session timeout (30 minutes)
$timeout_duration = 1800; // 30 minutes in seconds

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: ' . SUBFOLDER . '/' . $site_slug . '/login.php?timeout=1');
    exit;
}

$_SESSION['last_activity'] = time(); // Update last activity time

// Get current user info
$stmt = $db->prepare("SELECT * FROM site_users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['site_user_id']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$current_user) {
    session_destroy();
    header('Location: ' . SUBFOLDER . '/' . $site_slug . '/login.php');
    exit;
}

// Make user info available to pages
$user_id = $current_user['id'];
$username = $current_user['username'];
$user_email = $current_user['email'];
?>