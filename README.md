# 📝 ContractDigital Platform

> **Digital Contract Management System with Electronic Signature**

A comprehensive web-based platform for creating, managing, and signing digital contracts with legally-valid electronic signatures compliant with EU eIDAS regulations.

---

## 🚀 Features

### ✅ **Current Features (Production)**
- 📄 **Contract Template Management** - Create and manage reusable contract templates
- ✍️ **Electronic Signature** - SES+ (Simple Electronic Signature Plus)
- 📧 **Email Delivery** - Automated contract sending via email
- 🔐 **Secure PDF Generation** - Cryptographically signed PDFs
- 📊 **Contract Tracking** - Monitor contract status and history
- 🎨 **Multi-Tenant Support** - Separate instances for different clients (Mindloop, RoseUp)
- 🔄 **Auto Field Mapping** - Automatic placeholder detection and mapping

### 🚧 **In Development**
- 🔒 **Nivel 1 - SES+** (Simple Electronic Signature Plus)
  - Explicit consent tracking
  - SHA-256 document integrity verification
  - User-Agent and device fingerprinting
- 🔐 **Nivel 2 - SEA Light** (Advanced Electronic Signature Light) - Planned
- 🏛️ **Nivel 3 - SEA Complete** (Advanced Electronic Signature Complete) - Planned

---

## 🏗️ Architecture

### **Tech Stack**
- **Backend:** PHP 7.4+ (vanilla, no framework)
- **Database:** MySQL 5.7+
- **PDF Generation:** TCPDF library
- **Email:** SMTP (via custom email functions)
- **Frontend:** HTML5, CSS3, JavaScript (vanilla)
- **Storage:** File-based (local storage + FTP deployment)

### **Project Structure**
```
contractdigital-platform/
├── mindloop/              # Mindloop client instance
├── roseupadvisors/        # RoseUp Advisors client instance
├── _client_template/      # Template for new clients
├── includes/              # Shared PHP classes and functions
├── config/                # Configuration files (gitignored)
├── uploads/               # Generated PDFs and signatures (gitignored)
└── database/              # SQL schemas and migrations
```

---

## 🔧 Installation

### **Prerequisites**
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (optional, for dependencies)

### **Setup Steps**

1. **Clone Repository**
   ```bash
   git clone https://github.com/Ai-Mindloop-SRL/contractdigital-platform.git
   cd contractdigital-platform
   ```

2. **Configure Database**
   ```bash
   cp config/database.example.php config/database.php
   # Edit config/database.php with your DB credentials
   ```

3. **Import Database Schema**
   ```bash
   mysql -u your_user -p contractdigital_ro < database/schema.sql
   ```

4. **Configure Email**
   ```bash
   cp config/email.example.php config/email.php
   # Edit config/email.php with your SMTP credentials
   ```

5. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/contracts/
   chmod 755 uploads/signatures/
   ```

6. **Access Application**
   - Mindloop: `https://yourdomain.com/mindloop/templates.php`
   - RoseUp: `https://yourdomain.com/roseupadvisors/templates.php`

---

## 📚 Documentation

- [Architecture Overview](docs/ARCHITECTURE.md)
- [Deployment Guide](docs/DEPLOYMENT.md)
- [API Documentation](docs/API.md)
- [Legal Compliance (eIDAS)](docs/LEGAL.md)
- [Database Schema](database/README.md)

---

## 🔐 Security

### **Data Protection**
- ✅ CNP and ID numbers are encrypted (AES-256-CBC)
- ✅ Passwords are hashed (bcrypt)
- ✅ SQL injection protection (prepared statements)
- ✅ GDPR compliant data handling
- ✅ SHA-256 document integrity verification

### **Electronic Signature Levels**

| Level | Description | Legal Value | Status |
|-------|-------------|-------------|--------|
| **SES** | Simple Electronic Signature | Limited | ✅ Production |
| **SES+** | SES + Consent + Hash + Metadata | Medium | 🚧 Development |
| **SEA Light** | + ID Verification + Phone OTP | High | 📋 Planned |
| **SEA Complete** | + Email Verification + Geolocation + Biometrics | Very High | 📋 Planned |
| **SEQ** | Qualified Electronic Signature (requires TSP) | Maximum | ❌ Not Planned |

---

## 🚀 Deployment

### **Manual Deployment (FTP)**
```bash
# Upload files to server
ftp ftp.siteq.ro
# user: claude_ai@siteq.ro
# Upload mindloop/, roseupadvisors/, includes/, config/
```

### **Automated Deployment**
```bash
# Using deploy script
./scripts/deploy.sh mindloop
./scripts/deploy.sh roseupadvisors
```

---

## 🛠️ Development

### **Local Development**
```bash
# Start local PHP server
php -S localhost:8000

# Access at:
# http://localhost:8000/mindloop/templates.php
```

### **Database Migrations**
```bash
# Run migrations
mysql -u user -p contractdigital_ro < database/migrations/003_nivel1_ses_plus.sql
```

---

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

### **Latest Changes (2024-12-17)**
- ✅ Fixed case-insensitive placeholder replacement
- ✅ Fixed hardcoded CC email (office@splm.ro → dynamic from DB)
- ✅ Fixed download_pdf.php redirect to existing PDF
- ✅ Fixed template preview opening in new tab
- ✅ Fixed lowercase placeholder auto-mapping in edit_template.php
- 🚧 Implementing Nivel 1 - SES+ (in progress)

---

## 👥 Team

**Ai Mindloop SRL**
- GitHub: https://github.com/Ai-Mindloop-SRL
- Website: https://mindloop.ro

---

## 📄 License

Proprietary - All rights reserved by Ai Mindloop SRL

---

## 🆘 Support

For issues, questions, or support:
- **Email:** support@mindloop.ro
- **GitHub Issues:** https://github.com/Ai-Mindloop-SRL/contractdigital-platform/issues

---

## 🔗 Related Projects

- [Mindloop AI Platform](https://mindloop.ro)
- [RoseUp Advisors](https://roseupadvisors.ro)

---

**Last Updated:** December 17, 2024
