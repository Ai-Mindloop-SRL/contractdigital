# Client Template Installation

> **This file has been consolidated into central documentation.**

---

## 📖 For Complete Installation Instructions

Please see the **central installation guide**:

👉 **[../docs/INSTALLATION_GUIDE.md](../docs/INSTALLATION_GUIDE.md)**

---

## 🎯 Client Template-Specific Documentation

For detailed instructions on using this template for new clients:

👉 **[../docs/CLIENT_TEMPLATE.md](../docs/CLIENT_TEMPLATE.md)**

---

## 🚀 Quick Links

- **Main README**: [../README.md](../README.md)
- **Deployment Guide**: [../DEPLOYMENT.md](../DEPLOYMENT.md)
- **Database Setup**: [../database/README.md](../database/README.md)
- **Configuration**: [../config/README.md](../config/README.md)

---

## 📝 Quick Setup for New Client

```bash
# 1. Copy template folder
cp -r _client_template/ /new_client_name/

# 2. Follow complete guide
cat ../docs/CLIENT_TEMPLATE.md

# 3. Configure database
cp config/database.example.php config/database.php
nano config/database.php

# 4. Deploy
./scripts/deploy.sh new_client_name
```

---

**Note**: All installation procedures are now centralized to avoid duplication and maintain consistency across all client implementations.
