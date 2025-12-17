# Client Template - For New Clients

## 📍 Quick Links

- **Main Documentation:** [README.md](../README.md)
- **Installation:** [DEPLOYMENT.md](../DEPLOYMENT.md)
- **Database Setup:** [database/README.md](../database/README.md)
- **Configuration:** [config/README.md](../config/README.md)

---

## 🎯 What is Client Template?

The `_client_template/` folder contains a ready-to-deploy instance for **new clients**.

When you need to add a new client:
1. Copy `_client_template/` to `/new_client_name/`
2. Configure `site_id` in database
3. Update branding (colors, logos, emails)
4. Deploy

---

## 🚀 Setup New Client (Step by Step)

### **1. Copy Template**
```bash
cp -r _client_template/ /new_client_name/
```

### **2. Add to Database**
```sql
INSERT INTO sites (name, admin_email, cc_email, primary_color)
VALUES ('New Client Name', 'admin@newclient.com', 'cc@newclient.com', '#3498db');

-- Get the new site_id
SELECT id FROM sites WHERE name = 'New Client Name';
```

### **3. Update Contract Numbering**
Edit `new_client_name/fill_and_sign.php`:
```php
// Line ~79: Change contract prefix
$numar_contract = "NC-" . date('Y') . "-" . str_pad($next_num, 4, '0', STR_PAD_LEFT);
// NC = New Client initials
```

### **4. Update SQL Queries**
In `fill_and_sign.php`, `send_contract.php`, etc., find:
```php
WHERE c.site_id = ?
```
And ensure `site_id` matches your new client's ID.

### **5. Deploy**
```bash
# Upload to server
./scripts/deploy.sh new_client_name

# Or via FTP manually
```

---

## 📝 Files to Customize

| File | What to Change |
|------|----------------|
| `fill_and_sign.php` | Contract prefix (line 79) |
| `ContractPDF.php` | Creator, Author, Title metadata |
| `login.php` | Branding, logo |
| All `*.php` | Verify `site_id` queries |

---

## 🎨 Branding

Update in database `sites` table:
- `name` - Client name
- `primary_color` - HEX color code
- `admin_email` - Admin contact
- `cc_email` - CC for notifications

---

## 📊 Database

Each client needs:
- **Site entry** in `sites` table (unique `site_id`)
- **Templates** in `templates` table (with their `site_id`)
- **Contracts** in `contracts` table (with their `site_id`)

Multi-tenant architecture - all clients share same database, separated by `site_id`.

---

## 🆘 Support

For issues:
- Check main [README.md](../README.md)
- Review [DEPLOYMENT.md](../DEPLOYMENT.md)
- Contact: support@mindloop.ro

---

**Last Updated:** December 17, 2024
