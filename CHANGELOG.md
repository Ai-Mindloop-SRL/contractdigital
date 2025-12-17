# Changelog

All notable changes to ContractDigital Platform will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [Unreleased]

### 🚧 In Development
- **Nivel 1 - SES+** (Simple Electronic Signature Plus)
  - Explicit consent tracking (3 checkboxes)
  - SHA-256 document integrity verification
  - Enhanced user-agent and device fingerprinting
  - Screen resolution and timezone tracking

---

## [1.2.0] - 2024-12-17

### ✅ Fixed
- **Case-Insensitive Placeholders:** Fixed placeholder replacement to support both uppercase `[DATA_CONTRACT]` and lowercase `[data_contract]` variants
- **Email CC Bug:** Removed hardcoded `office@splm.ro` CC address, now uses dynamic `cc_email` from database
- **Global Email CC:** Removed global `EMAIL_CC_ADDRESS` constant that was CC'ing all emails to office@splm.ro
- **Download PDF Error:** Fixed `ArgumentCountError` in `download_pdf.php` by redirecting to existing PDF instead of regenerating
- **Contract Column Name:** Fixed `contract_html` → `contract_content` column mismatch error
- **Undefined admin_email Warning:** Added safe `!empty()` check for `admin_email` field
- **Template Preview:** Removed `target="_blank"` from Preview button so it opens in same tab

### ✨ Added
- **PDF Path Redirect:** `download_pdf.php` now redirects to existing PDF instead of regenerating (20-50x faster)
- **Lowercase Placeholder Support:** Auto-mapping regex in `edit_template.php` now accepts both uppercase and lowercase placeholders

### 🔧 Changed
- **Sequential Contract Numbers:** Contract numbering is now truly sequential (ML-2025-XXXX format)
- **PDF Generation:** PDFs are generated once at signing and stored, not regenerated on every download

### 📁 Files Modified
- `mindloop/fill_and_sign.php`
- `mindloop/download_pdf.php`
- `mindloop/contract_detail.php`
- `mindloop/edit_template.php`
- `mindloop/templates.php`
- `roseupadvisors/fill_and_sign.php`
- `roseupadvisors/download_pdf.php`
- `roseupadvisors/contract_detail.php`
- `roseupadvisors/edit_template.php`
- `roseupadvisors/templates.php`
- `_client_template/fill_and_sign.php`
- `_client_template/edit_template.php`
- `_client_template/templates.php`
- `config/email.php`

---

## [1.1.0] - 2024-12-04

### ✨ Added
- **Signature Display:** Added signature visualization in `contract_detail.php`
- **Contract Number Generation:** Implemented sequential contract numbering with transaction locking
- **PDF Storage:** PDFs are now stored with `pdf_path` in database

### ✅ Fixed
- **Signature Path Bug:** Fixed `contract_signatures` table query to use `contracts.signature_path`
- **Contract Content Save:** `fill_and_sign.php` now saves filled `contract_html` to database

---

## [1.0.0] - 2024-11-15

### 🎉 Initial Release
- **Template Management:** Create and manage contract templates
- **Field Mapping:** Automatic placeholder detection and field mapping
- **Electronic Signature:** Basic SES (Simple Electronic Signature)
- **PDF Generation:** Automatic PDF generation with TCPDF
- **Email Delivery:** SMTP email delivery system
- **Multi-Tenant:** Support for multiple client instances (Mindloop, RoseUp)
- **Contract Tracking:** Basic contract status tracking (sent, signed)

### 🏗️ Core Features
- Template CRUD operations
- Field definitions and mapping
- Signature capture (canvas-based)
- PDF generation with signature
- Email notifications
- Contract detail view
- Contract list view

---

## Legend

- 🎉 **Initial Release** - First version
- ✨ **Added** - New features
- ✅ **Fixed** - Bug fixes
- 🔧 **Changed** - Changes in existing functionality
- 🗑️ **Deprecated** - Soon-to-be removed features
- ❌ **Removed** - Removed features
- 🔐 **Security** - Security improvements
- 🚧 **In Development** - Work in progress

---

[Unreleased]: https://github.com/Ai-Mindloop-SRL/contractdigital-platform/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/Ai-Mindloop-SRL/contractdigital-platform/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/Ai-Mindloop-SRL/contractdigital-platform/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/Ai-Mindloop-SRL/contractdigital-platform/releases/tag/v1.0.0
