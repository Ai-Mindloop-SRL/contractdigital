# 🌹 RoseUp Advisors - Client Documentation

> **BNI Collaboration Contracts for RoseUp Advisors**

---

## 📍 Overview

**RoseUp Advisors** is a specialized instance of the ContractDigital platform for managing BNI (Business Network International) collaboration contracts.

### Key Information
- **Client Name**: ROSEUP ADVISORS S.R.L.
- **Contract Type**: Contract de Colaborare BNI
- **URL**: https://contractdigital.ro/ro/roseupadvisors/
- **Site ID**: Check database `sites` table

---

## 🗂️ Directory Structure

```
roseupadvisors/
├── fill_and_sign.php          # Contract form & signing
├── sign_contract.php           # Signature processing
├── contract_detail.php        # Contract details view
├── download_pdf.php           # PDF download handler
├── edit_template.php          # Template editor
├── index.php                  # Admin dashboard
├── login.php                  # Authentication
├── logout.php                 # Session termination
├── restore_template.php       # Template restore utility
├── send_contract.php          # Email sending
├── status.php                 # Contract status
├── templates.php              # Template management
├── view_contract.php          # Contract viewer
└── README.md                  # This directory's reference
```

---

## 🚀 Quick Start

### Access Points

| Function | URL |
|----------|-----|
| **Admin Login** | https://contractdigital.ro/ro/roseupadvisors/login.php |
| **Dashboard** | https://contractdigital.ro/ro/roseupadvisors/ |
| **Templates** | https://contractdigital.ro/ro/roseupadvisors/templates.php |

### Default Credentials
- **Username**: `admin`
- **Password**: `admin123` (⚠️ Change after first login!)

---

## 🎨 Branding & Customization

### PDF Branding
- **Company Name**: ROSEUP ADVISORS S.R.L.
- **Contract Title**: Contract de Colaborare BNI
- **PDF Metadata**: Configured in `includes/ContractPDF.php`

### Custom Features
- Standard ContractDigital features
- BNI-specific contract templates
- Supports all generic field types from `field_definitions`

---

## 📄 Contract Workflow

### 1. Create Contract
```
Admin Dashboard → Templates → Select "Contract de Colaborare BNI"
→ Fill admin fields → Send for signature
```

### 2. Client Signing Process
```
Client receives email → Click link → fill_and_sign.php
→ Complete required fields → Draw signature → Submit
→ PDF generated automatically
```

### 3. Download & Archive
```
Contract Detail → Download PDF → Archive signed contract
```

---

## 🗄️ Database Configuration

### Site Configuration
```sql
-- Find your site_id
SELECT id, site_name, site_domain 
FROM sites 
WHERE site_name LIKE '%RoseUp%';
```

### Contract Templates
```sql
-- List active templates
SELECT id, template_name, is_active 
FROM contract_templates 
WHERE site_id = YOUR_SITE_ID AND is_active = 1;
```

---

## 🔧 Configuration Files

### Required Files
1. **`config/database.php`** - Database connection
2. **`config/email.php`** - SMTP settings (optional for this client)
3. **`config/app.php`** - Application settings (if using centralized config)

### Example Configuration
See [INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md) for detailed configuration examples.

---

## 🐛 Troubleshooting

### Common Issues

#### ❌ Template Not Displaying
**Solution**: Verify `site_id` in database matches configuration
```sql
SELECT * FROM contract_templates WHERE site_id = YOUR_SITE_ID;
```

#### ❌ Email Not Sending
**Solution**: Check SMTP configuration or use PHP's `mail()` function
```php
// In config/email.php, ensure correct SMTP settings
$mail->Host = 'smtp.your-provider.com';
```

#### ❌ Signature Not Saving
**Solution**: Check directory permissions
```bash
chmod 755 uploads/signatures/
ls -la uploads/signatures/
```

---

## 📊 Differences from Mindloop

| Feature | RoseUp Advisors | Mindloop |
|---------|-----------------|----------|
| **Contract Type** | BNI Collaboration | Service Contract |
| **Custom Fields** | Standard | Montessori-specific |
| **Template Sync** | Single template | Multiple annexes |
| **Branding** | ROSEUP ADVISORS | AI Mindloop SRL |

---

## 🔄 Updates & Maintenance

### Applying Updates
```bash
# Pull latest changes from Git
cd /path/to/contractdigital-repo
git pull origin main

# Deploy to production
./scripts/deploy.sh roseupadvisors
```

### Backup
```bash
# Backup database
mysqldump -u USER -p r68649site_contractdigital_db > roseup_backup_$(date +%Y%m%d).sql

# Backup files
tar -czf roseup_files_$(date +%Y%m%d).tar.gz roseupadvisors/
```

---

## 📞 Support

### Documentation
- **Installation**: [INSTALLATION_GUIDE.md](./INSTALLATION_GUIDE.md)
- **Deployment**: [../DEPLOYMENT.md](../DEPLOYMENT.md)
- **Database**: [../database/README.md](../database/README.md)
- **Main README**: [../README.md](../README.md)

### Server Access
- **FTP**: ftp.siteq.ro
- **cPanel**: https://siteq.ro:2083/
- **phpMyAdmin**: https://siteq.ro:2083/phpMyAdmin/

---

## ✅ Checklist for New Setup

- [ ] Database configured with correct `site_id`
- [ ] Admin password changed from default
- [ ] SMTP configuration tested (if sending emails)
- [ ] Upload directories created with proper permissions
- [ ] BNI template created and activated
- [ ] Test contract creation workflow
- [ ] Test signing process
- [ ] Verify PDF generation with Romanian characters
- [ ] Verify signature appears in final PDF

---

**Last Updated**: 2025-12-17  
**Version**: 1.0  
**Contact**: See main repository documentation
