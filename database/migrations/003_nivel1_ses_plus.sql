-- NIVEL 1 - MINIM LEGAL (SES+)
-- VERSIUNE SAFE - Nu dă eroare dacă coloanele există deja
-- Modificări pentru Mindloop ContractDigital

-- Procedură pentru a adăuga coloană doar dacă nu există
DELIMITER $$

DROP PROCEDURE IF EXISTS AddColumnIfNotExists$$
CREATE PROCEDURE AddColumnIfNotExists(
    IN tableName VARCHAR(128),
    IN columnName VARCHAR(128),
    IN columnDefinition TEXT
)
BEGIN
    DECLARE col_count INT;
    
    SELECT COUNT(*) INTO col_count
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = tableName
      AND COLUMN_NAME = columnName;
    
    IF col_count = 0 THEN
        SET @sql = CONCAT('ALTER TABLE ', tableName, ' ADD COLUMN ', columnName, ' ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- 1. CONSIMȚĂMÂNT EXPLICIT
CALL AddColumnIfNotExists('contract_signatures', 'consent_given', "BOOLEAN DEFAULT 0 COMMENT 'Toate consimțămintele au fost date'");
CALL AddColumnIfNotExists('contract_signatures', 'consent_timestamp', "DATETIME COMMENT 'Data/ora când a dat consimțământul'");
CALL AddColumnIfNotExists('contract_signatures', 'consent_ip', "VARCHAR(45) COMMENT 'IP-ul de la consimțământ'");
CALL AddColumnIfNotExists('contract_signatures', 'consent_read', "BOOLEAN DEFAULT 0 COMMENT 'A citit contractul'");
CALL AddColumnIfNotExists('contract_signatures', 'consent_sign', "BOOLEAN DEFAULT 0 COMMENT 'Acceptă semnătura electronică'");
CALL AddColumnIfNotExists('contract_signatures', 'consent_gdpr', "BOOLEAN DEFAULT 0 COMMENT 'Acceptă GDPR'");

-- 2. HASH SHA-256 (Integritate Document)
CALL AddColumnIfNotExists('contract_signatures', 'contract_hash_before', "VARCHAR(64) COMMENT 'SHA-256 al contract_content înainte de semnare'");
CALL AddColumnIfNotExists('contract_signatures', 'pdf_hash_after', "VARCHAR(64) COMMENT 'SHA-256 al PDF-ului final'");

-- 3. USER-AGENT COMPLET
CALL AddColumnIfNotExists('contract_signatures', 'user_agent', "TEXT COMMENT 'User-Agent string complet'");
CALL AddColumnIfNotExists('contract_signatures', 'user_agent_parsed', "JSON COMMENT 'User-Agent parsed (browser, OS, device)'");
CALL AddColumnIfNotExists('contract_signatures', 'screen_resolution', "VARCHAR(20) COMMENT 'Rezoluție ecran (ex: 1920x1080)'");
CALL AddColumnIfNotExists('contract_signatures', 'timezone', "VARCHAR(50) COMMENT 'Timezone client (ex: Europe/Bucharest)'");
CALL AddColumnIfNotExists('contract_signatures', 'device_type', "VARCHAR(20) COMMENT 'Desktop/Mobile/Tablet'");

-- Cleanup
DROP PROCEDURE IF EXISTS AddColumnIfNotExists;

-- Verificare finală
SELECT 
    CONCAT('✅ Coloana ', COLUMN_NAME, ' există') AS Status,
    COLUMN_TYPE AS Type,
    COLUMN_COMMENT AS Comment
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'contract_signatures'
  AND COLUMN_NAME IN (
    'consent_given',
    'consent_timestamp',
    'consent_ip',
    'consent_read',
    'consent_sign',
    'consent_gdpr',
    'contract_hash_before',
    'pdf_hash_after',
    'user_agent',
    'user_agent_parsed',
    'screen_resolution',
    'timezone',
    'device_type'
  )
ORDER BY COLUMN_NAME;
