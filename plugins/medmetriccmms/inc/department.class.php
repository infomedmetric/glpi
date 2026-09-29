<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - departments / wards / units
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Medmetriccmms;

use CommonDBTM;
use Html;
use Location;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Department: hospital departments, wards and clinical units.
 */
class Department extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;
    public static $forward_entity_to = ['Equipment', 'WorkOrder', 'Notification'];

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Department', 'Departments', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-building-hospital';
   }

    /**
     * Search options for the standard list.
     *
     * @return array
     */
    public function rawSearchOptions() {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => __('Characteristics')];

        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'name',
            'name' => __('Name'), 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => $this->getTable(), 'field' => 'code',
            'name' => __('Code'), 'datatype' => 'string'];
        $tab[] = ['id' => '3', 'table' => 'glpi_locations', 'field' => 'completename',
            'name' => __('Location'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
            'name' => __('Comments'), 'datatype' => 'text'];
        $tab[] = ['id' => '19', 'table' => $this->getTable(), 'field' => 'date_mod',
            'name' => __('Last update'), 'datatype' => 'datetime', 'massiveaction' => false];
        $tab[] = ['id' => '121', 'table' => $this->getTable(), 'field' => 'date_creation',
            'name' => __('Creation date'), 'datatype' => 'datetime', 'massiveaction' => false];
        return $tab;
    }

    /**
     * Show the standard GLPI form (form page calls this).
     *
     * @param int   $ID      item id
     * @param array $options options
     *
     * @return bool
     */
    public function showForm($ID, array $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Name')) . "</td><td>";
        Html::input('name', ['value' => $this->fields['name'] ?? '']);
        echo "</td><td>" . htmlescape(__('Code')) . "</td><td>";
        Html::input('code', ['value' => $this->fields['code'] ?? '', 'size' => 20]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Location')) . "</td><td>";
        Location::dropdown(['value' => $this->fields['locations_id'] ?? 0]);
        echo "</td><td>" . htmlescape(__('Parent department')) . "</td><td>";
        self::dropdown(['name' => 'departments_id', 'value' => $this->fields['departments_id'] ?? 0,
            'entity' => $_SESSION['glpiactive_entity'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Comments')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'comment', 'value' => $this->fields['comment'] ?? '', 'rows' => 3]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Count equipment belonging to this department.
     *
     * @param int $departments_id department id
     *
     * @return int
     */
    public static function countEquipment(int $departments_id): int {
        global $DB;
        $iterator = $DB->request([
            'COUNT' => 'c',
            'FROM'  => Equipment::getTable(),
            'WHERE' => [
                'plugin_medmetriccmms_departments_id' => $departments_id,
                'is_deleted' => 0,
            ],
        ]);
        return count($iterator) ? (int) $iterator->current()['c'] : 0;
    }
}
