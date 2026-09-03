<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Medical Equipment Model Class
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Medical Equipment Model Class
 *
 * Manages models of medical equipment (e.g., GE Vivid E9, Philips IntelliVue MX800).
 */
class PluginHospitalcmmsModel extends CommonDropdown {

    // From CommonDBTM
    public $dohistory = true;

    public static function getTypeName($nb = 0) {
        return _n('Equipment Model', 'Equipment Models', $nb);
    }

    public function rawSearchOptions() {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '101',
            'table'         => static::getTable(),
            'field'         => 'name',
            'name'          => __('Model Name'),
            'datatype'      => 'string',
        ];

        return $tab;
    }
}
