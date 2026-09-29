<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - work orders (corrective + preventive)
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
use Glpi\Event;
use Html;
use Session;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * WorkOrder: corrective and preventive jobs with a full lifecycle.
 */
class WorkOrder extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const KIND_CORRECTIVE = 1;
    public const KIND_PREVENTIVE = 2;
    public const KIND_CALIBRATION = 3;
    public const KIND_IMPROVEMENT = 4;

    public const STATUS_NEW           = 1;
    public const STATUS_ASSIGNED      = 2;
    public const STATUS_IN_PROGRESS   = 3;
    public const STATUS_WAITING_PARTS = 4;
    public const STATUS_DONE          = 5;
    public const STATUS_CLOSED        = 6;
    public const STATUS_CANCELLED     = 7;

    public const TECH_INTERNAL = 0;
    public const TECH_VENDOR   = 1;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Work order', 'Work orders', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-clipboard-list';
    }

    /**
     * Kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_CORRECTIVE  => __('Corrective'),
            self::KIND_PREVENTIVE  => __('Preventive'),
            self::KIND_CALIBRATION => __('Calibration'),
            self::KIND_IMPROVEMENT => __('Improvement'),
        ];
    }

    /**
     * Status labels.
     *
     * @return array
     */
    public static function getStatuses(): array {
        return [
            self::STATUS_NEW           => __('New'),
            self::STATUS_ASSIGNED      => __('Assigned'),
            self::STATUS_IN_PROGRESS   => __('In progress'),
            self::STATUS_WAITING_PARTS => __('Waiting parts'),
            self::STATUS_DONE          => __('Done'),
            self::STATUS_CLOSED        => __('Closed'),
            self::STATUS_CANCELLED     => __('Cancelled'),
        ];
    }

    /**
     * Menu content.
     *
     * @return array|false
     */
    public static function getMenuContent() {
        $menu = parent::getMenuContent();
        if ($menu !== false) {
            $menu['title'] = self::getTypeName(Session::getPluralNumber());
            $menu['page'] = '/plugins/medmetriccmms/front/workorder.php';
            $menu['icon'] = self::getIcon();
            $menu['options'] = [
                'maintenance' => [
                    'title' => Maintenance::getTypeName(Session::getPluralNumber()),
                    'page'  => '/plugins/medmetriccmms/front/maintenance.php',
                    'icon'  => Maintenance::getIcon(),
                ],
                'maintenanceplan' => [
                    'title' => MaintenancePlan::getTypeName(Session::getPluralNumber()),
                    'page'  => '/plugins/medmetriccmms/front/maintenanceplan.php',
                    'icon'  => MaintenancePlan::getIcon(),
                ],
                'notification' => [
                    'title' => Notification::getTypeName(Session::getPluralNumber()),
                    'page'  => '/plugins/medmetriccmms/front/notification.php',
                    'icon'  => Notification::getIcon(),
                ],
            ];
        }
        return $menu;
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
        $tab[] = ['id' => '2', 'table' => $this->getTable(), 'field' => 'code',
            'name' => __('Code'), 'datatype' => 'string'];
        $tab[] = ['id' => '3', 'table' => 'glpi_plugin_medmetriccmms_equipments', 'field' => 'name',
            'name' => Equipment::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'workorderkind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'status',
            'name' => __('Status'), 'datatype' => 'specific'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'priority',
            'name' => __('Priority'), 'datatype' => 'specific'];
        $tab[] = ['id' => '7', 'table' => 'glpi_users', 'field' => 'name',
            'name' => __('Technician'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '8', 'table' => 'glpi_plugin_medmetriccmms_departments', 'field' => 'name',
            'name' => Department::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '9', 'table' => $this->getTable(), 'field' => 'date',
            'name' => __('Opened'), 'datatype' => 'datetime'];
        $tab[] = ['id' => '10', 'table' => $this->getTable(), 'field' => 'due_date',
            'name' => __('Due date'), 'datatype' => 'date'];
        $tab[] = ['id' => '11', 'table' => $this->getTable(), 'field' => 'downtime_duration',
            'name' => __('Downtime (min)'), 'datatype' => 'integer'];
        $tab[] = ['id' => '12', 'table' => $this->getTable(), 'field' => 'cost_total',
            'name' => __('Total cost'), 'datatype' => 'decimal'];
        $tab[] = ['id' => '13', 'table' => 'glpi_plugin_medmetriccmms_vendors', 'field' => 'name',
            'name' => Vendor::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'content',
            'name' => __('Description'), 'datatype' => 'text'];
        $tab[] = ['id' => '19', 'table' => $this->getTable(), 'field' => 'date_mod',
            'name' => __('Last update'), 'datatype' => 'datetime', 'massiveaction' => false];
        return $tab;
    }

    /**
     * Specific displays for kind/status/priority.
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
        switch ($field) {
            case 'workorderkind':
                $kinds = self::getKinds();
                return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? '');
            case 'status':
                $status = (int) ($values[$field] ?? 0);
                return sprintf(
                    "<span class='medmetric-badge %s'>%s</span>",
                    htmlescape(Toolbox::statusBadgeClass($status)),
                    htmlescape(self::getStatuses()[$status] ?? (string) $status)
                );
            case 'priority':
                $priorities = [1 => __('Very low'), 2 => __('Low'), 3 => __('Medium'), 4 => __('High'), 5 => __('Very high')];
                $priority = (int) ($values[$field] ?? 3);
                $class = $priority >= 4 ? 'medmetric-badge--cancelled' : ($priority <= 2 ? 'medmetric-badge--neutral' : 'medmetric-badge--progress');
                return sprintf("<span class='medmetric-badge %s'>%s</span>", $class,
                    htmlescape($priorities[$priority] ?? (string) $priority));
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

        echo "<tr class='tab_bg_1'><td>" . htmlescape(Equipment::getTypeName(1)) . "</td><td>";
        Equipment::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipments_id'] ?? 0]);
        echo "</td><td>" . htmlescape(Department::getTypeName(1)) . "</td><td>";
        Department::dropdown(['value' => $this->fields['plugin_medmetriccmms_departments_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('workorderkind', self::getKinds(), ['value' => $this->fields['workorderkind'] ?? self::KIND_CORRECTIVE, 'display' => true]);
        echo "</td><td>" . htmlescape(__('Priority')) . "</td><td>";
        $priorities = [1 => __('Very low'), 2 => __('Low'), 3 => __('Medium'), 4 => __('High'), 5 => __('Very high')];
        Html::select('priority', $priorities, ['value' => $this->fields['priority'] ?? 3, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Status')) . "</td><td>";
        Html::select('status', self::getStatuses(), ['value' => $this->fields['status'] ?? self::STATUS_NEW, 'display' => true]);
        echo "</td><td>" . htmlescape(__('Technician type')) . "</td><td>";
        Html::select('technician_kind', [self::TECH_INTERNAL => __('Internal staff'), self::TECH_VENDOR => __('External vendor')],
            ['value' => $this->fields['technician_kind'] ?? self::TECH_INTERNAL, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Technician')) . "</td><td>";
        User::dropdown(['name' => 'users_id_tech', 'value' => $this->fields['users_id_tech'] ?? 0,
            'right' => 'own_ticket']);
        echo "</td><td>" . htmlescape(Vendor::getTypeName(1)) . "</td><td>";
        Vendor::dropdown(['value' => $this->fields['plugin_medmetriccmms_vendors_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Due date')) . "</td><td>";
        Html::showDateField('due_date', ['value' => $this->fields['due_date'] ?? '']);
        echo "</td><td>" . htmlescape(__('Downtime (min)')) . "</td><td>";
        Html::input('downtime_duration', ['value' => $this->fields['downtime_duration'] ?? 0, 'size' => 8]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Description')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'content', 'value' => $this->fields['content'] ?? '', 'rows' => 5]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Solution')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'solution_description', 'value' => $this->fields['solution_description'] ?? '', 'rows' => 5]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Auto code and timestamps on add.
     *
     * @param array $input input
     *
     * @return array
     */
    public function prepareInputForAdd($input) {
        $input = parent::prepareInputForAdd($input);
        if ($input !== false && (!isset($input['code']) || $input['code'] === '')) {
            $input['code'] = Toolbox::generateCode('WO', self::getTable());
        }
        if (is_array($input) && !isset($input['date'])) {
            $input['date'] = $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s');
        }
        return $input;
    }

    /**
     * Post add: notify assigned department.
     *
     * @return void
     */
    public function post_addItem() {
        global $CFG_GLPI;
        parent::post_addItem();

        $equipments_id = (int) ($this->fields['plugin_medmetriccmms_equipments_id'] ?? 0);
        if ($equipments_id > 0 && (int) ($this->fields['workorderkind'] ?? 0) === self::KIND_CORRECTIVE) {
            Equipment::setStatus($equipments_id, Equipment::STATUS_BROKEN);
        }
        Notification::createForWorkOrder($this, Notification::KIND_WORKORDER_OPENED);
    }

    /**
     * Post update: handle completion side effects.
     *
     * @param int|bool $history keep history
     *
     * @return void
     */
    public function post_updateItem($history = true) {
        parent::post_updateItem($history);

        $status = (int) ($this->fields['status'] ?? 0);
        $equipments_id = (int) ($this->fields['plugin_medmetriccmms_equipments_id'] ?? 0);

        if ($status === self::STATUS_DONE && $equipments_id > 0) {
            Equipment::setStatus($equipments_id, Equipment::STATUS_IN_SERVICE);
            Equipment::recordMaintenance($equipments_id, [
                'last_maintenance' => date('Y-m-d'),
            ]);
        }
    }

    /**
     * Parts consumption lines for this work order.
     *
     * @return array
     */
    public function getParts(): array {
        global $DB;
        $rows = [];
        $iterator = $DB->request([
            'SELECT' => ['wi.id', 'wi.quantity', 'wi.price_unit', 'i.name', 'i.ref'],
            'FROM'   => 'glpi_plugin_medmetriccmms_workorderinventories AS wi',
            'LEFT JOIN' => [
                'glpi_plugin_medmetriccmms_inventories AS i' => ['ON' => ['wi' => 'plugin_medmetriccmms_inventories_id', 'i' => 'id']],
            ],
            'WHERE'  => ['wi.plugin_medmetriccmms_workorders_id' => (int) $this->fields['id']],
        ]);
        foreach ($iterator as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Total cost including parts.
     *
     * @return float
     */
    public function computeTotalCost(): float {
        $total = (float) ($this->fields['cost_total'] ?? 0);
        foreach ($this->getParts() as $part) {
            $total += ((int) $part['quantity']) * ((float) $part['price_unit']);
        }
        return $total;
    }
}
