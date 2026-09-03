<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Database hooks
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Install the plugin
 */
function plugin_hospitalcmms_install() {
    global $DB;

    $queries = [];

    // Medical Equipment Categories (Departments)
    $queries[] = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_categories` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Medical Equipment Types
    $queries[] = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_types` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Medical Equipment Models
    $queries[] = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_models` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Medical Equipment
    $queries[] = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_equipments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Maintenance Tasks (Preventive Maintenance Schedule)
    $queries[] = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_maintenance_tasks` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Maintenance History
    $queries[] = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_maintenance_history` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // User-Department assignments
    $queries[] = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_user_departments` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    // Subscriptions table
    $queries[] = "CREATE TABLE IF NOT EXISTS `glpi_plugin_hospitalcmms_subscriptions` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    foreach ($queries as $query) {
        $DB->doQuery($query);
    }

    // Update app name in config
    $DB->update('glpi_configs', [
        'value' => 'Hospital CMMS',
    ], [
        'context' => 'core',
        'name'    => 'config_name',
    ]);

    // Insert default department categories
    $default_departments = [
        'Emergency Department',
        'Intensive Care Unit (ICU)',
        'Surgery',
        'Radiology',
        'Laboratory',
        'Pharmacy',
        'Cardiology',
        'Oncology',
        'Pediatrics',
        'Obstetrics',
        'Orthopedics',
        'Neurology',
        'Dental',
        'Ophthalmology',
        'Physical Therapy',
        'Administration',
    ];

    foreach ($default_departments as $dept) {
        $DB->insert('glpi_plugin_hospitalcmms_categories', [
            'name'              => $dept,
            'entities_id'       => -1,
            'is_recursive'      => 1,
            'is_active'         => 1,
        ]);
    }

    // Insert default medical equipment types
    $default_types = [
        'Imaging Equipment',
        'Patient Monitoring',
        'Surgical Instruments',
        'Laboratory Equipment',
        'Life Support',
        'Diagnostic Equipment',
        'Therapeutic Equipment',
        'Dental Equipment',
        'Emergency Equipment',
        'Sterilization Equipment',
        'Rehabilitation Equipment',
        'Medical Furniture',
        'Respiratory Equipment',
        'Cardiology Equipment',
        'Ophthalmology Equipment',
    ];

    foreach ($default_types as $type) {
        $DB->insert('glpi_plugin_hospitalcmms_types', [
            'name'          => $type,
            'entities_id'   => -1,
            'is_recursive'  => 1,
        ]);
    }

    return true;
}

/**
 * Uninstall the plugin
 */
function plugin_hospitalcmms_uninstall() {
    global $DB;

    $queries = [
        "DROP TABLE IF EXISTS `glpi_plugin_hospitalcmms_subscriptions`",
        "DROP TABLE IF EXISTS `glpi_plugin_hospitalcmms_user_departments`",
        "DROP TABLE IF EXISTS `glpi_plugin_hospitalcmms_maintenance_history`",
        "DROP TABLE IF EXISTS `glpi_plugin_hospitalcmms_maintenance_tasks`",
        "DROP TABLE IF EXISTS `glpi_plugin_hospitalcmms_equipments`",
        "DROP TABLE IF EXISTS `glpi_plugin_hospitalcmms_models`",
        "DROP TABLE IF EXISTS `glpi_plugin_hospitalcmms_types`",
        "DROP TABLE IF EXISTS `glpi_plugin_hospitalcmms_categories`",
    ];

    foreach ($queries as $query) {
        $DB->doQuery($query);
    }

    return true;
}
