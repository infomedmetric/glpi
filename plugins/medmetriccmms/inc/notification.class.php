<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - fault reports / alerts
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
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Notification: fault report, alert and reminder feed for the CMMS.
 */
class Notification extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const KIND_FAULT       = 1;
    public const KIND_ALERT       = 2;
    public const KIND_MAINTENANCE_DUE = 3;
    public const KIND_WARRANTY    = 4;
    public const KIND_WORKORDER_OPENED = 5;

    public const STATUS_OPEN   = 1;
    public const STATUS_ACK    = 2;
    public const STATUS_CLOSED = 3;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Notification', 'Notifications', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-bell-ringing';
    }

    /**
     * Kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_FAULT            => __('Fault report'),
            self::KIND_ALERT            => __('Alert'),
            self::KIND_MAINTENANCE_DUE  => __('Maintenance due'),
            self::KIND_WARRANTY         => __('Warranty expiry'),
            self::KIND_WORKORDER_OPENED => __('Work order opened'),
        ];
    }

    /**
     * Status labels.
     *
     * @return array
     */
    public static function getStatuses(): array {
        return [
            self::STATUS_OPEN   => __('Open'),
            self::STATUS_ACK    => __('Acknowledged'),
            self::STATUS_CLOSED => __('Closed'),
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
        $tab[] = ['id' => '2', 'table' => 'glpi_plugin_medmetriccmms_equipments', 'field' => 'name',
            'name' => Equipment::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'notificationkind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'status',
            'name' => __('Status'), 'datatype' => 'specific'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'priority',
            'name' => __('Priority'), 'datatype' => 'integer'];
        $tab[] = ['id' => '6', 'table' => 'glpi_users', 'field' => 'name',
            'name' => __('User'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'content',
            'name' => __('Message'), 'datatype' => 'text'];
        $tab[] = ['id' => '19', 'table' => $this->getTable(), 'field' => 'date_mod',
            'name' => __('Last update'), 'datatype' => 'datetime', 'massiveaction' => false];
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
        if ($field === 'notificationkind') {
            $kinds = self::getKinds();
            return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? '');
        }
        if ($field === 'status') {
            $statuses = self::getStatuses();
            $status = (int) ($values[$field] ?? 0);
            $class = $status === self::STATUS_OPEN ? 'medmetric-badge--cancelled'
                : ($status === self::STATUS_ACK ? 'medmetric-badge--waiting' : 'medmetric-badge--done');
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

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Title')) . "</td><td colspan='3'>";
        Html::input('name', ['value' => $this->fields['name'] ?? '', 'size' => 80]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(Equipment::getTypeName(1)) . "</td><td>";
        Equipment::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipments_id'] ?? 0]);
        echo "</td><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('notificationkind', self::getKinds(), ['value' => $this->fields['notificationkind'] ?? self::KIND_FAULT, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Status')) . "</td><td>";
        Html::select('status', self::getStatuses(), ['value' => $this->fields['status'] ?? self::STATUS_OPEN, 'display' => true]);
        echo "</td><td>" . htmlescape(__('Priority')) . "</td><td>";
        Html::input('priority', ['value' => $this->fields['priority'] ?? 3, 'size' => 4]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Notify user')) . "</td><td>";
        User::dropdown(['name' => 'users_id', 'value' => $this->fields['users_id'] ?? 0]);
        echo "</td><td></td><td></td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Message')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'content', 'value' => $this->fields['content'] ?? '', 'rows' => 5]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Create a notification linked to a work order.
     *
     * @param WorkOrder $work_order work order
     * @param int       $kind       one of KIND_*
     *
     * @return int|false
     */
    public static function createForWorkOrder(WorkOrder $work_order, int $kind = self::KIND_WORKORDER_OPENED) {
        $notification = new self();
        $input = [
            'name' => sprintf('%s: %s', self::getKinds()[$kind] ?? 'Notification', (string) $work_order->fields['name']),
            'content' => Toolbox::truncate((string) ($work_order->fields['content'] ?? ''), 500),
            'plugin_medmetriccmms_equipments_id' => (int) ($work_order->fields['plugin_medmetriccmms_equipments_id'] ?? 0),
            'plugin_medmetriccmms_workorders_id' => (int) $work_order->fields['id'],
            'plugin_medmetriccmms_departments_id' => (int) ($work_order->fields['plugin_medmetriccmms_departments_id'] ?? 0),
            'notificationkind' => $kind,
            'priority' => (int) ($work_order->fields['priority'] ?? 3),
            'status' => self::STATUS_OPEN,
            'users_id' => (int) ($work_order->fields['users_id_tech'] ?? 0),
            'entities_id' => $work_order->fields['entities_id'] ?? 0,
            'is_recursive' => $work_order->fields['is_recursive'] ?? 0,
        ];
        return $notification->add($input);
    }

    /**
     * Cron: raise maintenance-due and warranty alerts.
     *
     * @param CronTask $task cron task
     *
     * @return int volume
     */
    public static function cronAlert(CronTask $task): int {
        global $DB;
        $volume = 0;
        $horizon = (int) Config::get('alert_frequency', 7);
        $today = date('Y-m-d');
        $limit = date('Y-m-d', strtotime("+$horizon days"));

        $dedupe = fn(string $key): bool => self::alreadyAlerted($key);

        // Maintenance due
        foreach (Equipment::getDueForMaintenance($horizon) as $equipment) {
            $key = 'maint-' . $equipment['id'] . '-' . $equipment['next_maintenance'];
            if ($dedupe($key)) {
                continue;
            }
            $notification = new self();
            $notification->add([
                'name' => sprintf(__('Maintenance due: %s'), (string) $equipment['name']),
                'content' => sprintf(
                    __('%s is due for maintenance on %s.'),
                    (string) $equipment['name'],
                    (string) $equipment['next_maintenance']
                ),
                'plugin_medmetriccmms_equipments_id' => (int) $equipment['id'],
                'notificationkind' => self::KIND_MAINTENANCE_DUE,
                'priority' => (int) $equipment['criticality'] >= Equipment::CRITICALITY_HIGH ? 4 : 3,
                'status' => self::STATUS_OPEN,
                'entities_id' => (int) $equipment['entities_id'],
                'is_recursive' => (int) $equipment['is_recursive'],
            ]);
            $volume++;
        }

        // Warranty expiring
        $iterator = $DB->request([
            'FROM'  => Equipment::getTable(),
            'WHERE' => [
                'is_deleted' => 0,
                'NOT' => ['warranty_end' => null],
                'warranty_end' => ['>=', $today],
                ['warranty_end' => ['<=', $limit]],
            ],
        ]);
        foreach ($iterator as $equipment) {
            $key = 'warranty-' . $equipment['id'] . '-' . $equipment['warranty_end'];
            if ($dedupe($key)) {
                continue;
            }
            $notification = new self();
            $notification->add([
                'name' => sprintf(__('Warranty expiring: %s'), (string) $equipment['name']),
                'content' => sprintf(__('Warranty of %s ends on %s.'), (string) $equipment['name'], (string) $equipment['warranty_end']),
                'plugin_medmetriccmms_equipments_id' => (int) $equipment['id'],
                'notificationkind' => self::KIND_WARRANTY,
                'priority' => 3,
                'status' => self::STATUS_OPEN,
                'entities_id' => (int) $equipment['entities_id'],
                'is_recursive' => (int) $equipment['is_recursive'],
            ]);
            $volume++;
        }

        $task->addVolume($volume);
        return $volume;
    }

    /**
     * Simple dedupe for repeated cron alerts.
     *
     * @param string $key dedupe key
     *
     * @return bool
     */
    protected static function alreadyAlerted(string $key): bool {
        global $DB;
        $iterator = $DB->request([
            'COUNT' => 'c',
            'FROM'  => self::getTable(),
            'WHERE' => ['name' => ['LIKE', '%' . $key . '%']],
        ]);
        return count($iterator) && (int) $iterator->current()['c'] > 0;
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
            'description' => __('Raise maintenance and warranty alerts'),
            'parameter'   => __('Alert horizon in days'),
        ];
    }
}
