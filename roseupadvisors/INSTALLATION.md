# 📦 INSTALARE CLIENT NOU - ContractDigital

## ✅ VERIFICARE COMPLETĂ

**Status**: ✅ PACHET COMPLET PREGĂTIT  
**Versiune**: 1.0  
**Data**: 2025-12-03  
**Locație FTP**: `ftp.siteq.ro/_client_template/`

---

## 📋 CE ESTE INCLUS

### 1️⃣ **APLICAȚIE PHP** (Funcțională completă)

```
_client_template/
├── fill_and_sign.php          # Formular generic (fără Montessori custom)
├── sign_contract.php           # Procesare semnătură
├── auth_check.php             # Verificare autentificare
├── contract_detail.php        # Detalii contract
├── download_pdf.php           # Descărcare PDF
├── edit_template.php          # Editor șabloane
├── index.php                  # Dashboard principal
├── login.php                  # Autentificare
├── logout.php                 # Deconectare
├── send_contract.php          # Trimitere email
├── status.php                 # Status contract
├── templates.php              # Gestionare șabloane
└── view_contract.php          # Vizualizare contract
```

### 2️⃣ **CONFIGURAȚIE** (Exemple de completat)

```
_client_template/config/
├── app.php.example            # Configurare aplicație
├── database.php.example       # Configurare bază de date
└── email.php.example          # Configurare email (PHPMailer)
```

### 3️⃣ **LIBRĂRII PHP** (Copii din Montessori)

```
_client_template/includes/
├── ContractPDF.php            # Generare PDF (TCPDF)
├── helpers.php                # Funcții helper
└── template_versioning.php    # Versiuni șabloane
```

### 4️⃣ **BAZĂ DE DATE** (Schema + Date inițiale)

```
_client_template/SQL/
├── schema.sql                 # Structura DB (CREATE TABLE)
└── seed.sql                   # Date inițiale (admin + template)
```

### 5️⃣ **DOCUMENTAȚIE**

```
_client_template/
├── README.md                  # Prezentare pachet
└── INSTALLATION.md           # Ghid instalare (acest fișier)
```

---

## 🚀 PAȘI DE INSTALARE

### **PASUL 1: Pregătire subdirectory client**

#### A. Creare folder pe server

1. **Via FTP**:
   ```bash
   Conectare: ftp.siteq.ro
   User: claude_ai@siteq.ro
   Pass: igkcwismekdgqndp
   
   Creare folder:
   /public_html/contractdigital.ro/ro/CLIENT_NUME/
   ```

2. **Via cPanel**: https://siteq.ro:2083/
   - File Manager → `public_html/contractdigital.ro/ro/`
   - Creare folder: `CLIENT_NUME`

#### B. Copiază fișiere din `_client_template`

```bash
# Copiază toate fișierele din _client_template → CLIENT_NUME/
cp -r _client_template/* CLIENT_NUME/
```

**Important**: NU copia folderul `SQL/` pe server public!

---

### **PASUL 2: Configurare bază de date**

#### A. Creare bază de date (phpMyAdmin)

**URL**: https://siteq.ro:2083/phpMyAdmin/

1. **Creare nouă bază de date**:
   - Nume: `r68649site_CLIENT_db`
   - Collation: `utf8mb4_unicode_ci`

2. **Rulare schema.sql**:
   ```sql
   -- phpMyAdmin → Import → schema.sql
   -- Creează toate tabelele
   ```

3. **Editare seed.sql** (IMPORTANT):
   ```sql
   -- Deschide seed.sql în editor text
   -- Modifică valorile:
   
   SET @SITE_NAME = 'Nume Client Complet';
   SET @SITE_DOMAIN = 'client-nume.contractdigital.ro';
   SET @ADMIN_EMAIL = 'admin@client-domeniu.ro';
   ```

4. **Rulare seed.sql**:
   ```sql
   -- phpMyAdmin → Import → seed.sql (modificat)
   -- Creează admin user + template standard
   ```

#### B. Verificare instalare DB

```sql
-- Rulează în phpMyAdmin SQL tab:

SELECT * FROM sites;
-- Trebuie să vezi 1 rând cu client nou

SELECT * FROM users WHERE role='admin';
-- Trebuie să vezi admin user

SELECT COUNT(*) as total_fields FROM field_definitions;
-- Trebuie să vezi ~35 fields

SELECT COUNT(*) as total_mappings FROM template_field_mapping;
-- Trebuie să vezi ~17 mappings

SELECT * FROM contract_templates WHERE is_active=1;
-- Trebuie să vezi 1 template "Contract Standard"
```

---

### **PASUL 3: Configurare fișiere**

#### A. `config/database.php`

```bash
# Redenumire
mv config/database.php.example config/database.php

# Editare
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
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch(PDOException $e) {
    die("Eroare conexiune: " . $e->getMessage());
}
?>
```

#### B. `config/app.php`

```bash
# Redenumire
mv config/app.php.example config/app.php

# Editare
nano config/app.php
```

```php
<?php
// ===== MODIFICĂ AICI =====
define('BASE_URL', 'https://contractdigital.ro/ro/CLIENT_NUME');
define('SITE_NAME', 'Nume Client Complet');
define('SITE_ID', 2);  // ID-ul din tabela 'sites'

// ===== Nu modifica =====
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('SIGNATURE_DIR', UPLOAD_DIR . 'signatures/');
define('CONTRACT_DIR', UPLOAD_DIR . 'contracts/');
define('TEMPLATE_DIR', __DIR__ . '/../templates/');

// Creare foldere necesare
if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(SIGNATURE_DIR)) mkdir(SIGNATURE_DIR, 0755, true);
if (!is_dir(CONTRACT_DIR)) mkdir(CONTRACT_DIR, 0755, true);
if (!is_dir(TEMPLATE_DIR)) mkdir(TEMPLATE_DIR, 0755, true);
?>
```

**Important**: Verifică `SITE_ID` din query:
```sql
SELECT id FROM sites WHERE site_domain = 'client-nume.contractdigital.ro';
```

#### C. `config/email.php`

```bash
# Redenumire
mv config/email.php.example config/email.php

# Editare
nano config/email.php
```

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
        // ===== MODIFICĂ AICI =====
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // sau alt server SMTP
        $mail->SMTPAuth = true;
        $mail->Username = 'email@client-domeniu.ro';
        $mail->Password = 'parola-smtp';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        $mail->setFrom('noreply@client-domeniu.ro', $from_name ?? SITE_NAME);
        $mail->addAddress($to);
        
        $mail->CharSet = 'UTF-8';
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return false;
    }
}
?>
```

---

### **PASUL 4: Creare foldere necesare**

```bash
# Via SSH sau FTP, creează:
CLIENT_NUME/
├── uploads/
│   ├── contracts/       # PDFs generate
│   ├── signatures/      # Semnături digitale
│   └── .htaccess       # Protecție acces
└── templates/          # Șabloane HTML locale (opțional)
```

**Conținut `.htaccess` în uploads/**:
```apache
Order Deny,Allow
Deny from all
<FilesMatch "\.(pdf|png|jpg)$">
    Allow from all
</FilesMatch>
```

---

### **PASUL 5: Verificare instalare**

#### A. Test acces aplicație

1. **Dashboard admin**:
   ```
   https://contractdigital.ro/ro/CLIENT_NUME/
   ```
   
   - Ar trebui să redirecționeze la `login.php`
   - Autentificare:
     - Username: `admin`
     - Password: `admin123`

2. **Verifică meniu**:
   - ✅ Șabloane → Ar trebui să vezi "Contract Standard"
   - ✅ Contracte → Lista goală inițial
   - ✅ Logout → Funcțional

#### B. Test creare contract

1. **Creare contract nou** → `templates.php`:
   - Selectează "Contract Standard"
   - Completează formular
   - Verifică că toate field-urile apar

2. **Trimitere pentru semnare** → `send_contract.php`:
   - Email destinatar
   - Verifică că email sosește
   - Link format: `https://contractdigital.ro/ro/CLIENT_NUME/fill_and_sign.php?token=XXXX`

3. **Completare și semnare** → `fill_and_sign.php`:
   - Deschide link din email
   - Completează câmpuri
   - Generează PDF → verifică diacritice
   - Semnează → `sign_contract.php`

4. **Descărcare PDF final** → `download_pdf.php`:
   - Verifică că PDF conține date corecte
   - Verifică diacritice românești (ă, â, î, ș, ț)

---

## 🔒 POST-INSTALARE - SECURITATE

### 1. **Schimbă parola admin**

```sql
-- Generează hash nou pentru parolă:
-- https://www.bcrypt-generator.com/

UPDATE users 
SET password = '$2y$10$YOUR_NEW_HASH_HERE'
WHERE username = 'admin' AND site_id = YOUR_SITE_ID;
```

### 2. **Restricționare acces FTP**

```bash
# În .htaccess root:
<Files "config/*.php">
    Order Allow,Deny
    Deny from all
</Files>
```

### 3. **Backup automat**

- Configurare cron pentru backup DB
- Backup periodic folder `uploads/`

---

## 📊 DIFERENȚE față de Montessori

| Funcționalitate | Montessori (Custom) | Client Generic |
|-----------------|---------------------|----------------|
| **Locație** | `/montessori/fill_and_sign.php` | `/CLIENT_NUME/fill_and_sign.php` |
| **Sincronizare Anexe** | ✅ DA (Program Anexa 1/2) | ❌ NU |
| **Checkbox "Nu am date"** | ✅ DA (mamă/tată) | ❌ NU |
| **grupa_clasa radio** | ✅ DA (Template 7) | ❌ NU |
| **Preview complex** | ✅ DA (live checkboxes) | ⚠️ SIMPLU (placeholders) |
| **Fields dinamice** | ✅ DA (din DB) | ✅ DA (din DB) |
| **PDF generation** | ✅ DA (TCPDF) | ✅ DA (TCPDF) |
| **Signature** | ✅ DA | ✅ DA |

---

## 🐛 TROUBLESHOOTING

### ❌ **Eroare: "Failed to open stream: config/database.php"**

**Cauză**: Path relativ greșit  
**Soluție**:
```php
// În toate fișierele PHP, verifică:
require_once __DIR__ . '/config/database.php';  // ✅ CORECT
require_once __DIR__ . '/../config/database.php';  // Dacă în subfolder
```

### ❌ **Eroare: "Unknown database 'r68649site_CLIENT_db'"**

**Cauză**: DB nu există sau nume greșit  
**Soluție**:
1. Verifică DB creat în phpMyAdmin
2. Verifică `config/database.php` → `DB_NAME`

### ❌ **Email nu sosește**

**Cauză**: SMTP config greșit  
**Soluție**:
1. Verifică `config/email.php` → credentials SMTP
2. Test SMTP: https://www.gmass.co/smtp-test
3. Verifică logs: `tail -f error_log`

### ❌ **PDF fără diacritice**

**Cauză**: Font encoding  
**Soluție**:
```php
// În ContractPDF.php, verifică:
$this->SetFont('dejavusans', '', 11);  // ✅ CORECT
// NU folosi: Arial, Helvetica (nu suportă ă, â, î, ș, ț)
```

### ❌ **Semnătură nu apare în PDF**

**Cauză**: Path greșit sau permisiuni  
**Soluție**:
```bash
# Verifică permisiuni
chmod 755 uploads/
chmod 755 uploads/signatures/

# Verifică path în sign_contract.php
echo SIGNATURE_DIR;  // Trebuie să fie absolut
```

---

## 📞 SUPPORT

### **Acces Server**
- **FTP**: `ftp.siteq.ro` → `claude_ai@siteq.ro` / `igkcwismekdgqndp`
- **cPanel**: https://siteq.ro:2083/
- **phpMyAdmin**: https://siteq.ro:2083/phpMyAdmin/

### **Database**
- **DB Name**: `r68649site_contractdigital_db`
- **DB User**: `r68649site_contractdigital_ro`
- **DB Pass**: `hc2od5atuo3fb46g`

### **URLs**
- **Montessori (Production)**: https://contractdigital.ro/ro/montessori/
- **Client Template**: https://contractdigital.ro/ro/_client_template/
- **Client Nou**: https://contractdigital.ro/ro/CLIENT_NUME/

---

## ✅ CHECKLIST FINAL

- [ ] Bază de date creată și populată (schema.sql + seed.sql)
- [ ] `config/database.php` configurat cu DB nou
- [ ] `config/app.php` configurat (BASE_URL, SITE_ID, SITE_NAME)
- [ ] `config/email.php` configurat cu SMTP
- [ ] Foldere create: `uploads/`, `uploads/contracts/`, `uploads/signatures/`
- [ ] Permisiuni setate: `chmod 755` pe foldere
- [ ] `.htaccess` creat în `uploads/`
- [ ] Test login cu admin/admin123
- [ ] Parolă admin schimbată
- [ ] Test creare contract
- [ ] Test trimitere email
- [ ] Test completare formular (fill_and_sign.php)
- [ ] Test generare PDF (verificat diacritice)
- [ ] Test semnare contract (sign_contract.php)
- [ ] Test descărcare PDF final

---

## 📝 NOTES

- **fill_and_sign.php GENERIC**: Nu include funcționalități custom Montessori
- **Extensibilitate**: Pentru features noi, editează `fill_and_sign.php` local
- **Sincronizare DB**: Toate field-uri sunt dinamice din `field_definitions`
- **Multi-tenancy**: Sistem suportă multiple site-uri (SITE_ID diferit)
- **Backup**: **IMPORTANT** - backup înainte de modificări major

---

**Instalare completă estimată**: 30-60 minute  
**Pregătit de**: Claude Code  
**Data**: 2025-12-03  
**Versiune**: 1.0
