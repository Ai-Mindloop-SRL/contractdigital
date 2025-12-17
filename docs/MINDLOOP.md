# Mindloop - Specific Documentation

## 📍 Quick Links

- **Main Documentation:** [README.md](../README.md)
- **Installation:** [DEPLOYMENT.md](../DEPLOYMENT.md)
- **Database Setup:** [database/README.md](../database/README.md)
- **Configuration:** [config/README.md](../config/README.md)

---

## 🎯 Mindloop-Specific Information

### **Site Configuration**
- **Site ID:** 1
- **Admin Email:** test@mindloop.ro
- **CC Email:** test@mindloop.ro (configured in database `sites.cc_email`)
- **Primary Color:** #3498db

### **Contract Numbering Format**
```
ML-YYYY-NNNN
Example: ML-2024-0001, ML-2024-0002, etc.
```

### **File Locations**
- **Code:** `/mindloop/`
- **Templates:** Database (`templates` table, `site_id = 1`)
- **PDF Output:** `/uploads/contracts/`
- **Signatures:** `/uploads/signatures/`

### **Access URLs**
- Templates: `https://contractdigital.ro/ro/mindloop/templates.php`
- Contracts: `https://contractdigital.ro/ro/mindloop/send_contract.php`
- Login: `https://contractdigital.ro/ro/mindloop/login.php`

---

## 🔧 Configuration

See main [config/README.md](../config/README.md) for:
- Database setup
- Email SMTP configuration
- Security keys

---

## 📊 Database

Mindloop uses:
- **Site ID:** 1 (in `sites` table)
- **Templates:** Stored in `templates` where `site_id = 1`
- **Contracts:** Stored in `contracts` where `site_id = 1`

---

## 🚀 Deployment

See [DEPLOYMENT.md](../DEPLOYMENT.md) for complete deployment instructions.

**Quick deploy:**
```bash
# Deploy only Mindloop
./scripts/deploy.sh mindloop
```

---

**Last Updated:** December 17, 2024
