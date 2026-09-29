<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - medical equipment / assets
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
use Entity;
use Html;
use Location;
use Manufacturer;
use Session;
use State;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Equipment: medical devices tracked by the CMMS.
 */
class Equipment extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    // Asset behaviour
    public static $forward_entity_to = ['WorkOrder', 'Maintenance', 'MaintenancePlan', 'Notification'];
    public $can_be_translated = false;

    public const STATUS_IN_SERVICE  = 1;
    public const STATUS_OUT_SERVICE = 2;
    public const STATUS_BROKEN      = 3;
    public const STATUS_MAINTENANCE = 4;
    public const STATUS_RETired     = 5;

    public const CRITICALITY_LOW      = 1;
    public const CRITICALITY_MEDIUM   = 2;
    public const CRITICALITY_HIGH     = 3;
    public const CRITICALITY_CRITICAL = 4;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Equipment', 'Equipment', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-heartbeat';
    }

    /**
     * Status labels.
     *
     * @return array
     */
    public static function getStatuses(): array {
        return [
            self::STATUS_IN_SERVICE  => __('In service'),
            self::STATUS_OUT_SERVICE => __('Out of service'),
            self::STATUS_BROKEN      => __('Broken'),
            self::STATUS_MAINTENANCE => __('Under maintenance'),
            self::STATUS_RETired     => __('Retired'),
        ];
    }

    /**
     * Status name.
     *
     * @param int $status status constant
     *
     * @return string
     */
    public static function getStatusName(int $status): string {
        return self::getStatuses()[$status] ?? (string) $status;
    }

    /**
     * Criticality labels.
     *
     * @return array
     */
    public static function getCriticalities(): array {
        return [
            self::CRITICALITY_LOW      => _x('priority', 'Low'),
            self::CRITICALITY_MEDIUM   => __('Medium'),
            self::CRITICALITY_HIGH     => _x('priority', 'High'),
            self::CRITICALITY_CRITICAL => __('Critical'),
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
            $menu['page'] = '/plugins/medmetriccmms/front/equipment.php';
            $menu['icon'] = self::getIcon();
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
            'name' => __('Name'), 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => $this->getTable(), 'field' => 'serial',
            'name' => __('Serial number'), 'datatype' => 'string'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'asset_tag',
            'name' => __('Asset tag'), 'datatype' => 'string'];
        $tab[] = ['id' => '4', 'table' => 'glpi_plugin_medmetriccmms_equipmenttypes', 'field' => 'name',
            'name' => EquipmentType::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '5', 'table' => 'glpi_plugin_medmetriccmms_equipmentmodels', 'field' => 'name',
            'name' => EquipmentModel::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '6', 'table' => 'glpi_plugin_medmetriccmms_departments', 'field' => 'name',
            'name' => Department::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '7', 'table' => 'glpi_locations', 'field' => 'completename',
            'name' => __('Location'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '8', 'table' => $this->getTable(), 'field' => 'status',
            'name' => __('Status'), 'datatype' => 'specific'];
        $tab[] = ['id' => '9', 'table' => $this->getTable(), 'field' => 'criticality',
            'name' => __('Criticality'), 'datatype' => 'specific'];
        $tab[] = ['id' => '10', 'table' => 'glpi_states', 'field' => 'completename',
            'name' => __('Status'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '11', 'table' => 'glpi_manufacturers', 'field' => 'name',
            'name' => Manufacturer::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '12', 'table' => $this->getTable(), 'field' => 'last_maintenance',
            'name' => __('Last maintenance'), 'datatype' => 'date'];
        $tab[] = ['id' => '13', 'table' => $this->getTable(), 'field' => 'next_maintenance',
            'name' => __('Next maintenance'), 'datatype' => 'date'];
        $tab[] = ['id' => '14', 'table' => $this->getTable(), 'field' => 'warranty_end',
            'name' => __('Warranty end'), 'datatype' => 'date'];
        $tab[] = ['id' => '15', 'table' => $this->getTable(), 'field' => 'operating_hours',
            'name' => __('Operating hours'), 'datatype' => 'decimal'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
            'name' => __('Comments'), 'datatype' => 'text'];
        $tab[] = ['id' => '80', 'table' => 'glpi_entities', 'field' => 'completename',
            'name' => Entity::getTypeName(1), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '19', 'table' => $this->getTable(), 'field' => 'date_mod',
            'name' => __('Last update'), 'datatype' => 'datetime', 'massiveaction' => false];
        return $tab;
    }

    /**
     * Specific displays for status/criticality.
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
        if ($field === 'status') {
            $statuses = self::getStatuses();
            $status = (int) ($values[$field] ?? 0);
            $class = $status === self::STATUS_IN_SERVICE ? 'medmetric-badge--done'
                : ($status === self::STATUS_BROKEN ? 'medmetric-badge--cancelled' : 'medmetric-badge--waiting');
            return sprintf(
                "<span class='medmetric-badge %s'>%s</span>",
                $class,
                htmlescape($statuses[$status] ?? (string) $status)
            );
        }
        if ($field === 'criticality') {
            $crits = self::getCriticalities();
            $crit = (int) ($values[$field] ?? 0);
            $class = $crit >= self::CRITICALITY_CRITICAL ? 'medmetric-badge--cancelled'
                : ($crit === self::CRITICALITY_HIGH ? 'medmetric-badge--waiting' : 'medmetric-badge--neutral');
            return sprintf(
                "<span class='medmetric-badge %s'>%s</span>",
                $class,
                htmlescape($crits[$crit] ?? (string) $crit)
            );
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
        echo "</td><td>" . htmlescape(__('Serial number')) . "</td><td>";
        Html::input('serial', ['value' => $this->fields['serial'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Asset tag')) . "</td><td>";
        Html::input('asset_tag', ['value' => $this->fields['asset_tag'] ?? '']);
        echo "</td><td>" . htmlescape(__('Inventory number')) . "</td><td>";
        Html::input('otherserial', ['value' => $this->fields['otherserial'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(EquipmentType::getTypeName(1)) . "</td><td>";
        EquipmentType::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipmenttypes_id'] ?? 0]);
        echo "</td><td>" . htmlescape(EquipmentModel::getTypeName(1)) . "</td><td>";
        EquipmentModel::dropdown(['value' => $this->fields['plugin_medmetriccmms_equipmentmodels_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(Department::getTypeName(1)) . "</td><td>";
        Department::dropdown(['value' => $this->fields['plugin_medmetriccmms_departments_id'] ?? 0]);
        echo "</td><td>" . htmlescape(__('Location')) . "</td><td>";
        Location::dropdown(['value' => $this->fields['locations_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Structure')) . "</td><td>";
        Structure::dropdown(['value' => $this->fields['plugin_medmetriccmms_structures_id'] ?? 0]);
        echo "</td><td>" . htmlescape(__('Manufacturer')) . "</td><td>";
        Manufacturer::dropdown(['value' => $this->fields['manufacturers_id'] ?? 0]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Status')) . "</td><td>";
        Html::select('status', self::getStatuses(), ['value' => $this->fields['status'] ?? self::STATUS_IN_SERVICE, 'display' => true]);
        echo "</td><td>" . htmlescape(__('Criticality')) . "</td><td>";
        Html::select('criticality', self::getCriticalities(), ['value' => $this->fields['criticality'] ?? self::CRITICALITY_MEDIUM, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Technician in charge')) . "</td><td>";
        $item = $this;
        \User::dropdown(['name' => 'users_id_tech', 'value' => $this->fields['users_id_tech'] ?? 0,
            'right' => 'own_ticket']);
        echo "</td><td>" . htmlescape(__('Warranty end')) . "</td><td>";
        Html::showDateField('warranty_end', ['value' => $this->fields['warranty_end'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Commissioning date')) . "</td><td>";
        Html::showDateField('commissioning_date', ['value' => $this->fields['commissioning_date'] ?? '']);
        echo "</td><td>" . htmlescape(__('Next maintenance')) . "</td><td>";
        Html::showDateField('next_maintenance', ['value' => $this->fields['next_maintenance'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Operating hours')) . "</td><td>";
        Html::input('operating_hours', ['value' => $this->fields['operating_hours'] ?? '0', 'size' => 10]);
        echo "</td><td>" . htmlescape(__('Comments')) . "</td><td>";
        Html::textarea(['name' => 'comment', 'value' => $this->fields['comment'] ?? '', 'rows' => 3]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Equipment due for maintenance within N days.
     *
     * @param int $days horizon
     * @param int $only_critical restrict to high criticality
     *
     * @return array
     */
    public static function getDueForMaintenance(int $days = 30, bool $only_critical = false): array {
        global $DB;
        $where = [
            'is_deleted' => 0,
            'NOT' => ['next_maintenance' => null],
            'next_maintenance' => ['<=', date('Y-m-d', strtotime("+$days days"))],
        ];
        if ($only_critical) {
            $where['criticality'] = [self::CRITICALITY_HIGH, self::CRITICALITY_CRITICAL];
        }
        $rows = [];
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => $where,
            'ORDER' => ['next_maintenance ASC'],
        ]);
        foreach ($iterator as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Mark equipment as under maintenance or back in service.
     *
     * @param int $equipments_id equipment id
     * @param int $status        new status
     *
     * @return bool
     */
    public static function setStatus(int $equipments_id, int $status): bool {
        $equipment = new self();
        if (!$equipment->getFromDB($equipments_id)) {
            return false;
        }
        return (bool) $equipment->update([
            'id'     => $equipments_id,
            'status' => $status,
        ]);
    }

    /**
     * Update maintenance bookkeeping after an intervention.
     *
     * @param int   $equipments_id equipment id
     * @param array $values        last_maintenance, next_maintenance
     *
     * @return bool
     */
    public static function recordMaintenance(int $equipments_id, array $values): bool {
        $equipment = new self();
        if (!$equipment->getFromDB($equipments_id)) {
            return false;
        }
        $input = ['id' => $equipments_id];
        if (!empty($values['last_maintenance'])) {
            $input['last_maintenance'] = $values['last_maintenance'];
        }
        if (!empty($values['next_maintenance'])) {
            $input['next_maintenance'] = $values['next_maintenance'];
        }
        return (bool) $equipment->update($input);
    }

    /**
     * Tab on Location form showing related equipment.
     *
     * @param array $params hook params
     *
     * @return void
     */
    public static function showForLocation(array $params): void {
        global $DB;
        $locations_id = (int) ($params['id'] ?? 0);
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['locations_id' => $locations_id, 'is_deleted' => 0],
            'ORDER' => ['name'],
        ]);
        echo "<div class='medmetric-card'><h3>" . htmlescape(self::getTypeName(Session::getPluralNumber())) . "</h3>";
        if (count($iterator) === 0) {
            echo "<p class='medmetric-muted'>" . htmlescape(__('No equipment recorded here.')) . "</p></div>";
            return;
        }
        echo "<table class='tab_cadre_fixehov medmetric-table'><tr><th>" . htmlescape(__('Name')) . "</th><th>"
            . htmlescape(__('Status')) . "</th><th>" . htmlescape(__('Next maintenance')) . "</th></tr>";
        foreach ($iterator as $row) {
            $url = self::getFormURLWithID((int) $row['id']);
            printf(
                "<tr><td><a href='%s'>%s</a></td><td>%s</td><td>%s</td></tr>",
                htmlescape($url),
                htmlescape((string) $row['name']),
                self::getSpecificValueToDisplay('status', $row),
                htmlescape((string) $row['next_maintenance'])
            );
        }
        echo "</table></div>";
    }
}
