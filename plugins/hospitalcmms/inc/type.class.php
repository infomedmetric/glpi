<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Medical Equipment Type Class
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Medical Equipment Type Class
 *
 * Manages types of medical equipment (e.g., Imaging, Surgical, Diagnostic).
 */
class PluginHospitalcmmsType extends CommonDropdown {

    // From CommonDBTM
    public $dohistory = true;

    public static function getTypeName($nb = 0) {
        return _n('Equipment Type', 'Equipment Types', $nb);
    }

    public function rawSearchOptions() {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '101',
            'table'         => static::getTable(),
            'field'         => 'name',
            'name'          => __('Type Name'),
            'datatype'      => 'string',
        ];

        return $tab;
    }
}
