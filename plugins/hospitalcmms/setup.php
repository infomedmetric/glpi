<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Computerized Maintenance Management System for Hospitals
 *
 * A GLPI plugin to convert GLPI into a hospital-focused CMMS for managing
 * medical equipment, preventive maintenance, and departmental assets.
 *
 * @copyright 2024 Hospital CMMS Contributors
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Plugin initialization
 */
function plugin_init_hospitalcmms() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['hospitalcmms'] = true;

    // Plugin configuration
    $plugin = new Plugin();
    if ($plugin->isActivated('hospitalcmms')) {
        // Initialize menu modifications
        PluginHospitalcmmsMenu::init();

        // Add menu entries
        $PLUGIN_HOOKS['menu_toadd']['hospitalcmms'] = [
            'admin' => 'PluginHospitalcmmsConfig',
            'assets' => 'PluginHospitalcmmsMedicalEquipment',
            'tools'  => 'PluginHospitalcmmsMaintenanceTask',
        ];

        // Add subscription menu for admins
        if (Session::haveRight('config', UPDATE)) {
            $PLUGIN_HOOKS['menu_toadd']['hospitalcmms']['config'] = 'PluginHospitalcmmsSubscription';
        }

        // Add submenu for department management
        $PLUGIN_HOOKS['submenu_entry']['admin']['options']['hospitalcmms_departments'] = [
            'title'  => __('Department Management'),
            'page'   => '/plugins/hospitalcmms/front/user_department.php',
            'links'  => [
                'add'  => '/plugins/hospitalcmms/front/user_department.php',
            ],
        ];

        // Add central page
        $PLUGIN_HOOKS['change_central_display']['hospitalcmms'] = [
            'PluginHospitalcmmsCentral',
            'showCentral',
        ];

        // Add itemtypes to search
        $PLUGIN_HOOKS['use_item']['hospitalcmms'] = [
            'Computer'          => 'PluginHospitalcmmsComputer',
            'Monitor'           => 'PluginHospitalcmmsMonitor',
            'Printer'           => 'PluginHospitalcmmsPrinter',
            'NetworkEquipment'  => 'PluginHospitalcmmsNetworkEquipment',
            'Peripheral'        => 'PluginHospitalcmmsPeripheral',
            'Phone'             => 'PluginHospitalcmmsPhone',
        ];

        // Register autoloader
        $PLUGIN_HOOKS['autoloader']['hospitalcmms'] = true;
    }
}

/**
 * Plugin configuration (mandatory)
 */
function plugin_version_hospitalcmms() {
    return [
        'name'           => 'Hospital CMMS',
        'version'        => '1.0.0',
        'author'         => 'Hospital CMMS Contributors',
        'license'        => 'GPLv2+',
        'homepage'       => 'https://github.com/hospital-cmms',
        'minGlpiVersion' => '10.0.0',
        'requirements'   => [
            'php' => [
                'min' => '8.1',
            ],
        ],
    ];
}

/**
 * Plugin uninstall
 */
function plugin_hospitalcmms_uninstall() {
    // Clean configuration
    $query = "DELETE FROM `glpi_configs` WHERE `context` = 'plugin:hospitalcmms'";
    $DB = DBmysql::getInstance();
    $DB->query($query);

    return true;
}
