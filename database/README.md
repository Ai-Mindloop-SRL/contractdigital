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
| `bcc_email` | VARCHAR(255) | BCC email for notifications |
| `smtp_host` | VARCHAR(255) | SMTP server host |
| `smtp_port` | INT | SMTP server port |
| `smtp_username` | VARCHAR(255) | SMTP authentication username |
| `smtp_password` | VARCHAR(255) | SMTP authentication password |
| `smtp_encryption` | VARCHAR(10) | SMTP encryption (tls/ssl) |
| `primary_color` | VARCHAR(7) | Hex color code |
| `logo_path` | VARCHAR(255) | Path to site logo |
| `created_at` | TIMESTAMP | Creation date |

---

#### **2. `contract_templates`**
Contract templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `site_id` | INT | FK to sites |
| `name` | VARCHAR(255) | Template name |
| `content` | LONGTEXT | HTML template content |
| `created_at` | TIMESTAMP | Creation date |
| `updated_at` | TIMESTAMP | Last update date |

---

#### **3. `field_definitions`**
Reusable field definitions.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `field_name` | VARCHAR(100) | Field identifier (e.g., "nume_firma") |
| `field_label` | VARCHAR(255) | Display label |
| `field_type` | VARCHAR(50) | input/textarea/date/email/tel/select |
| `is_required` | TINYINT(1) | Mandatory field? (0=no, 1=yes) |
| `placeholder` | VARCHAR(255) | Placeholder text |
| `default_value` | VARCHAR(255) | Default value for field |
| `validation_rules` | TEXT | JSON validation rules |

**Current Fields in Database (11 existing):**

| ID | field_name | field_label | field_type | is_required | placeholder |
|----|------------|-------------|------------|-------------|-------------|
| 1 | `numar_contract` | Număr Contract | input | 1 | Ex: ML-2025-0001 |
| 2 | `data_contract` | Data Contract | date | 1 | - |
| 3 | `nume_firma` | Nume Firmă | input | 1 | Ex: SC EXEMPLU SRL |
| 4 | `cui` | CUI | input | 1 | Ex: RO12345678 |
| 5 | `reg_com` | Reg. Com. | input | 1 | Ex: J40/1234/2025 |
| 6 | `adresa` | Adresă | textarea | 1 | Adresa completă a firmei |
| 7 | `email` | Email | email | 1 | email@exemplu.ro |
| 8 | `telefon` | Telefon | tel | 1 | Ex: 0721234567 |
| 9 | `cont_bancar` | Cont Bancar (IBAN) | input | 1 | Ex: RO49AAAA1B31007593840000 |
| 10 | `reprezentant` | Reprezentant Legal | input | 1 | Nume și prenume |
| 11 | `functie` | Funcție Reprezentant | input | 1 | Ex: Administrator, Director |

**Fields to be Added (via migration 005):**
- ⚡ `judet` - Județ (County for RoseUp BNI)
- ⚡ `taxa_membru` - Taxă Membru (Member fee EUR for RoseUp BNI)
- ⚡ `taxa_inscriere` - Taxă Înscriere (Registration fee EUR for RoseUp BNI)

---

#### **4. `template_field_mapping`**
Maps fields to templates.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `template_id` | INT | FK to contract_templates |
| `field_id` | INT | FK to field_definitions |
| `display_order` | INT | Field order in form |
| `created_at` | TIMESTAMP | Mapping creation date |

---

#### **5. `contracts`**
Individual contract instances.

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `site_id` | INT | FK to sites |
| `template_id` | INT | FK to contract_templates |
| `recipient_name` | VARCHAR(255) | Signer name |
| `recipient_email` | VARCHAR(255) | Signer email |
| `recipient_phone` | VARCHAR(20) | Signer phone |
| `contract_content` | LONGTEXT | Filled contract HTML |
| `contract_number` | VARCHAR(50) | Sequential number (ML-2025-0001) |
| `unique_token` | VARCHAR(64) | Unique URL token |
| `signing_token` | VARCHAR(64) | Signing URL token |
| `pdf_path` | VARCHAR(255) | Path to generated PDF |
| `signature_path` | VARCHAR(255) | Path to signature image PNG |
| `status` | ENUM | draft/sent/signed |
| `sent_at` | DATETIME | When email was sent |
| `signed_at` | DATETIME | When contract was signed |
| `created_at` | TIMESTAMP | Creation date |
| `updated_at` | TIMESTAMP | Last update date |

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
