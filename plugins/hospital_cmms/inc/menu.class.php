<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Menu Configuration
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Hospital CMMS Menu Configuration
 *
 * Handles menu customization to hide IT-specific items and show medical equipment.
 */
class PluginHospitalCmmsMenu extends CommonGLPI {

    /**
     * Initialize menu modifications
     */
    public static function init() {
        global $CFG_GLPI, $PLUGIN_HOOKS;

        // Register menu hooks
        $PLUGIN_HOOKS['menu_toadd']['hospital_cmms'] = [
            'assets' => 'PluginHospitalCmmsMedicalEquipment',
            'tools'  => 'PluginHospitalCmmsMaintenanceTask',
            'admin'  => 'PluginHospitalCmmsConfig',
        ];

        // Hide IT-specific menus if configured
        if (PluginHospitalCmmsConfig::shouldHideITMenus()) {
            self::hideITMenus();
        }
    }

    /**
     * Hide IT-specific menus
     */
    private static function hideITMenus() {
        global $CFG_GLPI;

        // Remove IT-specific itemtypes from various arrays
        $itTypes = [
            Computer::class,
            Monitor::class,
            Printer::class,
            NetworkEquipment::class,
            Peripheral::class,
            Phone::class,
            Software::class,
            SoftwareLicense::class,
        ];

        foreach ($CFG_GLPI as $key => &$value) {
            if (is_array($value)) {
                foreach ($itTypes as $itType) {
                    if (($index = array_search($itType, $value)) !== false) {
                        unset($value[$index]);
                        $value = array_values($value);
                    }
                }
            }
        }
        unset($value);
    }

    /**
     * Add custom menu entries
     */
    public static function addMenuEntries() {
        global $CFG_GLPI;

        // Add medical equipment to asset types
        if (!isset($CFG_GLPI['asset_types'])) {
            $CFG_GLPI['asset_types'] = [];
        }
        $CFG_GLPI['asset_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to document types
        if (!isset($CFG_GLPI['document_types'])) {
            $CFG_GLPI['document_types'] = [];
        }
        $CFG_GLPI['document_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to ticket types
        if (!isset($CFG_GLPI['ticket_types'])) {
            $CFG_GLPI['ticket_types'] = [];
        }
        $CFG_GLPI['ticket_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to contract types
        if (!isset($CFG_GLPI['contract_types'])) {
            $CFG_GLPI['contract_types'] = [];
        }
        $CFG_GLPI['contract_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to infocom types
        if (!isset($CFG_GLPI['infocom_types'])) {
            $CFG_GLPI['infocom_types'] = [];
        }
        $CFG_GLPI['infocom_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to link types
        if (!isset($CFG_GLPI['link_types'])) {
            $CFG_GLPI['link_types'] = [];
        }
        $CFG_GLPI['link_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to kb types
        if (!isset($CFG_GLPI['kb_types'])) {
            $CFG_GLPI['kb_types'] = [];
        }
        $CFG_GLPI['kb_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to location types
        if (!isset($CFG_GLPI['location_types'])) {
            $CFG_GLPI['location_types'] = [];
        }
        $CFG_GLPI['location_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to state types
        if (!isset($CFG_GLPI['state_types'])) {
            $CFG_GLPI['state_types'] = [];
        }
        $CFG_GLPI['state_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to report types
        if (!isset($CFG_GLPI['report_types'])) {
            $CFG_GLPI['report_types'] = [];
        }
        $CFG_GLPI['report_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to globalsearch types
        if (!isset($CFG_GLPI['globalsearch_types'])) {
            $CFG_GLPI['globalsearch_types'] = [];
        }
        $CFG_GLPI['globalsearch_types'][] = PluginHospitalCmmsMedicalEquipment::class;

        // Add medical equipment to lock_lockable_objects
        if (!isset($CFG_GLPI['lock_lockable_objects'])) {
            $CFG_GLPI['lock_lockable_objects'] = [];
        }
        $CFG_GLPI['lock_lockable_objects'][] = PluginHospitalCmmsMedicalEquipment::class;
    }

    /**
     * Get menu icon
     */
    public static function getIcon() {
        return "ti ti-heartbeat";
    }

    /**
     * Get menu name
     */
    public static function getTypeName($nb = 0) {
        return __('Hospital CMMS');
    }
}
