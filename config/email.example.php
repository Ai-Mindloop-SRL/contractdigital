<?php
/**
 * Email Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to: config/email.php
 * 2. Replace the placeholder values with your actual SMTP credentials
 * 3. NEVER commit config/email.php to GitHub (it's in .gitignore)
 */

// SMTP Settings
define('EMAIL_HOST', 'mail.contractdigital.ro');
define('EMAIL_PORT', 465);
define('EMAIL_USERNAME', 'noreply@contractdigital.ro');
define('EMAIL_PASSWORD', 'your_email_password_here');
define('EMAIL_FROM', 'noreply@contractdigital.ro');
define('EMAIL_FROM_NAME', 'ContractDigital');

// CC/BCC (leave empty if not needed)
define('EMAIL_CC_ADDRESS', '');  // Empty = no global CC
define('EMAIL_BCC_ADDRESS', '');

// Email settings
define('EMAIL_CHARSET', 'UTF-8');
define('EMAIL_SMTP_SECURE', 'ssl');  // 'ssl' or 'tls'
define('EMAIL_SMTP_AUTH', true);
define('EMAIL_SMTP_DEBUG', 0);  // 0 = off, 1 = client, 2 = client and server

?>
