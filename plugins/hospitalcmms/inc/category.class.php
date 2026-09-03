<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Department/Category Class
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Hospital Department Category Class
 *
 * Manages hospital departments as categories for medical equipment.
 * Supports hierarchical department structure (e.g., Surgery > Cardiac Surgery).
 */
class PluginHospitalcmmsCategory extends CommonTreeDropdown {

    // From CommonDBTM
    public $dohistory = true;

    public static function getTypeName($nb = 0) {
        return _n('Department', 'Departments', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        if (Session::haveRight(static::$rightname, READ)) {
            return self::createTabEntry(__('Departments'));
        }
        return '';
    }

    public static function displayTabContentForItem(
        CommonGLPI $item,
        $tabnum = 1,
        $withtemplate = 0
    ) {
        $ong = new self();
        $ong->showItems($item);
        return true;
    }

    public function rawSearchOptions() {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '101',
            'table'         => static::getTable(),
            'field'         => 'is_active',
            'name'          => __('Active'),
            'datatype'      => 'bool',
        ];

        return $tab;
    }

    /**
     * Get all active departments
     */
    public static function getActiveDepartments() {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'completename'],
            'FROM'   => static::getTable(),
            'WHERE'  => ['is_active' => 1],
            'ORDER'  => 'name ASC',
        ]);

        return $iterator;
    }
}
