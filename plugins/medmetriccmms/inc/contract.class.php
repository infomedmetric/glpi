<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - warranty / service contracts
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
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Contract: warranty and service contracts on equipment.
 */
class Contract extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const KIND_WARRANTY       = 1;
    public const KIND_MAINTENANCE    = 2;
    public const KIND_FULL_SERVICE   = 3;
    public const KIND_CALIBRATION    = 4;

    public const STATUS_ACTIVE    = 1;
    public const STATUS_EXPIRED   = 2;
    public const STATUS_CANCELLED = 3;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Contract', 'Contracts', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-file-text';
    }

    /**
     * Kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_WARRANTY     => __('Warranty'),
            self::KIND_MAINTENANCE  => __('Maintenance contract'),
            self::KIND_FULL_SERVICE => __('Full service'),
            self::KIND_CALIBRATION  => __('Calibration contract'),
        ];
    }

    /**
     * Status labels.
     *
     * @return array
     */
    public static function getStatuses(): array {
        return [
            self::STATUS_ACTIVE    => __('Active'),
            self::STATUS_EXPIRED   => __('Expired'),
            self::STATUS_CANCELLED => __('Cancelled'),
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
        $tab[] = ['id' => '3', 'table' => 'glpi_plugin_medmetriccmms_equipments', 'field' => 'name',
            'name' => Equipment::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '4', 'table' => 'glpi_plugin_medmetriccmms_vendors', 'field' => 'name',
            'name' => Vendor::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'contractkind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'start_date',
            'name' => __('Start date'), 'datatype' => 'date'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'end_date',
            'name' => __('End date'), 'datatype' => 'date'];
        $tab[] = ['id' => '8', 'table' => $this->getTable(), 'field' => 'cost_total',
            'name' => __('Total cost'), 'datatype' => 'decimal'];
        $tab[] = ['id' => '9', 'table' => $this->getTable(), 'field' => 'status',
            'name' => __('Status'), 'datatype' => 'specific'];
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
        if ($field === 'contractkind') {
            $kinds = self::getKinds();
            return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? '');
        }
        if ($field === 'status') {
            $statuses = self::getStatuses();
            $status = (int) ($values[$field] ?? 0);
            $class = $status === self::STATUS_ACTIVE ? 'medmetric-badge--done'
                : ($status === self::STATUS_EXPIRED ? 'medmetric-badge--cancelled' : 'medmetric-badge--neutral');
            return sprintf("<span class='medmetric-badge %s'>%s</span>", $class,
                htmlescape($statuses[$status] ?? ''));
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

        echo "<tr class='tab_bg_1'><td>" . htmlescape(Equipment::getTypeName(1)) . "</td><td>";
        Equipment::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipments_id'] ?? 0]);
        echo "</td><td>" . htmlescape(Vendor::getTypeName(1)) . "</td><td>";
        Vendor::dropdown(['value' => $this->fields['plugin_medmetriccmms_vendors_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('contractkind', self::getKinds(), ['value' => $this->fields['contractkind'] ?? self::KIND_MAINTENANCE, 'display' => true]);
        echo "</td><td>" . htmlescape(__('Status')) . "</td><td>";
        Html::select('status', self::getStatuses(), ['value' => $this->fields['status'] ?? self::STATUS_ACTIVE, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Start date')) . "</td><td>";
        Html::showDateField('start_date', ['value' => $this->fields['start_date'] ?? '']);
        echo "</td><td>" . htmlescape(__('End date')) . "</td><td>";
        Html::showDateField('end_date', ['value' => $this->fields['end_date'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Renewal date')) . "</td><td>";
        Html::showDateField('renewal_date', ['value' => $this->fields['renewal_date'] ?? '']);
        echo "</td><td>" . htmlescape(__('Total cost')) . "</td><td>";
        Html::input('cost_total', ['value' => $this->fields['cost_total'] ?? '0.00', 'size' => 12]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Comments')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'comment', 'value' => $this->fields['comment'] ?? '', 'rows' => 3]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Contracts expiring within N days.
     *
     * @param int $days horizon
     *
     * @return array
     */
    public static function getExpiring(int $days = 60): array {
        global $DB;
        $rows = [];
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'status' => self::STATUS_ACTIVE,
                'NOT' => ['end_date' => null],
                'end_date' => ['<=', date('Y-m-d', strtotime("+$days days"))],
            ],
            'ORDER' => ['end_date ASC'],
        ]);
        foreach ($iterator as $row) {
            $rows[] = $row;
        }
        return $rows;
    }
}
