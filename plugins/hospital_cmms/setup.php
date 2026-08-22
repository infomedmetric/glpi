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
function plugin_init_hospital_cmms() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['hospital_cmms'] = true;

    // Plugin configuration
    $plugin = new Plugin();
    if ($plugin->isActivated('hospital_cmms')) {
        // Initialize menu modifications
        PluginHospitalCmmsMenu::init();

        // Add menu entries
        $PLUGIN_HOOKS['menu_toadd']['hospital_cmms'] = [
            'admin' => 'PluginHospitalCmmsConfig',
            'assets' => 'PluginHospitalCmmsMedicalEquipment',
            'tools'  => 'PluginHospitalCmmsMaintenanceTask',
        ];

        // Add submenu for department management
        $PLUGIN_HOOKS['submenu_entry']['admin']['options']['hospital_cmms_departments'] = [
            'title'  => __('Department Management'),
            'page'   => '/plugins/hospital_cmms/front/user_department.php',
            'links'  => [
                'add'  => '/plugins/hospital_cmms/front/user_department.php',
            ],
        ];

        // Add central page
        $PLUGIN_HOOKS['change_central_display']['hospital_cmms'] = [
            'PluginHospitalCmmsCentral',
            'showCentral',
        ];

        // Add itemtypes to search
        $PLUGIN_HOOKS['use_item']['hospital_cmms'] = [
            'Computer'          => 'PluginHospitalCmmsComputer',
            'Monitor'           => 'PluginHospitalCmmsMonitor',
            'Printer'           => 'PluginHospitalCmmsPrinter',
            'NetworkEquipment'  => 'PluginHospitalCmmsNetworkEquipment',
            'Peripheral'        => 'PluginHospitalCmmsPeripheral',
            'Phone'             => 'PluginHospitalCmmsPhone',
        ];

        // Register autoloader
        $PLUGIN_HOOKS['autoloader']['hospital_cmms'] = true;
    }
}

/**
 * Plugin configuration (mandatory)
 */
function plugin_version_hospital_cmms() {
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
function plugin_hospital_cmms_uninstall() {
    // Clean configuration
    $query = "DELETE FROM `glpi_configs` WHERE `context` = 'plugin:hospital_cmms'";
    $DB = DBmysql::getInstance();
    $DB->query($query);

    return true;
}
