<?php
// Debug POST handler - simplu
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/post_errors.log');

header('Content-Type: application/json');

try {
    file_put_contents(__DIR__ . '/post_debug.log', date('Y-m-d H:i:s') . " - POST received\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/post_debug.log', "POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents(__DIR__ . '/post_debug.log', "FILES data: " . print_r($_FILES, true) . "\n", FILE_APPEND);
    
    // Include config
    require_once __DIR__ . '/../config/database.php';
    file_put_contents(__DIR__ . '/post_debug.log', "DB connected\n", FILE_APPEND);
    
    echo json_encode(['success' => true, 'message' => 'Debug OK']);
    
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/post_debug.log', "ERROR: " . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
