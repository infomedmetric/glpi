<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Configuration Class
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Hospital CMMS Configuration
 *
 * Manages plugin settings and menu configuration.
 */
class PluginHospitalcmmsConfig extends CommonGLPI {

    public static function getTypeName($nb = 0) {
        return __('Hospital CMMS');
    }

    /**
     * Get configuration value
     */
    public static function getConfig($key, $default = '') {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['value'],
            'FROM'   => 'glpi_configs',
            'WHERE'  => [
                'context' => 'plugin:hospitalcmms',
                'name'    => $key,
            ],
        ]);

        if (count($iterator)) {
            $data = $iterator->current();
            return $data['value'];
        }

        return $default;
    }

    /**
     * Set configuration value
     */
    public static function setConfig($key, $value) {
        global $DB;

        $existing = self::getConfig($key);

        if ($existing !== '') {
            $DB->update('glpi_configs', [
                'value' => $value,
            ], [
                'context' => 'plugin:hospitalcmms',
                'name'    => $key,
            ]);
        } else {
            $DB->insert('glpi_configs', [
                'context' => 'plugin:hospitalcmms',
                'name'    => $key,
                'value'   => $value,
            ]);
        }
    }

    /**
     * Get menu items for Hospital CMMS
     */
    public static function getMenuItems() {
        return [
            'plugin_hospitalcmms_equipments' => PluginHospitalcmmsMedicalEquipment::class,
            'plugin_hospitalcmms_maintenance' => PluginHospitalcmmsMaintenanceTask::class,
            'plugin_hospitalcmms_departments' => PluginHospitalcmmsCategory::class,
        ];
    }

    /**
     * Check if IT menus should be hidden
     */
    public static function shouldHideITMenus() {
        return self::getConfig('hide_it_menus', '1') === '1';
    }

    /**
     * Get itemtypes to hide from menus
     */
    public static function getHiddenItemtypes() {
        if (!self::shouldHideITMenus()) {
            return [];
        }

        return [
            Computer::class,
            Monitor::class,
            Printer::class,
            NetworkEquipment::class,
            Peripheral::class,
            Phone::class,
            Software::class,
            SoftwareLicense::class,
        ];
    }
}
