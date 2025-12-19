-- Migration: Add fields for RoseUp BNI contract
-- Date: 2025-12-19
-- Description: Adds judet, taxa_membru, and taxa_inscriere fields to field_definitions

-- ============================================================
-- Add judet field (County for RoseUp BNI)
-- ============================================================
INSERT INTO `field_definitions` 
  (`field_name`, `field_label`, `field_type`, `is_required`, `placeholder`) 
VALUES 
  ('judet', 'Județ', 'input', 0, 'Ex: Constanța, București, Cluj')
ON DUPLICATE KEY UPDATE 
  `field_label` = 'Județ',
  `field_type` = 'input',
  `is_required` = 0,
  `placeholder` = 'Ex: Constanța, București, Cluj';

-- ============================================================
-- Add taxa_membru field (Member Fee for RoseUp BNI)
-- ============================================================
INSERT INTO `field_definitions` 
  (`field_name`, `field_label`, `field_type`, `is_required`, `placeholder`) 
VALUES 
  ('taxa_membru', 'Taxă Membru (EUR)', 'input', 0, 'Ex: 450')
ON DUPLICATE KEY UPDATE 
  `field_label` = 'Taxă Membru (EUR)',
  `field_type` = 'input',
  `is_required` = 0,
  `placeholder` = 'Ex: 450';

-- ============================================================
-- Add taxa_inscriere field (Registration Fee for RoseUp BNI)
-- ============================================================
INSERT INTO `field_definitions` 
  (`field_name`, `field_label`, `field_type`, `is_required`, `placeholder`) 
VALUES 
  ('taxa_inscriere', 'Taxă Înscriere (EUR)', 'input', 0, 'Ex: 250')
ON DUPLICATE KEY UPDATE 
  `field_label` = 'Taxă Înscriere (EUR)',
  `field_type` = 'input',
  `is_required` = 0,
  `placeholder` = 'Ex: 250';

-- ============================================================
-- Verification Query (run this after migration to verify)
-- ============================================================
-- SELECT field_name, field_label, field_type, is_required, placeholder 
-- FROM field_definitions 
-- WHERE field_name IN ('judet', 'taxa_membru', 'taxa_inscriere');
