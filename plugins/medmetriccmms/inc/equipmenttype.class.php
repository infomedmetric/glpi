<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - equipment types
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

use CommonDropdown;
use Html;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * EquipmentType: categories of medical devices.
 */
class EquipmentType extends CommonDropdown
{
    public static $rightname = 'plugin_medmetriccmms';

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Equipment type', 'Equipment types', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-heart-rate-monitor';
    }

    /**
     * Additional form field: icon name.
     *
     * @return array
     */
    public function getAdditionalFields() {
        return [
            [
                'name'  => 'icon',
                'label' => __('Icon'),
                'type'  => 'text',
                'list'  => true,
            ],
        ];
    }

    /**
     * Search options for the standard list.
     *
     * @return array
     */
    public function rawSearchOptions() {
        $tab = parent::rawSearchOptions();
        $tab[] = ['id' => '10', 'table' => $this->getTable(), 'field' => 'icon',
            'name' => __('Icon'), 'datatype' => 'string'];
        return $tab;
    }

    /**
     * Dependencies warning before delete.
     *
     * @return array
     */
    public function getDropdownTables() {
        return ['glpi_plugin_medmetriccmms_equipments'];
    }
}
