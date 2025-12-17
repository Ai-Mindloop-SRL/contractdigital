# Configuration Files

## 🔐 Security Notice

**NEVER commit actual configuration files to GitHub!**

This folder contains `.example.php` templates. Copy them to create your actual config files:

```bash
cp database.example.php database.php
cp email.example.php email.php
cp security.example.php security.php
```

Then edit each file with your actual credentials.

---

## 📝 Configuration Files

### **database.php** (from database.example.php)
MySQL database connection settings.

Required values:
- `DB_HOST` - Database host (usually 'localhost')
- `DB_USER` - MySQL username
- `DB_PASS` - MySQL password
- `DB_NAME` - Database name (contractdigital_ro)

### **email.php** (from email.example.php)
SMTP email server settings.

Required values:
- `EMAIL_HOST` - SMTP server
- `EMAIL_PORT` - SMTP port (465 for SSL, 587 for TLS)
- `EMAIL_USERNAME` - SMTP username
- `EMAIL_PASSWORD` - SMTP password

### **security.php** (from security.example.php)
Security and encryption settings.

Generate keys:
```bash
# Encryption key
openssl rand -base64 32

# Encryption IV
openssl rand -hex 16
```

---

## ✅ .gitignore

The `.gitignore` file ensures these config files are **never** committed:

```
config/database.php
config/email.php
config/security.php
```

Only `.example.php` files are tracked in Git.

---

## 🆘 Troubleshooting

### "Connection refused" error
- Check `DB_HOST` and `DB_PORT`
- Ensure MySQL is running
- Check firewall settings

### "Access denied" error
- Verify `DB_USER` and `DB_PASS`
- Check MySQL user permissions

### Email not sending
- Verify SMTP credentials
- Check firewall for outbound port 465/587
- Try both SSL and TLS modes
