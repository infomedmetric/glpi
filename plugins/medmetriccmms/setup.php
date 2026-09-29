<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - Computerized Maintenance Management System for GLPI
 * ---------------------------------------------------------------------
 * Medical equipment maintenance, work orders, preventive plans,
 * spare parts inventory, vendors, contracts, analytics and AI assists.
 *
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

define('PLUGIN_MEDMETRICCMMS_VERSION', '1.0.2');
define('PLUGIN_MEDMETRICCMMS_MIN_GLPI', '11.0.0');
define('PLUGIN_MEDMETRICCMMS_MIN_PHP', '8.2');

/**
 * Init hooks of the plugin.
 *
 * @return void
 */
function plugin_init_medmetriccmms() {
    global $PLUGIN_HOOKS;

    // Load plugin classes. This plugin uses the classic inc/ layout, and
    // GLPI's plugin autoloader only maps src/, so the classes must be
    // required here on every load. Setup.php lives in the plugin root,
    // so __DIR__ is always the plugin directory.
    $inc_dir = __DIR__ . '/inc';
    foreach (['toolbox', 'config', 'log', 'aimodel', 'ai', 'department', 'structure',
                 'equipment', 'equipmenttype', 'equipmentmodel', 'maintenance',
                 'maintenanceplan', 'workorder', 'notification', 'solution',
                 'inventory', 'vendor', 'contract', 'analytics', 'report',
                 'menu', 'profile'] as $class_file) {
        $path = "$inc_dir/$class_file.class.php";
        if (file_exists($path)) {
            require_once($path);
        }
    }

    Plugin::registerClass(GlpiPlugin\Medmetriccmms\Equipment::class, ['addtabon' => 'Location']);
    Plugin::registerClass(GlpiPlugin\Medmetriccmms\WorkOrder::class, ['ticket_types' => true]);
    Plugin::registerClass(GlpiPlugin\Medmetriccmms\Inventory::class, ['infocom_types' => true]);
    Plugin::registerClass(GlpiPlugin\Medmetriccmms\Contract::class, ['contract_types' => true]);

    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::CSRF_COMPLIANT]['medmetriccmms'] = true;

    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::ADD_CSS]['medmetriccmms'] = 'css/medmetriccmms.css';
    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::ADD_JAVASCRIPT]['medmetriccmms'] = 'js/medmetriccmms.js';

    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::CONFIG_PAGE]['medmetriccmms'] = 'front/config.form.php';

    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::MENU_TOADD]['medmetriccmms'] = [
        'assets' => [
            GlpiPlugin\Medmetriccmms\Equipment::class,
            GlpiPlugin\Medmetriccmms\Department::class,
            GlpiPlugin\Medmetriccmms\Structure::class,
            GlpiPlugin\Medmetriccmms\Inventory::class,
        ],
        'helpdesk' => [
            GlpiPlugin\Medmetriccmms\WorkOrder::class,
            GlpiPlugin\Medmetriccmms\Maintenance::class,
            GlpiPlugin\Medmetriccmms\MaintenancePlan::class,
            GlpiPlugin\Medmetriccmms\Notification::class,
        ],
        'management' => [
            GlpiPlugin\Medmetriccmms\Vendor::class,
            GlpiPlugin\Medmetriccmms\Contract::class,
            GlpiPlugin\Medmetriccmms\Solution::class,
        ],
        'tools' => [
            GlpiPlugin\Medmetriccmms\Report::class,
            GlpiPlugin\Medmetriccmms\Analytics::class,
        ],
    ];

    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::DISPLAY_CENTRAL]['medmetriccmms'] = 'dashboard_widget';

    // Cron tasks are registered in plugin_medmetriccmms_install() (hook.php) and
    // are dispatched from the glpi_crontasks table via Itemtype::cronInfo()/cronName()
    // calls (GLPI 11 has no CRON plugin hook). plugin_init stays side-effect-free
    // so a transient DB issue can never prevent hook.php from loading.

    $PLUGIN_HOOKS[Glpi\Plugin\Hooks::AUTO_ADD_DEFAULT_WHERE]['medmetriccmms'] =
        'plugin_medmetriccmms_addDefaultWhere';
}

/**
 * Get the name and the version of the plugin.
 *
 * @return array
 */
function plugin_version_medmetriccmms() {
    return [
        'name'           => 'MedMetric CMMS',
        'version'        => PLUGIN_MEDMETRICCMMS_VERSION,
        'author'         => 'MedMetric team',
        'license'        => 'GPLv3+',
        'homepage'       => 'https://github.com/medmetric/medmetriccmms',
        'requirements'   => [
            'glpi' => [
                'min' => PLUGIN_MEDMETRICCMMS_MIN_GLPI,
                'dev' => true,
            ],
            'php' => [
                'min' => PLUGIN_MEDMETRICCMMS_MIN_PHP,
            ],
        ],
    ];
}

/**
 * Check prerequisites before install: environment, class loading.
 *
 * @return bool
 */
function plugin_medmetriccmms_check_prerequisites() {
    if (version_compare(PHP_VERSION, PLUGIN_MEDMETRICCMMS_MIN_PHP, '<')) {
        echo 'MedMetric CMMS requires PHP >= ' . PLUGIN_MEDMETRICCMMS_MIN_PHP;
        return false;
    }
    // Classes are loaded lazily by GLPI's plugin autoloader when needed;
    // nothing can be required here that would block installation.
    return true;
}

/**
 * Check configuration process: no blocking config for now.
 *
 * @return bool
 */
function plugin_medmetriccmms_check_config() {
    return true;
}
