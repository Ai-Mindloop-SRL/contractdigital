# Database Documentation

## 📊 Schema Overview

### **Main Tables**

#### **1. `sites`**
Stores multi-tenant site configurations.

| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| `id` | INT(11) | NO | PRI | - | Primary key |
| `site_name` | VARCHAR(100) | NO | - | - | Site name (e.g., "Mindloop") |
| `site_slug` | VARCHAR(50) | NO | UNI | - | URL slug (unique identifier) |
| `admin_email` | VARCHAR(100) | NO | - | - | Admin email |
| `cc_email` | VARCHAR(100) | YES | - | NULL | CC email for notifications |
| `logo_path` | VARCHAR(255) | YES | - | NULL | Path to site logo |
| `primary_color` | VARCHAR(7) | NO | - | '#3498db' | Hex color code |
| `created_at` | DATETIME | NO | - | current_timestamp() | Creation date |
| `is_active` | TINYINT(1) | NO | MUL | 1 | Site active status |

---

#### **2. `contract_templates`**
Contract templates.

| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| `id` | INT(11) | NO | PRI | - | Primary key |
| `site_id` | INT(11) | NO | MUL | - | FK to sites |
| `template_name` | VARCHAR(100) | NO | - | - | Template name |
| `template_content` | LONGTEXT | NO | - | - | HTML template content |
| `is_active` | TINYINT(1) | YES | - | 1 | Template active status |
| `created_at` | DATETIME | NO | MUL | current_timestamp() | Creation date |
| `updated_at` | DATETIME | NO | - | current_timestamp() | Last update date |
| `contract_counter` | INT(11) | YES | - | 0 | Sequential contract counter |

---

#### **3. `field_definitions`**
Reusable field definitions (global field catalog).

| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| `id` | INT(11) | NO | PRI | - | Primary key |
| `field_name` | VARCHAR(100) | NO | UNI | - | Field identifier (e.g., "nume_firma") |
| `field_type` | VARCHAR(50) | NO | - | 'text' | Field type: text/input/textarea/date/email/tel/select |
| `field_label` | VARCHAR(255) | NO | - | - | Display label |
| `placeholder` | TEXT | YES | - | NULL | Placeholder text |
| `field_group` | VARCHAR(100) | YES | MUL | NULL | Field grouping (e.g., "company_info") |
| `validation_rules` | TEXT | YES | - | NULL | JSON validation rules |
| `created_at` | TIMESTAMP | NO | - | current_timestamp() | Creation timestamp |
| `updated_at` | TIMESTAMP | NO | - | current_timestamp() | Last update timestamp |

**Current Fields in Database (11 existing):**

| ID | field_name | field_label | field_type |
|----|------------|-------------|------------|
| 1 | `numar_contract` | Număr Contract | input |
| 2 | `data_contract` | Data Contract | date |
| 3 | `nume_firma` | Nume Firmă | input |
| 4 | `cui` | CUI | input |
| 5 | `reg_com` | Reg. Com. | input |
| 6 | `adresa` | Adresă | textarea |
| 7 | `email` | Email | email |
| 8 | `telefon` | Telefon | tel |
| 9 | `cont_bancar` | Cont Bancar (IBAN) | input |
| 10 | `reprezentant` | Reprezentant Legal | input |
| 11 | `functie` | Funcție Reprezentant | input |

**Fields to be Added (via migration 005):**
- ⚡ `judet` - Județ (County for RoseUp BNI)
- ⚡ `taxa_membru` - Taxă Membru (Member fee EUR for RoseUp BNI)
- ⚡ `taxa_inscriere` - Taxă Înscriere (Registration fee EUR for RoseUp BNI)

---

#### **4. `template_field_mapping`**
Maps fields to templates with per-template customization.

| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| `id` | INT(11) | NO | PRI | - | Primary key |
| `template_id` | INT(11) | NO | MUL | - | FK to contract_templates |
| `field_definition_id` | INT(11) | NO | MUL | - | FK to field_definitions |
| `display_order` | INT(11) | YES | MUL | 0 | Field order in form |
| `is_required` | TINYINT(1) | YES | - | 1 | Mandatory field for this template? |
| `custom_placeholder` | TEXT | YES | - | NULL | Override placeholder for this template |
| `custom_label` | VARCHAR(255) | YES | - | NULL | Override label for this template |
| `created_at` | TIMESTAMP | NO | - | current_timestamp() | Mapping creation timestamp |

**Note:** `is_required`, `custom_placeholder`, and `custom_label` allow per-template field customization.

---

#### **5. `contracts`**
Individual contract instances.

| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| `id` | INT(11) | NO | PRI | - | Primary key |
| `site_id` | INT(11) | NO | MUL | - | FK to sites |
| `template_id` | INT(11) | NO | MUL | - | FK to contract_templates |
| `contract_content` | LONGTEXT | NO | - | - | Filled contract HTML |
| `signing_token` | VARCHAR(64) | YES | UNI | NULL | Signing URL token |
| `unique_token` | VARCHAR(64) | NO | UNI | - | Unique URL token |
| `recipient_email` | VARCHAR(100) | NO | MUL | - | Signer email |
| `recipient_phone` | VARCHAR(20) | YES | - | NULL | Signer phone |
| `recipient_name` | VARCHAR(100) | YES | - | NULL | Signer name |
| `status` | ENUM | NO | MUL | 'draft' | draft/sent/signed |
| `signature_path` | VARCHAR(255) | YES | - | NULL | Path to signature image PNG |
| `form_data` | TEXT | YES | - | NULL | JSON form field data |
| `pdf_path` | VARCHAR(255) | YES | - | NULL | Path to generated PDF |
| `sent_at` | DATETIME | YES | - | NULL | When email was sent |
| `signed_at` | DATETIME | YES | - | NULL | When contract was signed |
| `signature_data` | LONGTEXT | YES | - | NULL | Base64 signature data |
| `completed_at` | DATETIME | YES | - | NULL | When contract was completed |
| `ip_address` | VARCHAR(45) | YES | - | NULL | IP address at signing |
| `created_at` | DATETIME | NO | MUL | current_timestamp() | Creation date |
| `contract_number` | VARCHAR(50) | YES | MUL | NULL | Sequential number (ML-2025-0001) |

---

#### **6. `contract_signatures`**
Signature metadata and legal compliance data (Nivel 1 - SES+).

| Column | Type | Nullable | Key | Default | Description |
|--------|------|----------|-----|---------|-------------|
| `id` | INT(11) | NO | PRI | - | Primary key |
| `contract_id` | INT(11) | NO | MUL | - | FK to contracts |
| `signer_name` | VARCHAR(100) | NO | - | - | Signer name |
| `signature_data` | LONGTEXT | NO | - | - | Base64 PNG signature |
| `ip_address` | VARCHAR(45) | YES | - | NULL | Signer IP |
| `user_agent` | VARCHAR(255) | YES | - | NULL | User-Agent string |
| `signed_at` | DATETIME | NO | - | - | Signing timestamp |
| **Nivel 1 - SES+ Fields:** | | | | | |
| `consent_given` | TINYINT(1) | YES | - | 0 | All consents accepted |
| `consent_timestamp` | DATETIME | YES | MUL | NULL | When consent was given |
| `consent_ip` | VARCHAR(45) | YES | - | NULL | IP at consent |
| `consent_read` | TINYINT(1) | YES | - | 0 | Read contract consent |
| `consent_sign` | TINYINT(1) | YES | - | 0 | Accept e-signature consent |
| `consent_gdpr` | TINYINT(1) | YES | - | 0 | Accept GDPR consent |
| `contract_hash_before` | VARCHAR(64) | YES | MUL | NULL | SHA-256 before signing |
| `pdf_hash_after` | VARCHAR(64) | YES | - | NULL | SHA-256 of final PDF |
| `user_agent_parsed` | LONGTEXT | YES | - | NULL | JSON parsed user-agent |
| `screen_resolution` | VARCHAR(20) | YES | - | NULL | Screen resolution |
| `timezone` | VARCHAR(50) | YES | - | NULL | Client timezone |
| `device_type` | VARCHAR(20) | YES | - | NULL | Desktop/Mobile/Tablet |

---

## 🔄 Migrations

Migrations are located in `database/migrations/` and should be applied in order:

```bash
# Apply all migrations
mysql -u user -p contractdigital_ro < database/migrations/001_initial_schema.sql
mysql -u user -p contractdigital_ro < database/migrations/002_add_signatures.sql
mysql -u user -p contractdigital_ro < database/migrations/003_nivel1_ses_plus.sql
mysql -u user -p contractdigital_ro < database/migrations/005_add_roseup_fields.sql
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
CREATE INDEX idx_contracts_created_at ON contracts(created_at);
CREATE INDEX idx_contracts_recipient_email ON contracts(recipient_email);
CREATE INDEX idx_contracts_contract_number ON contracts(contract_number);

-- Optimize template queries
CREATE INDEX idx_templates_site_id ON contract_templates(site_id);
CREATE INDEX idx_templates_created_at ON contract_templates(created_at);

-- Optimize signature queries
CREATE INDEX idx_signatures_contract_id ON contract_signatures(contract_id);
CREATE INDEX idx_signatures_consent_timestamp ON contract_signatures(consent_timestamp);
CREATE INDEX idx_signatures_contract_hash ON contract_signatures(contract_hash_before);

-- Optimize field queries
CREATE INDEX idx_field_defs_group ON field_definitions(field_group);

-- Optimize mapping queries
CREATE INDEX idx_mapping_template ON template_field_mapping(template_id);
CREATE INDEX idx_mapping_field ON template_field_mapping(field_definition_id);
CREATE INDEX idx_mapping_order ON template_field_mapping(display_order);

-- Optimize site queries
CREATE INDEX idx_sites_active ON sites(is_active);
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

### **Get signature metadata with consent info**
```sql
SELECT 
    c.contract_number,
    cs.signed_at,
    cs.ip_address,
    cs.consent_given,
    cs.consent_read,
    cs.consent_sign,
    cs.consent_gdpr,
    JSON_UNQUOTE(JSON_EXTRACT(cs.user_agent_parsed, '$.browser')) AS browser,
    JSON_UNQUOTE(JSON_EXTRACT(cs.user_agent_parsed, '$.os')) AS os,
    cs.device_type
FROM contracts c
JOIN contract_signatures cs ON c.id = cs.contract_id
WHERE c.id = 64;
```

### **Verify PDF integrity**
```sql
SELECT 
    c.contract_number,
    cs.contract_hash_before,
    cs.pdf_hash_after,
    cs.signed_at
FROM contracts c
JOIN contract_signatures cs ON c.id = cs.contract_id
WHERE c.id = 64;
```

### **Get template with all mapped fields**
```sql
SELECT 
    ct.template_name,
    fd.field_name,
    fd.field_label,
    fd.field_type,
    tfm.is_required,
    COALESCE(tfm.custom_label, fd.field_label) AS effective_label,
    COALESCE(tfm.custom_placeholder, fd.placeholder) AS effective_placeholder,
    tfm.display_order
FROM contract_templates ct
JOIN template_field_mapping tfm ON ct.id = tfm.template_id
JOIN field_definitions fd ON tfm.field_definition_id = fd.id
WHERE ct.id = 1
ORDER BY tfm.display_order;
```

---

**Last Updated:** December 19, 2025  
**Based on:** Production database dump from phpMyAdmin
