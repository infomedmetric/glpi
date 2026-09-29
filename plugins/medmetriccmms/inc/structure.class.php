<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - location hierarchy / installation tree
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
 * Structure: installation tree (buildings, floors, rooms) on top of locations.
 */
class Structure extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const KIND_BUILDING = 1;
    public const KIND_FLOOR    = 2;
    public const KIND_ROOM     = 3;
    public const KIND_OTHER    = 4;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Structure', 'Structures', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-building';
    }

    /**
     * Kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_BUILDING => __('Building'),
            self::KIND_FLOOR    => __('Floor'),
            self::KIND_ROOM     => __('Room'),
            self::KIND_OTHER    => __('Other'),
        ];
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
        $tab[] = ['id' => '2', 'table' => 'glpi_locations', 'field' => 'completename',
            'name' => __('Location'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'structurekind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '14', 'table' => $this->getTable(), 'field' => 'level',
            'name' => __('Level'), 'datatype' => 'integer'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
            'name' => __('Comments'), 'datatype' => 'text'];
        return $tab;
    }

    /**
     * Specific display for kind.
     *
     * @param string $field   field
     * @param mixed  $values  values
     * @param array  $options options
     *
     * @return string
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        if ($field === 'structurekind') {
            $kinds = self::getKinds();
            return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? (string) $values[$field]);
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Show the standard GLPI form.
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
        echo "</td><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('structurekind', self::getKinds(), ['value' => $this->fields['structurekind'] ?? self::KIND_BUILDING, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Location')) . "</td><td>";
        Location::dropdown(['value' => $this->fields['locations_id'] ?? 0]);
        echo "</td><td>" . htmlescape(__('Parent structure')) . "</td><td>";
        self::dropdown(['name' => 'structures_id', 'value' => $this->fields['structures_id'] ?? 0,
            'entity' => $_SESSION['glpiactive_entity'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Comments')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'comment', 'value' => $this->fields['comment'] ?? '', 'rows' => 3]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Render the installation tree grouped by kind (analytics/structure page helper).
     *
     * @return void
     */
    public static function showTree(): void {
        global $DB;
        $iterator = $DB->request(['FROM' => self::getTable(), 'ORDER' => ['structurekind', 'name']]);
        $groups = [];
        foreach ($iterator as $row) {
            $groups[(int) $row['structurekind']][] = $row;
        }
        foreach (self::getKinds() as $kind => $label) {
            if (!isset($groups[$kind])) {
                continue;
            }
            echo "<h3 class='medmetric-card__title'>" . htmlescape($label) . "</h3>";
            echo "<ul class='medmetric-list'>";
            foreach ($groups[$kind] as $row) {
                $url = self::getFormURLWithID((int) $row['id']);
                printf(
                    "<li><a href='%s'>%s</a> <span class='medmetric-muted'>%s</span></li>",
                    htmlescape($url),
                    htmlescape((string) $row['name']),
                    htmlescape((string) ($row['comment'] ?? ''))
                );
            }
            echo "</ul>";
        }
    }
}
