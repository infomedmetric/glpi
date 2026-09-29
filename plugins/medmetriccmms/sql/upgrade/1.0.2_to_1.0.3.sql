-- ---------------------------------------------------------------------
-- MedMetric CMMS 1.0.2 -> 1.0.3
-- Adds calibration reminder fields on maintenance plans
-- ---------------------------------------------------------------------

ALTER TABLE `glpi_plugin_medmetriccmms_maintenanceplans`
  ADD COLUMN `calibration_reminder_days` int NOT NULL DEFAULT 14 AFTER `maintenancekind`;
