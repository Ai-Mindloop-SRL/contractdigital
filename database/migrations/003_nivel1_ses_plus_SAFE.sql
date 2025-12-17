-- =====================================================
-- NIVEL 1 - MINIM LEGAL (SES+) - SAFE VERSION
-- Only adds columns if they don't exist
-- =====================================================

USE r68649site_contractdigital_db;

-- Check and add columns one by one (safe for re-running)

-- 1. EXPLICIT CONSENT
SET @dbname = 'r68649site_contractdigital_db';
SET @tablename = 'contract_signatures';

-- consent_given
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='consent_given');
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE contract_signatures ADD COLUMN consent_given TINYINT(1) DEFAULT 0 COMMENT "User gave explicit consent to sign"',
    'SELECT "Column consent_given already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- consent_timestamp
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='consent_timestamp');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN consent_timestamp DATETIME DEFAULT NULL COMMENT "When consent was given"',
    'SELECT "Column consent_timestamp already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- consent_ip
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='consent_ip');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN consent_ip VARCHAR(45) DEFAULT NULL COMMENT "IP address when consent was given"',
    'SELECT "Column consent_ip already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- consent_read
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='consent_read');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN consent_read TINYINT(1) DEFAULT 0 COMMENT "User confirmed reading the contract"',
    'SELECT "Column consent_read already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- consent_sign
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='consent_sign');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN consent_sign TINYINT(1) DEFAULT 0 COMMENT "User confirmed electronic signature"',
    'SELECT "Column consent_sign already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- consent_gdpr
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='consent_gdpr');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN consent_gdpr TINYINT(1) DEFAULT 0 COMMENT "User agreed to GDPR data processing"',
    'SELECT "Column consent_gdpr already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. DOCUMENT INTEGRITY

-- contract_hash_before
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='contract_hash_before');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN contract_hash_before VARCHAR(64) DEFAULT NULL COMMENT "SHA-256 hash of contract HTML before signing"',
    'SELECT "Column contract_hash_before already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- pdf_hash_after
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='pdf_hash_after');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN pdf_hash_after VARCHAR(64) DEFAULT NULL COMMENT "SHA-256 hash of final PDF after signing"',
    'SELECT "Column pdf_hash_after already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. DETAILED USER-AGENT

-- user_agent
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='user_agent');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN user_agent TEXT DEFAULT NULL COMMENT "Full user agent string"',
    'SELECT "Column user_agent already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- user_agent_parsed
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='user_agent_parsed');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN user_agent_parsed JSON DEFAULT NULL COMMENT "Parsed user agent (browser, OS, device)"',
    'SELECT "Column user_agent_parsed already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- screen_resolution
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='screen_resolution');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN screen_resolution VARCHAR(20) DEFAULT NULL COMMENT "Screen resolution (e.g., 1920x1080)"',
    'SELECT "Column screen_resolution already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- timezone
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='timezone');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN timezone VARCHAR(50) DEFAULT NULL COMMENT "User timezone (e.g., Europe/Bucharest)"',
    'SELECT "Column timezone already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- device_type
SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND COLUMN_NAME='device_type');
SET @sql = IF(@col_exists = 0,
    'ALTER TABLE contract_signatures ADD COLUMN device_type VARCHAR(20) DEFAULT NULL COMMENT "Device type: desktop, mobile, tablet"',
    'SELECT "Column device_type already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes (safe)
SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND INDEX_NAME='idx_consent_timestamp');
SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_consent_timestamp ON contract_signatures(consent_timestamp)',
    'SELECT "Index idx_consent_timestamp already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA=@dbname AND TABLE_NAME=@tablename AND INDEX_NAME='idx_contract_hash');
SET @sql = IF(@index_exists = 0,
    'CREATE INDEX idx_contract_hash ON contract_signatures(contract_hash_before)',
    'SELECT "Index idx_contract_hash already exists" AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- VERIFICATION
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
