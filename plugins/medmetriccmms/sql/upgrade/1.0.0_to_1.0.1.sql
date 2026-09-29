-- ---------------------------------------------------------------------
-- MedMetric CMMS 1.0.0 -> 1.0.1
-- Adds reading fields to equipment for compliance tracking
-- ---------------------------------------------------------------------

ALTER TABLE `glpi_plugin_medmetriccmms_equipments`
  ADD COLUMN IF NOT EXISTS `qa_required` tinyint NOT NULL DEFAULT 0 AFTER `operating_hours`,
  ADD COLUMN IF NOT EXISTS `risk_class` varchar(16) DEFAULT NULL AFTER `qa_required`;
