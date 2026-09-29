<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - spare parts, consumables, stock
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
 * Inventory: spare parts and consumables with stock levels.
 */
class Inventory extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const KIND_SPARE     = 1;
    public const KIND_CONSUMABLE = 2;
    public const KIND_TOOL      = 3;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Spare part', 'Spare parts', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-package';
    }

    /**
     * Kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_SPARE      => __('Spare part'),
            self::KIND_CONSUMABLE => __('Consumable'),
            self::KIND_TOOL       => __('Tool'),
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
        $tab[] = ['id' => '2', 'table' => $this->getTable(), 'field' => 'ref',
            'name' => __('Reference'), 'datatype' => 'string'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'barcode',
            'name' => __('Barcode'), 'datatype' => 'string'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'inventorykind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'stock_current',
            'name' => __('In stock'), 'datatype' => 'integer'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'stock_min',
            'name' => __('Minimum stock'), 'datatype' => 'integer'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'stock_ordered',
            'name' => __('Ordered'), 'datatype' => 'integer'];
        $tab[] = ['id' => '8', 'table' => 'glpi_locations', 'field' => 'completename',
            'name' => __('Storage location'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '9', 'table' => 'glpi_plugin_medmetriccmms_vendors', 'field' => 'name',
            'name' => Vendor::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '10', 'table' => $this->getTable(), 'field' => 'price_unit',
            'name' => __('Unit price'), 'datatype' => 'decimal'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
            'name' => __('Comments'), 'datatype' => 'text'];
        return $tab;
    }

    /**
     * Specific displays.
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
        if ($field === 'inventorykind') {
            $kinds = self::getKinds();
            return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? '');
        }
        if ($field === 'stock_current') {
            $current = (int) ($values['stock_current'] ?? 0);
            $min = (int) ($values['stock_min'] ?? 0);
            $class = $min > 0 && $current <= $min ? 'medmetric-badge--cancelled' : 'medmetric-badge--done';
            return sprintf("<span class='medmetric-badge %s'>%d</span>", $class, $current);
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
        echo "</td><td>" . htmlescape(__('Reference')) . "</td><td>";
        Html::input('ref', ['value' => $this->fields['ref'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Barcode')) . "</td><td>";
        Html::input('barcode', ['value' => $this->fields['barcode'] ?? '']);
        echo "</td><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('inventorykind', self::getKinds(), ['value' => $this->fields['inventorykind'] ?? self::KIND_SPARE, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('In stock')) . "</td><td>";
        Html::input('stock_current', ['value' => $this->fields['stock_current'] ?? 0, 'size' => 8]);
        echo "</td><td>" . htmlescape(__('Minimum stock')) . "</td><td>";
        Html::input('stock_min', ['value' => $this->fields['stock_min'] ?? 0, 'size' => 8]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Ordered')) . "</td><td>";
        Html::input('stock_ordered', ['value' => $this->fields['stock_ordered'] ?? 0, 'size' => 8]);
        echo "</td><td>" . htmlescape(__('Unit price')) . "</td><td>";
        Html::input('price_unit', ['value' => $this->fields['price_unit'] ?? '0.00', 'size' => 10]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Storage location')) . "</td><td>";
        Location::dropdown(['value' => $this->fields['locations_id'] ?? 0]);
        echo "</td><td>" . htmlescape(Vendor::getTypeName(1)) . "</td><td>";
        Vendor::dropdown(['value' => $this->fields['plugin_medmetriccmms_vendors_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Comments')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'comment', 'value' => $this->fields['comment'] ?? '', 'rows' => 3]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Consume stock for a work order and record the line.
     *
     * @param int $workorders_id work order
     * @param int $inventories_id spare part
     * @param int $quantity quantity used
     *
     * @return bool
     */
    public static function consumeForWorkOrder(int $workorders_id, int $inventories_id, int $quantity): bool {
        global $DB;
        if ($quantity <= 0) {
            return false;
        }
        $part = new self();
        if (!$part->getFromDB($inventories_id)) {
            return false;
        }
        $new_stock = max(0, (int) $part->fields['stock_current'] - $quantity);
        $ok = (bool) $part->update(['id' => $inventories_id, 'stock_current' => $new_stock]);
        if ($ok) {
            $DB->insertOrDie('glpi_plugin_medmetriccmms_workorderinventories', [
                'plugin_medmetriccmms_workorders_id' => $workorders_id,
                'plugin_medmetriccmms_inventories_id' => $inventories_id,
                'quantity' => $quantity,
                'price_unit' => (float) $part->fields['price_unit'],
                'date_creation' => date('Y-m-d H:i:s'),
            ], 'MedMetric CMMS work order part insert failed');
        }
        return $ok;
    }

    /**
     * Items below minimum stock.
     *
     * @return array
     */
    public static function getLowStock(): array {
        global $DB;
        $rows = [];
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'is_deleted' => 0,
                'NOT' => ['stock_min' => 0],
            ],
            'ORDER' => ['stock_current ASC'],
        ]);
        foreach ($iterator as $row) {
            if ((int) $row['stock_current'] <= (int) $row['stock_min']) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
}
