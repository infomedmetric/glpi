<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - maintenance tasks / interventions
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
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Maintenance: a preventive or corrective intervention record on equipment.
 */
class Maintenance extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const KIND_PREVENTIVE = 1;
    public const KIND_CORRECTIVE = 2;
    public const KIND_CALIBRATION = 3;
    public const KIND_INSPECTION = 4;

    public const STATE_PLANNED   = 1;
    public const STATE_IN_PROGRESS = 2;
    public const STATE_DONE      = 3;
    public const STATE_CANCELLED = 4;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Maintenance', 'Maintenances', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-tool';
    }

    /**
     * Kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_PREVENTIVE  => __('Preventive'),
            self::KIND_CORRECTIVE  => __('Corrective'),
            self::KIND_CALIBRATION => __('Calibration'),
            self::KIND_INSPECTION  => __('Inspection'),
        ];
    }

    /**
     * State labels.
     *
     * @return array
     */
    public static function getStates(): array {
        return [
            self::STATE_PLANNED     => __('Planned'),
            self::STATE_IN_PROGRESS => __('In progress'),
            self::STATE_DONE        => __('Done'),
            self::STATE_CANCELLED   => __('Cancelled'),
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
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_medmetriccmms_equipments', 'field' => 'name',
            'name' => Equipment::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'maintenancekind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'state',
            'name' => __('State'), 'datatype' => 'specific'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'date',
            'name' => __('Date'), 'datatype' => 'date'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'duration',
            'name' => __('Duration (min)'), 'datatype' => 'integer'];
        $tab[] = ['id' => '7', 'table' => 'glpi_users', 'field' => 'name',
            'name' => __('Technician'), 'datatype' => 'dropdown'];
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
        if ($field === 'maintenancekind') {
            $kinds = self::getKinds();
            return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? '');
        }
        if ($field === 'state') {
            $states = self::getStates();
            $state = (int) ($values[$field] ?? 0);
            $class = $state === self::STATE_DONE ? 'medmetric-badge--done'
                : ($state === self::STATE_CANCELLED ? 'medmetric-badge--cancelled' : 'medmetric-badge--progress');
            return sprintf("<span class='medmetric-badge %s'>%s</span>", $class,
                htmlescape($states[$state] ?? ''));
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
        echo "</td><td>" . htmlescape(__('Date')) . "</td><td>";
        Html::showDateField('date', ['value' => $this->fields['date'] ?? date('Y-m-d')]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(Equipment::getTypeName(1)) . "</td><td>";
        Equipment::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipments_id'] ?? 0]);
        echo "</td><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('maintenancekind', self::getKinds(), ['value' => $this->fields['maintenancekind'] ?? self::KIND_PREVENTIVE, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Technician')) . "</td><td>";
        User::dropdown(['name' => 'users_id_tech', 'value' => $this->fields['users_id_tech'] ?? 0,
            'right' => 'own_ticket']);
        echo "</td><td>" . htmlescape(__('State')) . "</td><td>";
        Html::select('state', self::getStates(), ['value' => $this->fields['state'] ?? self::STATE_PLANNED, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Duration (min)')) . "</td><td>";
        Html::input('duration', ['value' => $this->fields['duration'] ?? 0, 'size' => 8]);
        echo "</td><td>" . htmlescape(__('Linked plan')) . "</td><td>";
        MaintenancePlan::dropdown(['value' => $this->fields['plugin_medmetriccmms_maintenanceplans_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Checklist / notes')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'checklist', 'value' => $this->fields['checklist'] ?? '', 'rows' => 6]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Create a maintenance record from a plan occurrence and roll the plan forward.
     *
     * @param MaintenancePlan $plan  active plan
     * @param string          $date  intervention date
     *
     * @return int|false new maintenance id
     */
    public static function createFromPlan(MaintenancePlan $plan, string $date) {
        $maintenance = new self();
        $input = [
            'name' => sprintf('%s - %s', $plan->fields['name'] ?? __('Plan'), $date),
            'plugin_medmetriccmms_equipments_id' => (int) ($plan->fields['plugin_medmetriccmms_equipments_id'] ?? 0),
            'plugin_medmetriccmms_maintenanceplans_id' => (int) $plan->fields['id'],
            'maintenancekind' => (int) ($plan->fields['maintenancekind'] ?? self::KIND_PREVENTIVE),
            'checklist' => $plan->fields['checklist'] ?? '',
            'date' => $date,
            'state' => self::STATE_PLANNED,
            'entities_id' => $plan->fields['entities_id'] ?? 0,
            'is_recursive' => $plan->fields['is_recursive'] ?? 0,
        ];
        $new_id = $maintenance->add($input);
        return $new_id > 0 ? $new_id : false;
    }
}
