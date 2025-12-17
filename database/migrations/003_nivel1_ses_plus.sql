-- =====================================================
-- NIVEL 1 - MINIM LEGAL (SES+)
-- Simple Electronic Signature PLUS
-- =====================================================
-- Date: 2025-12-17
-- Purpose: Add legal signature enhancements
-- Legal Value: SES+ (harder to contest than basic SES)
-- =====================================================

USE r68649site_contractdigital_db;

-- Add columns to contract_signatures table
ALTER TABLE contract_signatures
-- 1. EXPLICIT CONSENT
ADD COLUMN consent_given TINYINT(1) DEFAULT 0 COMMENT 'User gave explicit consent to sign',
ADD COLUMN consent_timestamp DATETIME DEFAULT NULL COMMENT 'When consent was given',
ADD COLUMN consent_ip VARCHAR(45) DEFAULT NULL COMMENT 'IP address when consent was given',
ADD COLUMN consent_read TINYINT(1) DEFAULT 0 COMMENT 'User confirmed reading the contract',
ADD COLUMN consent_sign TINYINT(1) DEFAULT 0 COMMENT 'User confirmed electronic signature',
ADD COLUMN consent_gdpr TINYINT(1) DEFAULT 0 COMMENT 'User agreed to GDPR data processing',

-- 2. DOCUMENT INTEGRITY (SHA-256)
ADD COLUMN contract_hash_before VARCHAR(64) DEFAULT NULL COMMENT 'SHA-256 hash of contract HTML before signing',
ADD COLUMN pdf_hash_after VARCHAR(64) DEFAULT NULL COMMENT 'SHA-256 hash of final PDF after signing',

-- 3. DETAILED USER-AGENT
ADD COLUMN user_agent TEXT DEFAULT NULL COMMENT 'Full user agent string',
ADD COLUMN user_agent_parsed JSON DEFAULT NULL COMMENT 'Parsed user agent (browser, OS, device)',
ADD COLUMN screen_resolution VARCHAR(20) DEFAULT NULL COMMENT 'Screen resolution (e.g., 1920x1080)',
ADD COLUMN timezone VARCHAR(50) DEFAULT NULL COMMENT 'User timezone (e.g., Europe/Bucharest)',
ADD COLUMN device_type VARCHAR(20) DEFAULT NULL COMMENT 'Device type: desktop, mobile, tablet';

-- Add indexes for common queries
CREATE INDEX idx_consent_timestamp ON contract_signatures(consent_timestamp);
CREATE INDEX idx_contract_hash ON contract_signatures(contract_hash_before);

-- =====================================================
-- VERIFICATION QUERIES
-- =====================================================

-- Check if columns were added successfully
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'r68649site_contractdigital_db'
    AND TABLE_NAME = 'contract_signatures'
    AND COLUMN_NAME IN (
        'consent_given', 'consent_timestamp', 'consent_ip',
        'consent_read', 'consent_sign', 'consent_gdpr',
        'contract_hash_before', 'pdf_hash_after',
        'user_agent', 'user_agent_parsed', 'screen_resolution',
        'timezone', 'device_type'
    )
ORDER BY COLUMN_NAME;

-- Expected result: 13 rows (13 new columns)
