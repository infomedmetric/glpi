-- ---------------------------------------------------------------------
-- MedMetric CMMS 1.0.0 - install schema and seed data
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_departments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(64) DEFAULT NULL,
  `comment` text,
  `locations_id` int unsigned NOT NULL DEFAULT 0,
  `departments_id` int unsigned NOT NULL DEFAULT 0,
  `is_helpdesk_visible` tinyint NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_id` (`locations_id`),
  KEY `departments_id` (`departments_id`),
  KEY `name` (`name`(120))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_structures` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `locations_id` int unsigned NOT NULL DEFAULT 0,
  `structures_id` int unsigned NOT NULL DEFAULT 0,
  `level` int NOT NULL DEFAULT 1,
  `structurekind` varchar(64) DEFAULT 'building',
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_id` (`locations_id`),
  KEY `structures_id` (`structures_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_equipments` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `serial` varchar(255) DEFAULT NULL,
  `otherserial` varchar(255) DEFAULT NULL,
  `asset_tag` varchar(128) DEFAULT NULL,
  `comment` text,
  `plugin_medmetriccmms_departments_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_structures_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_equipmenttypes_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_equipmentmodels_id` int unsigned NOT NULL DEFAULT 0,
  `locations_id` int unsigned NOT NULL DEFAULT 0,
  `users_id` int unsigned NOT NULL DEFAULT 0,
  `users_id_tech` int unsigned NOT NULL DEFAULT 0,
  `groups_id_tech` int unsigned NOT NULL DEFAULT 0,
  `manufacturers_id` int unsigned NOT NULL DEFAULT 0,
  `states_id` int unsigned NOT NULL DEFAULT 0,
  `manufacture_year` varchar(8) DEFAULT NULL,
  `criticality` int NOT NULL DEFAULT 2,
  `purchase_date` date DEFAULT NULL,
  `commissioning_date` date DEFAULT NULL,
  `warranty_end` date DEFAULT NULL,
  `last_maintenance` date DEFAULT NULL,
  `next_maintenance` date DEFAULT NULL,
  `operating_hours` decimal(12,1) NOT NULL DEFAULT 0.0,
  `status` int NOT NULL DEFAULT 1,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `is_deleted` tinyint NOT NULL DEFAULT 0,
  `is_template` tinyint NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_departments_id` (`plugin_medmetriccmms_departments_id`),
  KEY `plugin_medmetriccmms_structures_id` (`plugin_medmetriccmms_structures_id`),
  KEY `plugin_medmetriccmms_equipmenttypes_id` (`plugin_medmetriccmms_equipmenttypes_id`),
  KEY `plugin_medmetriccmms_equipmentmodels_id` (`plugin_medmetriccmms_equipmentmodels_id`),
  KEY `locations_id` (`locations_id`),
  KEY `users_id` (`users_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `manufacturers_id` (`manufacturers_id`),
  KEY `states_id` (`states_id`),
  KEY `status` (`status`),
  KEY `next_maintenance` (`next_maintenance`),
  KEY `entities_id` (`entities_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_equipmenttypes` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `icon` varchar(64) DEFAULT NULL,
  `plugin_medmetriccmms_equipments_id` int unsigned NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`(120))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_equipmentmodels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `plugin_medmetriccmms_equipmenttypes_id` int unsigned NOT NULL DEFAULT 0,
  `manufacturer` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_equipmenttypes_id` (`plugin_medmetriccmms_equipmenttypes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_maintenances` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(64) DEFAULT NULL,
  `comment` text,
  `plugin_medmetriccmms_equipments_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_maintenanceplans_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_workorders_id` int unsigned NOT NULL DEFAULT 0,
  `users_id_tech` int unsigned NOT NULL DEFAULT 0,
  `date` date DEFAULT NULL,
  `duration` int NOT NULL DEFAULT 0,
  `maintenancekind` int NOT NULL DEFAULT 1,
  `checklist` text,
  `state` int NOT NULL DEFAULT 1,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_equipments_id` (`plugin_medmetriccmms_equipments_id`),
  KEY `plugin_medmetriccmms_maintenanceplans_id` (`plugin_medmetriccmms_maintenanceplans_id`),
  KEY `plugin_medmetriccmms_workorders_id` (`plugin_medmetriccmms_workorders_id`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `date` (`date`),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_maintenanceplans` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `plugin_medmetriccmms_equipments_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_equipmenttypes_id` int unsigned NOT NULL DEFAULT 0,
  `periodicity` int NOT NULL DEFAULT 30,
  `periodicity_unit` int NOT NULL DEFAULT 2,
  `next_creation` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `checklist` text,
  `maintenancekind` int NOT NULL DEFAULT 2,
  `is_active` tinyint NOT NULL DEFAULT 1,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_equipments_id` (`plugin_medmetriccmms_equipments_id`),
  KEY `plugin_medmetriccmms_equipmenttypes_id` (`plugin_medmetriccmms_equipmenttypes_id`),
  KEY `next_creation` (`next_creation`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_workorders` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `code` varchar(64) DEFAULT NULL,
  `content` text,
  `solution_description` text,
  `plugin_medmetriccmms_equipments_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_maintenanceplans_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_departments_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_vendors_id` int unsigned NOT NULL DEFAULT 0,
  `workorderkind` int NOT NULL DEFAULT 1,
  `priority` int NOT NULL DEFAULT 3,
  `status` int NOT NULL DEFAULT 1,
  `technician_kind` int NOT NULL DEFAULT 0,
  `users_id_tech` int unsigned NOT NULL DEFAULT 0,
  `groups_id_tech` int unsigned NOT NULL DEFAULT 0,
  `users_id_recipient` int unsigned NOT NULL DEFAULT 0,
  `date` timestamp NULL DEFAULT NULL,
  `date_planned` timestamp NULL DEFAULT NULL,
  `date_started` timestamp NULL DEFAULT NULL,
  `date_closed` timestamp NULL DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `downtime_duration` int NOT NULL DEFAULT 0,
  `cost_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_equipments_id` (`plugin_medmetriccmms_equipments_id`),
  KEY `plugin_medmetriccmms_maintenanceplans_id` (`plugin_medmetriccmms_maintenanceplans_id`),
  KEY `plugin_medmetriccmms_departments_id` (`plugin_medmetriccmms_departments_id`),
  KEY `plugin_medmetriccmms_vendors_id` (`plugin_medmetriccmms_vendors_id`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY `users_id_tech` (`users_id_tech`),
  KEY `groups_id_tech` (`groups_id_tech`),
  KEY `date` (`date`),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_notifications` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `content` text,
  `plugin_medmetriccmms_equipments_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_workorders_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_departments_id` int unsigned NOT NULL DEFAULT 0,
  `notificationkind` int NOT NULL DEFAULT 1,
  `priority` int NOT NULL DEFAULT 3,
  `status` int NOT NULL DEFAULT 1,
  `users_id` int unsigned NOT NULL DEFAULT 0,
  `users_id_ack` int unsigned NOT NULL DEFAULT 0,
  `date_ack` timestamp NULL DEFAULT NULL,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_equipments_id` (`plugin_medmetriccmms_equipments_id`),
  KEY `plugin_medmetriccmms_workorders_id` (`plugin_medmetriccmms_workorders_id`),
  KEY `plugin_medmetriccmms_departments_id` (`plugin_medmetriccmms_departments_id`),
  KEY `status` (`status`),
  KEY `users_id` (`users_id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_solutions` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `content` longtext,
  `plugin_medmetriccmms_equipmenttypes_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_workorders_id` int unsigned NOT NULL DEFAULT 0,
  `solutionkind` int NOT NULL DEFAULT 1,
  `users_id` int unsigned NOT NULL DEFAULT 0,
  `is_validated` tinyint NOT NULL DEFAULT 0,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_equipmenttypes_id` (`plugin_medmetriccmms_equipmenttypes_id`),
  KEY `plugin_medmetriccmms_workorders_id` (`plugin_medmetriccmms_workorders_id`),
  KEY `users_id` (`users_id`),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_inventories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `ref` varchar(128) DEFAULT NULL,
  `barcode` varchar(128) DEFAULT NULL,
  `comment` text,
  `inventorykind` int NOT NULL DEFAULT 1,
  `stock_min` int NOT NULL DEFAULT 0,
  `stock_max` int NOT NULL DEFAULT 0,
  `stock_current` int NOT NULL DEFAULT 0,
  `stock_ordered` int NOT NULL DEFAULT 0,
  `locations_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_vendors_id` int unsigned NOT NULL DEFAULT 0,
  `price_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `is_deleted` tinyint NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ref` (`ref`),
  KEY `locations_id` (`locations_id`),
  KEY `plugin_medmetriccmms_vendors_id` (`plugin_medmetriccmms_vendors_id`),
  KEY `entities_id` (`entities_id`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_workorderinventories` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `plugin_medmetriccmms_workorders_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_inventories_id` int unsigned NOT NULL DEFAULT 0,
  `quantity` int NOT NULL DEFAULT 1,
  `price_unit` decimal(12,2) NOT NULL DEFAULT 0.00,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_workorders_id` (`plugin_medmetriccmms_workorders_id`),
  KEY `plugin_medmetriccmms_inventories_id` (`plugin_medmetriccmms_inventories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_vendors` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `phonenumber` varchar(64) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `comment` text,
  `vendorkind` int NOT NULL DEFAULT 1,
  `contact_name` varchar(255) DEFAULT NULL,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `name` (`name`(120)),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_contracts` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `comment` text,
  `contractkind` int NOT NULL DEFAULT 1,
  `plugin_medmetriccmms_equipments_id` int unsigned NOT NULL DEFAULT 0,
  `plugin_medmetriccmms_vendors_id` int unsigned NOT NULL DEFAULT 0,
  `ref` varchar(128) DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `renewal_date` date DEFAULT NULL,
  `cost_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `status` int NOT NULL DEFAULT 1,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plugin_medmetriccmms_equipments_id` (`plugin_medmetriccmms_equipments_id`),
  KEY `plugin_medmetriccmms_vendors_id` (`plugin_medmetriccmms_vendors_id`),
  KEY `end_date` (`end_date`),
  KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_aimodels` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `provider` varchar(64) DEFAULT 'openai',
  `model_id` varchar(128) DEFAULT NULL,
  `base_url` varchar(255) DEFAULT NULL,
  `api_key` varchar(255) DEFAULT NULL,
  `temperature` decimal(3,2) NOT NULL DEFAULT 0.20,
  `max_tokens` int NOT NULL DEFAULT 1024,
  `purpose` int NOT NULL DEFAULT 1,
  `is_active` tinyint NOT NULL DEFAULT 1,
  `comment` text,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `provider` (`provider`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_logs` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `logkind` int NOT NULL DEFAULT 1,
  `action` varchar(191) DEFAULT NULL,
  `itemtype` varchar(191) DEFAULT NULL,
  `items_id` int unsigned NOT NULL DEFAULT 0,
  `users_id` int unsigned NOT NULL DEFAULT 0,
  `request_payload` text,
  `response_summary` text,
  `tokens_used` int NOT NULL DEFAULT 0,
  `duration_ms` int NOT NULL DEFAULT 0,
  `date_creation` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `logkind` (`logkind`),
  KEY `itemtype` (`itemtype`),
  KEY `items_id` (`items_id`),
  KEY `users_id` (`users_id`),
  KEY `date_creation` (`date_creation`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_reports` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `reportkind` int NOT NULL DEFAULT 1,
  `parameters` text,
  `users_id` int unsigned NOT NULL DEFAULT 0,
  `entities_id` int unsigned NOT NULL DEFAULT 0,
  `is_recursive` tinyint NOT NULL DEFAULT 1,
  `date_creation` timestamp NULL DEFAULT NULL,
  `date_mod` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reportkind` (`reportkind`),
  KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci ROW_FORMAT=DYNAMIC;

-- Seed equipment types for a hospital environment
INSERT INTO `glpi_plugin_medmetriccmms_equipmenttypes` (`name`, `comment`, `date_creation`)
SELECT * FROM (
  SELECT 'Imaging' AS name, 'X-ray, CT, MRI, ultrasound, mammography' AS comment, NOW() AS date_creation
  UNION ALL SELECT 'Laboratory', 'Analyzers, centrifuges, microscopes', NOW()
  UNION ALL SELECT 'Patient Monitoring', 'Vital signs monitors, telemetry, ECG', NOW()
  UNION ALL SELECT 'Life Support', 'Ventilators, anesthesia machines, defibrillators', NOW()
  UNION ALL SELECT 'Infusion Therapy', 'Infusion pumps, syringe pumps', NOW()
  UNION ALL SELECT 'Sterilization', 'Autoclaves, washers disinfectors', NOW()
  UNION ALL SELECT 'Dialysis', 'Hemodialysis and related machines', NOW()
  UNION ALL SELECT 'General Biomedical', 'Other biomedical devices', NOW()
) AS seed
WHERE NOT EXISTS (SELECT 1 FROM `glpi_plugin_medmetriccmms_equipmenttypes` WHERE `name` = 'Imaging');
