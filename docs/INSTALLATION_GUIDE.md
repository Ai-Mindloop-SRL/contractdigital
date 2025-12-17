# 📦 Installation Guide - ContractDigital Platform

> **Central installation documentation for all clients and templates**

---

## 🎯 Quick Navigation

- **For Mindloop**: See [MINDLOOP.md](./MINDLOOP.md)
- **For RoseUp Advisors**: See [ROSEUP.md](./ROSEUP.md)
- **For New Clients**: See [CLIENT_TEMPLATE.md](./CLIENT_TEMPLATE.md)
- **Database Setup**: See [../database/README.md](../database/README.md)
- **Deployment**: See [../DEPLOYMENT.md](../DEPLOYMENT.md)

---

## 📋 Prerequisites

### Server Requirements
- PHP 7.4+ (recommended: PHP 8.0+)
- MySQL 5.7+ or MariaDB 10.3+
- Apache/Nginx with mod_rewrite enabled
- Composer (for dependencies)

### Required PHP Extensions
```bash
php -m | grep -E "pdo|pdo_mysql|mbstring|openssl|json"
```

### FTP/cPanel Access
- **FTP Server**: `ftp.siteq.ro`
- **FTP User**: `claude_ai@siteq.ro`
- **cPanel**: https://siteq.ro:2083/
- **phpMyAdmin**: https://siteq.ro:2083/phpMyAdmin/

---

## 🚀 Installation Steps (Generic)

### 1. Database Setup

#### Create Database
```sql
CREATE DATABASE IF NOT EXISTS r68649site_CLIENT_db 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;
```

#### Run Migrations
```bash
# From database/migrations/
mysql -u DB_USER -p DB_NAME < 001_initial_schema.sql
mysql -u DB_USER -p DB_NAME < 002_add_lowercase_support.sql
```

#### Verify Installation
```sql
SELECT 
    (SELECT COUNT(*) FROM sites) as sites_count,
    (SELECT COUNT(*) FROM users WHERE role='admin') as admin_count,
    (SELECT COUNT(*) FROM field_definitions) as fields_count,
    (SELECT COUNT(*) FROM contract_templates WHERE is_active=1) as templates_count;
```

**Expected Results:**
- Sites: 1+
- Admin users: 1+
- Fields: ~35
- Active templates: 1+

---

### 2. Configuration Files

#### A. Database Configuration

Create `config/database.php` from example:
```bash
cp config/database.example.php config/database.php
nano config/database.php
```

```php
<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'r68649site_CLIENT_db');
define('DB_USER', 'r68649site_contractdigital_ro');
define('DB_PASS', 'hc2od5atuo3fb46g');
define('DB_CHARSET', 'utf8mb4');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Eroare conexiune bază de date. Verificați configurația.");
}
?>
```

#### B. Email Configuration (PHPMailer)

Create `config/email.php`:
```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../includes/phpmailer/src/Exception.php';
require __DIR__ . '/../includes/phpmailer/src/PHPMailer.php';
require __DIR__ . '/../includes/phpmailer/src/SMTP.php';

function sendEmail($to, $subject, $body, $from_name = null) {
    $mail = new PHPMailer(true);
    
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@domain.com';
        $mail->Password = 'your-smtp-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('noreply@contractdigital.ro', $from_name ?? 'ContractDigital');
        $mail->addAddress($to);
        
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
```

---

### 3. Directory Structure

Create required directories:
```bash
mkdir -p uploads/contracts
mkdir -p uploads/signatures
chmod 755 uploads/
chmod 755 uploads/contracts/
chmod 755 uploads/signatures/
```

Create `.htaccess` for upload protection:
```apache
# uploads/.htaccess
Order Deny,Allow
Deny from all
<FilesMatch "\.(pdf|png|jpg|jpeg)$">
    Allow from all
</FilesMatch>
```

---

### 4. Verification Checklist

Run this verification script:
```bash
# Save as verify_installation.php
<?php
echo "ContractDigital Installation Verification\n";
echo "==========================================\n\n";

// Check PHP version
echo "PHP Version: " . phpversion() . "\n";

// Check required extensions
$required = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json'];
foreach ($required as $ext) {
    echo "Extension $ext: " . (extension_loaded($ext) ? '✅' : '❌') . "\n";
}

// Check database connection
echo "\nDatabase Connection: ";
try {
    require_once 'config/database.php';
    echo "✅\n";
} catch (Exception $e) {
    echo "❌ " . $e->getMessage() . "\n";
}

// Check directories
echo "\nDirectory Permissions:\n";
$dirs = ['uploads/', 'uploads/contracts/', 'uploads/signatures/'];
foreach ($dirs as $dir) {
    $perms = is_writable($dir) ? '✅ (writable)' : '❌ (not writable)';
    echo "$dir: $perms\n";
}
?>
```

Run verification:
```bash
php verify_installation.php
```

---

## 🔒 Security Hardening

### 1. Change Default Admin Password

```sql
-- Generate new bcrypt hash: https://www.bcrypt-generator.com/
UPDATE users 
SET password = '$2y$10$YOUR_NEW_HASH_HERE'
WHERE username = 'admin' AND role = 'admin';
```

### 2. Protect Configuration Files

Add to root `.htaccess`:
```apache
<FilesMatch "^(database|email|app)\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
```

### 3. Enable Error Logging

Add to `php.ini` or `.htaccess`:
```ini
php_flag display_errors off
php_flag log_errors on
php_value error_log /path/to/error_log
```

---

## 🐛 Common Issues & Solutions

### ❌ "Failed to open stream: config/database.php"

**Cause**: Incorrect file path  
**Solution**: Verify path in all PHP files:
```php
require_once __DIR__ . '/config/database.php';  // From root
require_once __DIR__ . '/../config/database.php';  // From subdirectory
```

### ❌ "Unknown database"

**Cause**: Database not created or wrong name  
**Solution**:
1. Verify database exists in phpMyAdmin
2. Check `config/database.php` → `DB_NAME`

### ❌ Email Not Sending

**Cause**: SMTP configuration incorrect  
**Solution**:
1. Verify credentials in `config/email.php`
2. Test SMTP: https://www.gmass.co/smtp-test
3. Check error logs: `tail -f error_log`

### ❌ PDF Without Romanian Characters (ă, î, ș, ț)

**Cause**: Wrong font encoding  
**Solution**: Use DejaVu fonts in `includes/ContractPDF.php`:
```php
$this->SetFont('dejavusans', '', 11);  // ✅ Correct
// DON'T use: Arial, Helvetica (no Romanian support)
```

### ❌ Signature Not Appearing in PDF

**Cause**: Wrong path or permissions  
**Solution**:
```bash
chmod 755 uploads/signatures/
# Verify path in code:
echo SIGNATURE_DIR;  // Must be absolute path
```

---

## 📞 Support Resources

### Documentation
- **Main README**: [../README.md](../README.md)
- **Deployment Guide**: [../DEPLOYMENT.md](../DEPLOYMENT.md)
- **Database Guide**: [../database/README.md](../database/README.md)
- **Configuration**: [../config/README.md](../config/README.md)

### Server Access
- **FTP**: ftp.siteq.ro (Port 21)
- **cPanel**: https://siteq.ro:2083/
- **phpMyAdmin**: https://siteq.ro:2083/phpMyAdmin/

### Database Credentials
- **DB Name**: `r68649site_contractdigital_db`
- **DB User**: `r68649site_contractdigital_ro`
- **DB Pass**: `hc2od5atuo3fb46g`

### Live URLs
- **Mindloop**: https://contractdigital.ro/ro/mindloop/
- **RoseUp**: https://contractdigital.ro/ro/roseupadvisors/
- **Client Template**: https://contractdigital.ro/ro/_client_template/

---

## ✅ Final Checklist

Before going live, verify:

- [ ] Database created with correct charset (utf8mb4_unicode_ci)
- [ ] All migrations applied successfully
- [ ] `config/database.php` configured with correct credentials
- [ ] `config/email.php` configured with valid SMTP
- [ ] Upload directories created with correct permissions
- [ ] `.htaccess` files in place for security
- [ ] Admin password changed from default
- [ ] Test contract creation workflow
- [ ] Test email sending
- [ ] Test PDF generation (verify Romanian characters)
- [ ] Test digital signature workflow

---

**Installation Time**: ~30-60 minutes  
**Prepared by**: Claude Code  
**Last Updated**: 2025-12-17  
**Version**: 2.0
