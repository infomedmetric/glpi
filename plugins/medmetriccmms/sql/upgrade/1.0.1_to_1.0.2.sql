-- ---------------------------------------------------------------------
-- MedMetric CMMS 1.0.1 -> 1.0.2
-- Adds SLA tracking columns to work orders
-- ---------------------------------------------------------------------

ALTER TABLE `glpi_plugin_medmetriccmms_workorders`
  ADD COLUMN `sla_hours` int NOT NULL DEFAULT 0 AFTER `due_date`,
  ADD COLUMN `sla_breached` tinyint NOT NULL DEFAULT 0 AFTER `sla_hours`;
