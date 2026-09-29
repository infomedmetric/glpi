<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - preventive maintenance schedules
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

use CronTask;
use CommonDBTM;
use Html;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * MaintenancePlan: periodic preventive maintenance definition.
 */
class MaintenancePlan extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const UNIT_DAY   = 1;
    public const UNIT_WEEK  = 2;
    public const UNIT_MONTH = 3;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Maintenance plan', 'Maintenance plans', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-calendar-stats';
    }

    /**
     * Periodicity unit labels.
     *
     * @return array
     */
    public static function getUnits(): array {
        return [
            self::UNIT_DAY   => _n('Day', 'Days', Session::getPluralNumber()),
            self::UNIT_WEEK  => _n('Week', 'Weeks', Session::getPluralNumber()),
            self::UNIT_MONTH => _n('Month', 'Months', Session::getPluralNumber()),
        ];
    }

    /**
     * Convert a periodicity + unit to days.
     *
     * @param int $periodicity count
     * @param int $unit       unit constant
     *
     * @return int
     */
    public static function toDays(int $periodicity, int $unit): int {
        return match ($unit) {
            self::UNIT_WEEK  => $periodicity * 7,
            self::UNIT_MONTH => $periodicity * 30,
            default => $periodicity,
        };
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
        $tab[] = ['id' => '3', 'table' => 'glpi_plugin_medmetriccmms_equipmenttypes', 'field' => 'name',
            'name' => EquipmentType::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'periodicity',
            'name' => __('Periodicity'), 'datatype' => 'integer'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'next_creation',
            'name' => __('Next occurrence'), 'datatype' => 'date'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'is_active',
            'name' => __('Active'), 'datatype' => 'bool'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
            'name' => __('Comments'), 'datatype' => 'text'];
        return $tab;
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
        echo "</td><td>" . htmlescape(__('Active')) . "</td><td>";
        Html::select('is_active', ['0' => __('No'), '1' => __('Yes')], ['value' => $this->fields['is_active'] ?? 1, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(Equipment::getTypeName(1)) . "</td><td>";
        Equipment::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipments_id'] ?? 0]);
        echo "</td><td>" . htmlescape(EquipmentType::getTypeName(1)) . "</td><td>";
        EquipmentType::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipmenttypes_id'] ?? 0,
            'toadd' => ['0' => __('All types')]]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Periodicity')) . "</td><td>";
        Html::input('periodicity', ['value' => $this->fields['periodicity'] ?? 30, 'size' => 6]);
        echo "</td><td>" . htmlescape(__('Unit')) . "</td><td>";
        Html::select('periodicity_unit', self::getUnits(), ['value' => $this->fields['periodicity_unit'] ?? self::UNIT_MONTH, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Next occurrence')) . "</td><td>";
        Html::showDateField('next_creation', ['value' => $this->fields['next_creation'] ?? date('Y-m-d', strtotime('+7 days'))]);
        echo "</td><td>" . htmlescape(__('End date')) . "</td><td>";
        Html::showDateField('end_date', ['value' => $this->fields['end_date'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('maintenancekind', Maintenance::getKinds(), ['value' => $this->fields['maintenancekind'] ?? Maintenance::KIND_PREVENTIVE, 'display' => true]);
        echo "</td><td></td><td></td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Checklist')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'checklist', 'value' => $this->fields['checklist'] ?? '', 'rows' => 6]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Cron: generate planned occurrences for plans whose date has arrived.
     *
     * @param CronTask $task cron task
     *
     * @return int volume processed
     */
    public static function cronPlan(CronTask $task): int {
        global $DB;
        $volume = 0;
        $today = date('Y-m-d');

        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => [
                'is_active' => 1,
                'NOT' => ['next_creation' => null],
                'next_creation' => ['<=', $today],
            ],
            'ORDER' => ['next_creation ASC'],
        ]);
        foreach ($iterator as $plan_row) {
            $plan = new self();
            if (!$plan->getFromDB($plan_row['id'])) {
                continue;
            }
            $date = (string) $plan_row['next_creation'];
            $maintenance_id = Maintenance::createFromPlan($plan, $date);
            if ($maintenance_id === false) {
                continue;
            }
            $volume++;

            // Roll forward to the next occurrence
            $days = self::toDays((int) $plan_row['periodicity'], (int) $plan_row['periodicity_unit']);
            $next = date('Y-m-d', strtotime($date . " +$days days"));
            $input = ['id' => (int) $plan_row['id'], 'next_creation' => $next];

            // Stop when the plan has an end date in the past
            $end = $plan_row['end_date'] ?? null;
            if ($end !== null && $end !== '' && $next > $end) {
                $input['is_active'] = 0;
            }
            $plan->update($input);

            // Keep the equipment schedule in sync
            $equipments_id = (int) ($plan_row['plugin_medmetriccmms_equipments_id'] ?? 0);
            if ($equipments_id > 0) {
                Equipment::recordMaintenance($equipments_id, [
                    'next_maintenance' => $next,
                ]);
            }
        }
        $task->addVolume($volume);
        return $volume;
    }

    /**
     * Cron descriptor.
     *
     * @param string $name task name
     *
     * @return array
     */
    public static function cronInfo($name) {
        return [
            'description' => __('Generate planned preventive maintenance occurrences'),
            'parameter'   => __('Maximum age of plans to process in days'),
        ];
    }
}
