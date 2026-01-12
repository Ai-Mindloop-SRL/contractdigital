# 📦 Contract Digital - Client Template

**Version:** 1.0  
**Date:** 2025-12-03  
**Purpose:** Ready-to-deploy contract management system for new clients

---

## 🎯 What's Included

This template provides everything needed to deploy a contract management system for a new client:

### ✅ Core Features
- **Dynamic contract forms** - Generated from database
- **PDF generation** - Professional contract PDFs with TCPDF
- **Digital signatures** - Capture and embed signatures
- **Email notifications** - Send contracts via email
- **Admin panel** - Manage templates and contracts
- **Multi-tenant** - Support multiple clients in one database (via `site_id`)

### ✅ Field Types Supported
- Text, Email, Tel, Date
- Textarea (long text)
- Radio buttons (single choice)
- Checkboxes (multiple choice)
- Custom validation rules

---

## 📁 Folder Structure

```
_client_template/
├── README.md                    # This file
├── INSTALLATION.md              # Step-by-step setup guide
├── config/
│   ├── app.php.example          # Application settings
│   ├── database.php.example     # Database credentials
│   └── email.php.example        # SMTP email settings
├── includes/
│   ├── ContractPDF.php          # PDF generation library
│   ├── helpers.php              # Utility functions
│   └── template_versioning.php  # Template version control
├── admin/                       # Admin panel files
│   ├── index.php                # Dashboard
│   ├── login.php                # Authentication
│   ├── templates.php            # Template CRUD
│   ├── send_contract.php        # Send contracts
│   └── ...
├── fill_and_sign.php            # Main contract form (GENERIC)
├── sign_contract.php            # Signature capture page
└── SQL/
    ├── schema.sql               # Database structure
    └── seed.sql                 # Initial data (optional)
```

---

## 🚀 Quick Start

### **1. Copy Template**
```bash
cp -r _client_template/ /path/to/new-client/
cd /path/to/new-client/
```

### **2. Configure**
```bash
# Rename config files
mv config/app.php.example config/app.php
mv config/database.php.example config/database.php
mv config/email.php.example config/email.php

# Edit configs with your values
nano config/app.php
nano config/database.php
nano config/email.php
```

### **3. Import Database**
```bash
mysql -u your_user -p your_database < SQL/schema.sql
mysql -u your_user -p your_database < SQL/seed.sql
```

### **4. Set Permissions**
```bash
chmod 755 uploads/
chmod 755 uploads/contracts/
```

### **5. Test**
- Access admin: `https://your-domain.com/client_name/admin/`
- Default credentials: See `INSTALLATION.md`

---

## ⚙️ Configuration

### **config/app.php**
```php
define('BASE_URL', 'https://contractdigital.ro/ro/client_name');
define('SITE_ID', 2);  // Unique ID for this client
define('SITE_NAME', 'Client Name - Contract Digital');
```

### **config/database.php**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'your_database');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

### **config/email.php**
```php
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
```

---

## 🗄️ Database Structure

### **Multi-Tenant Architecture**

All clients share the same database but are separated by `site_id`:

```sql
contract_templates (site_id = 2)  -- Client A templates
contract_templates (site_id = 3)  -- Client B templates
```

### **Key Tables**
- `contract_templates` - HTML templates for contracts
- `field_definitions` - Master list of form fields
- `template_field_mapping` - Maps fields to templates
- `contracts` - Generated contracts
- `users` - Admin users

---

## 📝 Creating Your First Template

### **1. Login to Admin**
```
https://contractdigital.ro/ro/client_name/admin/
```

### **2. Create Template**
- Navigate to "Templates"
- Click "Add New Template"
- Enter template name
- Paste HTML content with placeholders:
  ```html
  <h1>Contract</h1>
  <p>Nume: [NUME_COMPLET]</p>
  <p>Email: [EMAIL]</p>
  ```

### **3. Define Fields**
- Add fields in database:
  ```sql
  INSERT INTO field_definitions (field_name, field_type, field_label)
  VALUES ('nume_complet', 'text', 'Nume complet');
  ```

### **4. Map Fields to Template**
```sql
INSERT INTO template_field_mapping (template_id, field_definition_id, display_order, is_required)
VALUES (1, 1, 1, 1);
```

---

## 🎨 Customization

### **Styling**
- Edit `fill_and_sign.php` CSS section
- Or link external stylesheet

### **Add Custom Fields**
```sql
-- Add new field type
INSERT INTO field_definitions 
(field_name, field_type, field_label, placeholder)
VALUES 
('phone', 'tel', 'Telefon', '+40 XXX XXX XXX');

-- Map to template
INSERT INTO template_field_mapping 
(template_id, field_definition_id, display_order, is_required)
VALUES (1, <field_id>, 10, 1);
```

### **Email Templates**
Edit `admin/send_contract.php` to customize email content.

---

## 🔒 Security

### **Important:**
1. **Change default passwords** immediately
2. **Use HTTPS** in production
3. **Protect config folder:**
   ```apache
   # .htaccess in config/
   Deny from all
   ```
4. **Set proper file permissions:**
   ```bash
   chmod 644 config/*.php
   chmod 755 uploads/
   ```

---

## 🆘 Troubleshooting

### **"Cannot open config/database.php"**
- Ensure config files are renamed (remove `.example`)
- Check file permissions

### **"Database connection failed"**
- Verify credentials in `config/database.php`
- Check MySQL service is running

### **"PDF generation failed"**
- Ensure `uploads/contracts/` is writable
- Check TCPDF library in `includes/tcpdf/`

### **Diacritics appear as `?` in PDF**
- Check `ContractPDF.php` line 18 has `'UTF-8'` parameter

---

## 📞 Support

**Documentation:** See `INSTALLATION.md` for detailed setup  
**Database Schema:** See `SQL/schema.sql`  
**Main Project:** https://contractdigital.ro

---

## 📄 License

Proprietary - For use with Contract Digital clients only.

---

**Last Updated:** 2025-12-03
