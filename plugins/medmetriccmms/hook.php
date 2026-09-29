<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - GLPI hook callbacks
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

// Defensive class loading for lifecycle functions: even if plugin_init was
// skipped in a previous failed request, install/uninstall/upgrade must work.
$medmetric_inc_dir = __DIR__ . '/inc';
foreach (['toolbox', 'config', 'log', 'aimodel', 'ai', 'department', 'structure',
             'equipment', 'equipmenttype', 'equipmentmodel', 'maintenance',
             'maintenanceplan', 'workorder', 'notification', 'solution',
             'inventory', 'vendor', 'contract', 'analytics', 'report',
             'menu', 'profile'] as $medmetric_class) {
    $medmetric_path = "$medmetric_inc_dir/$medmetric_class.class.php";
    if (file_exists($medmetric_path)) {
        require_once($medmetric_path);
    }
}
unset($medmetric_inc_dir, $medmetric_class, $medmetric_path);

/**
 * Plugin installation: schema, seed data, rights, cron.
 *
 * @return bool
 */
function plugin_medmetriccmms_install() {
    $migration = new Migration(PLUGIN_MEDMETRICCMMS_VERSION);
    $migration->displayMessage('Installing MedMetric CMMS');

    $db = $GLOBALS['DB'] ?? ($DB ?? null);
    $sql_file = Plugin::getPhpDir('medmetriccmms') . '/sql/install.sql';
    if (!$db->runFile($sql_file)) {
        $migration->displayMessage('Error running MedMetric CMMS install.sql');
        return false;
    }

    $migration->addConfig(
        [
            'ai_provider'      => 'openai',
            'ai_base_url'      => 'https://api.openai.com/v1',
            'ai_model'         => 'gpt-4o-mini',
            'ai_temperature'   => '0.2',
            'alert_frequency'  => '7',
            'default_priority' => '3',
        ],
        'plugin:medmetriccmms'
    );

    // Create the `plugin_medmetriccmms` right row for every profile (idempotent),
    // then give full rights to the administrators. Never call
    // ProfileRight::addProfileRights() with a name => rights map: it iterates
    // the values and would insert the ALLSTANDARDRIGHT integer as a right name.
    $profile_class = GlpiPlugin\Medmetriccmms\Profile::class;
    $profile_class::installRights();
    $profile_class::grantSuperAdmin();
    $profiles_id = (int) ($_SESSION['glpiactiveprofile']['id'] ?? 0);
    if ($profiles_id > 0) {
        ProfileRight::updateProfileRights($profiles_id, [$profile_class::RIGHTNAME => ALLSTANDARDRIGHT]);
    }

    $migration->updateDisplayPrefs([
        GlpiPlugin\Medmetriccmms\Equipment::class => [2, 3, 4, 7, 8],
        GlpiPlugin\Medmetriccmms\WorkOrder::class => [2, 3, 4, 7, 8],
    ]);

    $cron_names = [
        [GlpiPlugin\Medmetriccmms\MaintenancePlan::class, 'Plan', DAY_TIMESTAMP],
        [GlpiPlugin\Medmetriccmms\Notification::class, 'Alert', HOUR_TIMESTAMP],
    ];
    foreach ($cron_names as [$class, $name, $frequency]) {
        if (!CronTask::register($class, $name, $frequency)) {
            $migration->displayMessage("Failed to register cron task $class::$name");
        }
    }

    $migration->executeMigration();
    return true;
}

/**
 * Plugin uninstall: drop tables, remove config, rights and cron.
 *
 * @return bool
 */
function plugin_medmetriccmms_uninstall() {
    $migration = new Migration(PLUGIN_MEDMETRICCMMS_VERSION);
    $migration->displayMessage('Uninstalling MedMetric CMMS');

    $db = $GLOBALS['DB'] ?? ($DB ?? null);
    $sql_file = Plugin::getPhpDir('medmetriccmms') . '/sql/uninstall.sql';
    if (file_exists($sql_file)) {
        $db->runFile($sql_file);
    }

    $migration->removeConfig(
        [
            'ai_provider',
            'ai_base_url',
            'ai_model',
            'ai_temperature',
            'alert_frequency',
            'default_priority',
        ],
        'plugin:medmetriccmms'
    );

    ProfileRight::deleteProfileRights(['plugin_medmetriccmms']);
    $migration->updateDisplayPrefs([
        GlpiPlugin\Medmetriccmms\Equipment::class => [],
        GlpiPlugin\Medmetriccmms\WorkOrder::class => [],
    ]);
    CronTask::unregister('medmetriccmms');
    $migration->executeMigration();
    return true;
}

/**
 * Plugin upgrade path.
 *
 * @param string $from_version previous version
 *
 * @return bool
 */
function plugin_medmetriccmms_upgrade($from_version) {
    $migration = new Migration(PLUGIN_MEDMETRICCMMS_VERSION);
    $migration->displayMessage("Upgrading MedMetric CMMS from $from_version");

    $upgrade_dir = Plugin::getPhpDir('medmetriccmms') . '/sql/upgrade';
    $target_map = [
        '1.0.0' => '1.0.0_to_1.0.1.sql',
        '1.0.1' => '1.0.1_to_1.0.2.sql',
        '1.0.2' => '1.0.2_to_1.0.3.sql',
    ];
    $db = $GLOBALS['DB'] ?? ($DB ?? null);
    if (isset($target_map[(string) $from_version])) {
        $file = $upgrade_dir . '/' . $target_map[(string) $from_version];
        if (file_exists($file)) {
            $db->runFile($file);
        }
    }

    $migration->executeMigration();
    return true;
}

/**
 * Default WHERE hook: restrict plugin itemtypes to active entity.
 *
 * @param string $itemtype itemtype class name
 *
 * @return string|false
 */
function plugin_medmetriccmms_addDefaultWhere($itemtype) {
    if (!is_string($itemtype) || !str_starts_with($itemtype, 'GlpiPlugin\Medmetriccmms\\')) {
        return false;
    }
    $item = new $itemtype();
    if (!$item instanceof CommonDBTM || !$item->isField('entities_id')) {
        return false;
    }
    $table = getTableForItemType($itemtype);
    $entities = [];
    $base = (int) ($_SESSION['glpiactive_entity'] ?? 0);
    if (!empty($_SESSION['glpiactive_entity_recursive'])) {
        $sons = getSonsOf('glpi_entities', $base);
        $entities = array_map('intval', (array) $sons);
    } else {
        $entities = [$base];
    }
    $in = implode(',', $entities);
    return "`" . $item->getTable() . "`.`entities_id` IN ($in)";
}

/**
 * Central dashboard widget hook: compact CMMS status card.
 *
 * @return void
 */
function plugin_medmetriccmms_dashboard_widget() {
    Analytics::showCentralWidget();
}
