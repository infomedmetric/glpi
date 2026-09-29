<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - solutions / knowledge base
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
 * Solution: reusable knowledge base entries built from resolved work orders.
 */
class Solution extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const KIND_PROCEDURE  = 1;
    public const KIND_TROUBLESHOOT = 2;
    public const KIND_FAQ        = 3;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Solution', 'Solutions', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-bulb';
    }

    /**
     * Kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_PROCEDURE    => __('Procedure'),
            self::KIND_TROUBLESHOOT => __('Troubleshooting'),
            self::KIND_FAQ          => __('FAQ'),
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
            'name' => __('Title'), 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_medmetriccmms_equipmenttypes', 'field' => 'name',
            'name' => EquipmentType::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'solutionkind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'is_validated',
            'name' => __('Validated'), 'datatype' => 'bool'];
        $tab[] = ['id' => '5', 'table' => 'glpi_users', 'field' => 'name',
            'name' => __('Author'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'content',
            'name' => __('Content'), 'datatype' => 'text'];
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
        if ($field === 'solutionkind') {
            $kinds = self::getKinds();
            return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? '');
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

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Title')) . "</td><td colspan='3'>";
        Html::input('name', ['value' => $this->fields['name'] ?? '', 'size' => 80]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(EquipmentType::getTypeName(1)) . "</td><td>";
        EquipmentType::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipmenttypes_id'] ?? 0,
            'toadd' => ['0' => __('Any type')]]);
        echo "</td><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('solutionkind', self::getKinds(), ['value' => $this->fields['solutionkind'] ?? self::KIND_TROUBLESHOOT, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Validated')) . "</td><td>";
        Html::select('is_validated', ['0' => __('No'), '1' => __('Yes')], ['value' => $this->fields['is_validated'] ?? 0, 'display' => true]);
        echo "</td><td>" . htmlescape(__('Source work order')) . "</td><td>";
        WorkOrder::dropdown(['value' => $this->fields['plugin_medmetriccmms_workorders_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Content')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'content', 'value' => $this->fields['content'] ?? '', 'rows' => 10]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Create a knowledge base entry from a closed work order.
     *
     * @param WorkOrder $work_order resolved work order
     *
     * @return int|false
     */
    public static function createFromWorkOrder(WorkOrder $work_order) {
        $content = trim((string) ($work_order->fields['solution_description'] ?? ''));
        if ($content === '') {
            return false;
        }
        $equipment = new Equipment();
        $types_id = 0;
        if ($equipment->getFromDB((int) ($work_order->fields['plugin_medmetriccmms_equipments_id'] ?? 0))) {
            $types_id = (int) ($equipment->fields['plugin_medmetriccmms_equipmenttypes_id'] ?? 0);
        }
        $solution = new self();
        return $solution->add([
            'name' => (string) $work_order->fields['name'],
            'content' => $content,
            'plugin_medmetriccmms_equipmenttypes_id' => $types_id,
            'plugin_medmetriccmms_workorders_id' => (int) $work_order->fields['id'],
            'solutionkind' => self::KIND_TROUBLESHOOT,
            'users_id' => $_SESSION['glpiID'] ?? 0,
            'is_validated' => 0,
            'entities_id' => $work_order->fields['entities_id'] ?? 0,
            'is_recursive' => $work_order->fields['is_recursive'] ?? 0,
        ]);
    }

    /**
     * Simple keyword search used by the ajax endpoint.
     *
     * @param string $term search term
     * @param int    $limit max rows
     *
     * @return array
     */
    public static function search(string $term, int $limit = 10): array {
        global $DB;
        $rows = [];
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'OR' => [
                    ['name' => ['LIKE', "%$term%"]],
                    ['content' => ['LIKE', "%$term%"]],
                ],
            ],
            'ORDER'  => ['is_validated DESC', 'date_mod DESC'],
            'LIMIT'  => $limit,
        ]);
        foreach ($iterator as $row) {
            $rows[] = $row;
        }
        return $rows;
    }
}
