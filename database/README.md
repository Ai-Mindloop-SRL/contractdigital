# Database Documentation

## 📊 Schema Overview

### **Main Tables**

#### **1. `sites`**
Stores multi-tenant site configurations.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `name` | VARCHAR(100) | Site name (e.g., "Mindloop") |
| `admin_email` | VARCHAR(255) | Admin email |
| `cc_email` | VARCHAR(255) | CC email for notifications |
| `primary_color` | VARCHAR(7) | Hex color code |

---

#### **2. `templates`**
Contract templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `site_id` | INT | FK to sites |
| `name` | VARCHAR(255) | Template name |
| `content` | LONGTEXT | HTML template content |
| `created_at` | TIMESTAMP | Creation date |

---

#### **3. `field_definitions`**
Reusable field definitions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `field_name` | VARCHAR(100) | Field identifier (e.g., "nume_firma") |
| `field_label` | VARCHAR(255) | Display label |
| `field_type` | VARCHAR(50) | input/textarea/select |
| `is_required` | BOOLEAN | Mandatory field? |
| `placeholder` | VARCHAR(255) | Placeholder text |

**Available Fields:**
- `numar_contract` - Contract number
- `data_contract` - Contract date
- `nume_firma` - Company name
- `cui` - Fiscal code (CUI)
- `reg_com` - Commercial registration number
- `adresa` - Address
- `email` - Email address
- `telefon` - Phone number
- `cont_bancar` - Bank account (IBAN)
- `reprezentant` - Legal representative name
- `functie` - Representative function/position
- `judet` - County (for RoseUp BNI) ⚡ NEW
- `taxa_membru` - Member fee (for RoseUp BNI) ⚡ NEW
- `taxa_inscriere` - Registration fee (for RoseUp BNI) ⚡ NEW

---

#### **4. `template_field_mapping`**
Maps fields to templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `template_id` | INT | FK to templates |
| `field_id` | INT | FK to field_definitions |
| `display_order` | INT | Field order in form |

---

#### **5. `contracts`**
Individual contract instances.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `template_id` | INT | FK to templates |
| `site_id` | INT | FK to sites |
| `recipient_name` | VARCHAR(255) | Signer name |
| `recipient_email` | VARCHAR(255) | Signer email |
| `recipient_phone` | VARCHAR(20) | Signer phone |
| `unique_token` | VARCHAR(64) | Unique URL token |
| `signing_token` | VARCHAR(64) | Signing URL token |
| `contract_content` | LONGTEXT | Filled contract HTML |
| `contract_number` | VARCHAR(50) | Sequential number (ML-2025-0001) |
| `pdf_path` | VARCHAR(255) | Path to generated PDF |
| `status` | ENUM | draft/sent/signed |
| `sent_at` | DATETIME | When email was sent |
| `signed_at` | DATETIME | When contract was signed |
| `created_at` | TIMESTAMP | Creation date |

---

#### **6. `contract_signatures`**
Signature metadata and legal data.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `contract_id` | INT | FK to contracts |
| `signature_data` | LONGTEXT | Base64 PNG signature |
| `signed_at` | DATETIME | Signing timestamp |
| `ip_address` | VARCHAR(45) | Signer IP |
| **Nivel 1 - SES+ Fields:** | | |
| `consent_given` | BOOLEAN | All consents accepted |
| `consent_timestamp` | DATETIME | When consent was given |
| `consent_ip` | VARCHAR(45) | IP at consent |
| `consent_read` | BOOLEAN | Read contract |
| `consent_sign` | BOOLEAN | Accept e-signature |
| `consent_gdpr` | BOOLEAN | Accept GDPR |
| `contract_hash_before` | VARCHAR(64) | SHA-256 before signing |
| `pdf_hash_after` | VARCHAR(64) | SHA-256 of final PDF |
| `user_agent` | TEXT | Full user-agent string |
| `user_agent_parsed` | JSON | Parsed (browser, OS, device) |
| `screen_resolution` | VARCHAR(20) | Screen resolution |
| `timezone` | VARCHAR(50) | Client timezone |
| `device_type` | VARCHAR(20) | Desktop/Mobile/Tablet |

---

## 🔄 Migrations

Migrations are located in `database/migrations/` and should be applied in order:

```bash
# Apply all migrations
mysql -u user -p contractdigital_ro < database/migrations/001_initial_schema.sql
mysql -u user -p contractdigital_ro < database/migrations/002_add_signatures.sql
mysql -u user -p contractdigital_ro < database/migrations/003_nivel1_ses_plus.sql
```

### **Migration History**

| Migration | Date | Description |
|-----------|------|-------------|
| `001_initial_schema.sql` | 2024-11-15 | Initial database structure |
| `002_add_signatures.sql` | 2024-12-04 | Added signature tracking |
| `003_nivel1_ses_plus.sql` | 2024-12-17 | Nivel 1 - SES+ fields |
| `005_add_roseup_fields.sql` | 2025-12-19 | RoseUp BNI fields (judet, taxa_membru, taxa_inscriere) |

---

## 🔐 Security Notes

### **Encrypted Fields**
The following fields should be encrypted in production:
- `contract_signatures.signer_cnp_encrypted` (Nivel 2+)
- `contract_signatures.signer_id_encrypted` (Nivel 2+)

### **Sensitive Data**
- IP addresses are stored for legal compliance
- User-Agent data is stored for fraud prevention
- All personal data follows GDPR regulations

---

## 📈 Performance Indexes

```sql
-- Optimize contract lookups
CREATE INDEX idx_contracts_unique_token ON contracts(unique_token);
CREATE INDEX idx_contracts_signing_token ON contracts(signing_token);
CREATE INDEX idx_contracts_status ON contracts(status);
CREATE INDEX idx_contracts_site_id ON contracts(site_id);

-- Optimize template queries
CREATE INDEX idx_templates_site_id ON templates(site_id);

-- Optimize signature queries
CREATE INDEX idx_signatures_contract_id ON contract_signatures(contract_id);
```

---

## 🗄️ Backup & Restore

### **Backup**
```bash
# Full database backup
mysqldump -u user -p contractdigital_ro > backup_$(date +%Y%m%d).sql

# Backup specific tables
mysqldump -u user -p contractdigital_ro contracts contract_signatures > contracts_backup.sql
```

### **Restore**
```bash
mysql -u user -p contractdigital_ro < backup_20241217.sql
```

---

## 📊 Sample Queries

### **Get all signed contracts for a site**
```sql
SELECT c.id, c.contract_number, c.recipient_name, c.signed_at
FROM contracts c
WHERE c.site_id = 1 AND c.status = 'signed'
ORDER BY c.signed_at DESC;
```

### **Get signature metadata**
```sql
SELECT 
    c.contract_number,
    cs.signed_at,
    cs.ip_address,
    cs.consent_given,
    cs.user_agent_parsed->>'$.browser' AS browser,
    cs.user_agent_parsed->>'$.os' AS os
FROM contracts c
JOIN contract_signatures cs ON c.id = cs.contract_id
WHERE c.id = 64;
```

### **Verify PDF integrity**
```sql
SELECT 
    contract_number,
    pdf_hash_after,
    signed_at
FROM contracts c
JOIN contract_signatures cs ON c.id = cs.contract_id
WHERE c.id = 64;
```

---

**Last Updated:** December 19, 2025
