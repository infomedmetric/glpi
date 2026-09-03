-- Hospital CMMS Installation Script
-- Run this manually if automatic installation fails

-- Medical Equipment Categories (Departments)
CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_categories` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `comment` text,
    `completename` text,
    `plugin_hospitalcmms_categories_id` int(11) NOT NULL DEFAULT '0',
    `level` int(11) NOT NULL DEFAULT '0',
    `ancestors_cache` longtext,
    `sons_cache` longtext,
    `entities_id` int(11) NOT NULL DEFAULT '-1',
    `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
    `is_active` tinyint(1) NOT NULL DEFAULT '1',
    `date_mod` datetime DEFAULT NULL,
    `date_creation` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `name` (`name`),
    KEY `entities_id` (`entities_id`),
    KEY `is_recursive` (`is_recursive`),
    KEY `plugin_hospitalcmms_categories_id` (`plugin_hospitalcmms_categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medical Equipment Types
CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `comment` text,
    `entities_id` int(11) NOT NULL DEFAULT '-1',
    `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
    `date_mod` datetime DEFAULT NULL,
    `date_creation` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `name` (`name`),
    KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medical Equipment Models
CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_models` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `comment` text,
    `entities_id` int(11) NOT NULL DEFAULT '-1',
    `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
    `date_mod` datetime DEFAULT NULL,
    `date_creation` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `name` (`name`),
    KEY `entities_id` (`entities_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medical Equipment
CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_equipments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `serial` varchar(255) NOT NULL DEFAULT '',
    `otherserial` varchar(255) NOT NULL DEFAULT '',
    `comment` text,
    `plugin_hospitalcmms_categories_id` int(11) NOT NULL DEFAULT '0',
    `plugin_hospitalcmms_types_id` int(11) NOT NULL DEFAULT '0',
    `plugin_hospitalcmms_models_id` int(11) NOT NULL DEFAULT '0',
    `manufacturers_id` int(11) NOT NULL DEFAULT '0',
    `locations_id` int(11) NOT NULL DEFAULT '0',
    `states_id` int(11) NOT NULL DEFAULT '0',
    `users_id` int(11) NOT NULL DEFAULT '0',
    `users_id_tech` int(11) NOT NULL DEFAULT '0',
    `groups_id` int(11) NOT NULL DEFAULT '0',
    `groups_id_tech` int(11) NOT NULL DEFAULT '0',
    `entities_id` int(11) NOT NULL DEFAULT '-1',
    `is_recursive` tinyint(1) NOT NULL DEFAULT '0',
    `is_deleted` tinyint(1) NOT NULL DEFAULT '0',
    `date_mod` datetime DEFAULT NULL,
    `date_creation` datetime DEFAULT NULL,
    `date_purchase` date DEFAULT NULL,
    `date_warranty` date DEFAULT NULL,
    `date_commissioning` date DEFAULT NULL,
    `warranty_duration` int(11) NOT NULL DEFAULT '0',
    `warranty_info` text,
    `value` decimal(20,4) NOT NULL DEFAULT '0',
    `sink_type` int(11) NOT NULL DEFAULT '0',
    `last_calibration_date` date DEFAULT NULL,
    `next_calibration_date` date DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `name` (`name`),
    KEY `serial` (`serial`),
    KEY `plugin_hospitalcmms_categories_id` (`plugin_hospitalcmms_categories_id`),
    KEY `plugin_hospitalcmms_types_id` (`plugin_hospitalcmms_types_id`),
    KEY `plugin_hospitalcmms_models_id` (`plugin_hospitalcmms_models_id`),
    KEY `manufacturers_id` (`manufacturers_id`),
    KEY `locations_id` (`locations_id`),
    KEY `states_id` (`states_id`),
    KEY `entities_id` (`entities_id`),
    KEY `is_deleted` (`is_deleted`),
    KEY `users_id_tech` (`users_id_tech`),
    KEY `groups_id_tech` (`groups_id_tech`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintenance Tasks (Preventive Maintenance Schedule)
CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_maintenance_tasks` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(255) NOT NULL DEFAULT '',
    `comment` text,
    `plugin_hospitalcmms_equipments_id` int(11) NOT NULL DEFAULT '0',
    `type` varchar(100) NOT NULL DEFAULT 'preventive',
    `frequency` int(11) NOT NULL DEFAULT '1',
    `frequency_unit` varchar(20) NOT NULL DEFAULT 'month',
    `next_execution_date` date DEFAULT NULL,
    `last_execution_date` date DEFAULT NULL,
    `users_id_tech` int(11) NOT NULL DEFAULT '0',
    `groups_id_tech` int(11) NOT NULL DEFAULT '0',
    `entities_id` int(11) NOT NULL DEFAULT '-1',
    `is_active` tinyint(1) NOT NULL DEFAULT '1',
    `date_mod` datetime DEFAULT NULL,
    `date_creation` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_hospitalcmms_equipments_id` (`plugin_hospitalcmms_equipments_id`),
    KEY `next_execution_date` (`next_execution_date`),
    KEY `users_id_tech` (`users_id_tech`),
    KEY `groups_id_tech` (`groups_id_tech`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Maintenance History
CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_maintenance_history` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `plugin_hospitalcmms_equipments_id` int(11) NOT NULL DEFAULT '0',
    `plugin_hospitalcmms_maintenance_tasks_id` int(11) NOT NULL DEFAULT '0',
    `action` varchar(100) NOT NULL DEFAULT '',
    `comment` text,
    `users_id` int(11) NOT NULL DEFAULT '0',
    `execution_date` datetime DEFAULT NULL,
    `duration` int(11) NOT NULL DEFAULT '0',
    `cost` decimal(20,4) NOT NULL DEFAULT '0',
    `entities_id` int(11) NOT NULL DEFAULT '-1',
    `date_mod` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `plugin_hospitalcmms_equipments_id` (`plugin_hospitalcmms_equipments_id`),
    KEY `plugin_hospitalcmms_maintenance_tasks_id` (`plugin_hospitalcmms_maintenance_tasks_id`),
    KEY `execution_date` (`execution_date`),
    KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User-Department assignments (Permission System)
CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_user_departments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `users_id` int(11) NOT NULL DEFAULT '0',
    `plugin_hospitalcmms_categories_id` int(11) NOT NULL DEFAULT '0',
    `role` varchar(50) NOT NULL DEFAULT 'staff',
    `entities_id` int(11) NOT NULL DEFAULT '-1',
    `date_creation` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_user_department` (`users_id`, `plugin_hospitalcmms_categories_id`),
    KEY `users_id` (`users_id`),
    KEY `plugin_hospitalcmms_categories_id` (`plugin_hospitalcmms_categories_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscriptions table
CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_subscriptions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `hospital_name` varchar(255) NOT NULL DEFAULT '',
    `contact_name` varchar(255) NOT NULL DEFAULT '',
    `email` varchar(255) NOT NULL DEFAULT '',
    `phone` varchar(50) NOT NULL DEFAULT '',
    `country` varchar(50) NOT NULL DEFAULT '',
    `login` varchar(100) NOT NULL DEFAULT '',
    `password_hash` varchar(255) NOT NULL DEFAULT '',
    `plan` varchar(50) NOT NULL DEFAULT 'basic',
    `status` varchar(50) NOT NULL DEFAULT 'trial',
    `trial_start` datetime DEFAULT NULL,
    `trial_end` datetime DEFAULT NULL,
    `subscription_start` datetime DEFAULT NULL,
    `subscription_end` datetime DEFAULT NULL,
    `payment_method` varchar(50) NOT NULL DEFAULT '',
    `last_payment_date` datetime DEFAULT NULL,
    `next_payment_date` datetime DEFAULT NULL,
    `users_id` int(11) NOT NULL DEFAULT '0',
    `entities_id` int(11) NOT NULL DEFAULT '-1',
    `is_active` tinyint(1) NOT NULL DEFAULT '1',
    `date_creation` datetime DEFAULT NULL,
    `date_mod` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_email` (`email`),
    UNIQUE KEY `unique_login` (`login`),
    KEY `status` (`status`),
    KEY `plan` (`plan`),
    KEY `trial_end` (`trial_end`),
    KEY `users_id` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default departments
INSERT INTO `glpi_plugin_hospitalcmms_categories` (`name`, `entities_id`, `is_recursive`, `is_active`) VALUES
('Emergency Department', -1, 1, 1),
('Intensive Care Unit (ICU)', -1, 1, 1),
('Surgery', -1, 1, 1),
('Radiology', -1, 1, 1),
('Laboratory', -1, 1, 1),
('Pharmacy', -1, 1, 1),
('Cardiology', -1, 1, 1),
('Oncology', -1, 1, 1),
('Pediatrics', -1, 1, 1),
('Obstetrics', -1, 1, 1),
('Orthopedics', -1, 1, 1),
('Neurology', -1, 1, 1),
('Dental', -1, 1, 1),
('Ophthalmology', -1, 1, 1),
('Physical Therapy', -1, 1, 1),
('Administration', -1, 1, 1);

-- Insert default equipment types
INSERT INTO `glpi_plugin_hospitalcmms_types` (`name`, `entities_id`, `is_recursive`) VALUES
('Imaging Equipment', -1, 1),
('Patient Monitoring', -1, 1),
('Surgical Instruments', -1, 1),
('Laboratory Equipment', -1, 1),
('Life Support', -1, 1),
('Diagnostic Equipment', -1, 1),
('Therapeutic Equipment', -1, 1),
('Dental Equipment', -1, 1),
('Emergency Equipment', -1, 1),
('Sterilization Equipment', -1, 1),
('Rehabilitation Equipment', -1, 1),
('Medical Furniture', -1, 1),
('Respiratory Equipment', -1, 1),
('Cardiology Equipment', -1, 1),
('Ophthalmology Equipment', -1, 1);
