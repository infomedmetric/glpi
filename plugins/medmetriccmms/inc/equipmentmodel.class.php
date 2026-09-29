<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - equipment models
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
 * EquipmentModel: device models grouped by type.
 */
class EquipmentModel extends CommonDropdown
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
        return _n('Equipment model', 'Equipment models', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-device-desktop-analytics';
    }

    /**
     * Additional form fields.
     *
     * @return array
     */
    public function getAdditionalFields() {
        return [
            [
                'name'   => 'plugin_medmetriccmms_equipmenttypes_id',
                'label'  => EquipmentType::getTypeName(1),
                'type'   => 'dropdownValue',
                'list'   => true,
            ],
            [
                'name'   => 'manufacturer',
                'label'  => __('Manufacturer'),
                'type'   => 'text',
                'list'   => true,
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
        $tab[] = ['id' => '10', 'table' => 'glpi_plugin_medmetriccmms_equipmenttypes', 'field' => 'name',
            'name' => EquipmentType::getTypeName(1), 'datatype' => 'dropdown'];
        return $tab;
    }
}
