# 🚀 Deployment Guide

## Production Environment

**Server:** contractdigital.ro  
**FTP Access:** ftp.siteq.ro  
**PHP Version:** 7.4+  
**MySQL Version:** 5.7+

---

## 📋 Pre-Deployment Checklist

- [ ] All tests passed locally
- [ ] Database migrations prepared
- [ ] Config files reviewed (database.php, email.php)
- [ ] .gitignore configured correctly
- [ ] Sensitive data removed from code
- [ ] Backup current production database
- [ ] Backup current production files

---

## 🔧 Manual Deployment

### **Step 1: Backup Production**
```bash
# Backup database
ssh user@contractdigital.ro
mysqldump -u user -p contractdigital_ro > backup_$(date +%Y%m%d_%H%M%S).sql

# Backup files
tar -czf backup_files_$(date +%Y%m%d_%H%M%S).tar.gz /path/to/mindloop /path/to/roseupadvisors
```

### **Step 2: Upload Files via FTP**
```bash
# Using FileZilla or command line FTP
ftp ftp.siteq.ro
# Username: claude_ai@siteq.ro
# Password: igkcwismekdgqndp

# Upload directories:
put -r mindloop/
put -r roseupadvisors/
put -r includes/
```

### **Step 3: Run Database Migrations**
```bash
# Connect to database
mysql -u user -p contractdigital_ro

# Run migration
source database/migrations/003_nivel1_ses_plus.sql
```

### **Step 4: Verify Deployment**
- [ ] https://contractdigital.ro/ro/mindloop/templates.php loads
- [ ] https://contractdigital.ro/ro/roseupadvisors/templates.php loads
- [ ] Test contract creation
- [ ] Test contract signing
- [ ] Test PDF download

---

## 🤖 Automated Deployment

### **Using deploy.sh Script**
```bash
# Make script executable
chmod +x scripts/deploy.sh

# Deploy specific project
./scripts/deploy.sh mindloop
./scripts/deploy.sh roseupadvisors

# Deploy everything
./scripts/deploy.sh all
```

---

## 🗄️ Database Migrations

### **Apply New Migrations**
```bash
# SSH to server
ssh user@contractdigital.ro

# Navigate to project
cd /path/to/contractdigital

# Apply migration
mysql -u user -p contractdigital_ro < database/migrations/003_nivel1_ses_plus.sql
```

### **Rollback Migration** (if needed)
```bash
# Restore from backup
mysql -u user -p contractdigital_ro < backup_20241217_143000.sql
```

---

## 🔍 Post-Deployment Verification

### **1. Health Check**
```bash
curl -I https://contractdigital.ro/ro/mindloop/templates.php
# Should return: HTTP/1.1 200 OK
```

### **2. Database Connection**
```bash
# Check error logs
tail -f /path/to/error_log
```

### **3. Test Critical Flows**
- [ ] Create new template
- [ ] Send contract
- [ ] Sign contract
- [ ] Download PDF
- [ ] View contract details

---

## 🚨 Rollback Procedure

If deployment fails:

### **1. Restore Files**
```bash
# Via FTP, upload backup files
# Or via SSH:
tar -xzf backup_files_20241217_143000.tar.gz -C /
```

### **2. Restore Database**
```bash
mysql -u user -p contractdigital_ro < backup_20241217_143000.sql
```

### **3. Clear Cache** (if applicable)
```bash
rm -rf /path/to/cache/*
```

---

## 📊 Monitoring

### **Check Logs**
```bash
# PHP error log
tail -f /path/to/error_log

# Apache/Nginx access log
tail -f /var/log/apache2/access.log
```

### **Check Disk Space**
```bash
df -h
```

### **Check Database Size**
```sql
SELECT 
    table_schema AS 'Database',
    SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)'
FROM information_schema.tables
WHERE table_schema = 'contractdigital_ro'
GROUP BY table_schema;
```

---

## 🔐 Security Checklist

- [ ] Config files NOT accessible via web (move outside web root)
- [ ] uploads/ directory has proper permissions (755 for dirs, 644 for files)
- [ ] Database user has minimum required privileges
- [ ] HTTPS enabled (SSL certificate valid)
- [ ] .htaccess configured for security headers
- [ ] Error display OFF in production (`display_errors = Off` in php.ini)

---

## 📞 Support Contacts

**Technical Issues:**
- Claude AI Assistant
- GitHub Issues: https://github.com/Ai-Mindloop-SRL/contractdigital-platform/issues

**Server/Hosting Issues:**
- Hosting Provider: siteq.ro
- Control Panel: https://siteq.ro:2083/

---

**Last Updated:** December 17, 2024
