<?php
/**
 * Security Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to: config/security.php
 * 2. Generate new random keys (see commands below)
 * 3. NEVER commit config/security.php to GitHub (it's in .gitignore)
 * 
 * Generate encryption key:
 * openssl rand -base64 32
 * 
 * Generate IV:
 * openssl rand -hex 16
 */

// Encryption Settings (for CNP, CI, sensitive data)
define('ENCRYPTION_KEY', 'your-32-character-encryption-key-generate-with-openssl');
define('ENCRYPTION_METHOD', 'AES-256-CBC');
define('ENCRYPTION_IV', 'your-16-byte-iv-generate-with-openssl');

// Session Security
define('SESSION_LIFETIME', 3600);  // 1 hour in seconds
define('SESSION_NAME', 'ContractDigital_Session');
define('SESSION_SECURE', true);    // Only over HTTPS
define('SESSION_HTTPONLY', true);  // Not accessible via JavaScript
define('SESSION_SAMESITE', 'Strict');

// Password Hashing
define('PASSWORD_COST', 12);  // bcrypt cost factor (10-12 recommended)

// CSRF Protection
define('CSRF_TOKEN_LENGTH', 32);
define('CSRF_TOKEN_LIFETIME', 3600);  // 1 hour

?>
