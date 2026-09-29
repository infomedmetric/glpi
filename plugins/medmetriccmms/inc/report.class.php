<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - exportable reports
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
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Report: saved report definitions and export generation.
 */
class Report extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';

    public const KIND_WORKORDERS  = 1;
    public const KIND_EQUIPMENT   = 2;
    public const KIND_MAINTENANCE = 3;
    public const KIND_INVENTORY   = 4;
    public const KIND_CONTRACTS   = 5;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Report', 'Reports', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-report';
    }

    /**
     * Report kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_WORKORDERS  => WorkOrder::getTypeName(Session::getPluralNumber()),
            self::KIND_EQUIPMENT   => Equipment::getTypeName(Session::getPluralNumber()),
            self::KIND_MAINTENANCE => Maintenance::getTypeName(Session::getPluralNumber()),
            self::KIND_INVENTORY   => Inventory::getTypeName(Session::getPluralNumber()),
            self::KIND_CONTRACTS   => Contract::getTypeName(Session::getPluralNumber()),
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
        $tab[] = ['id' => '2', 'table' => $this->getTable(), 'field' => 'reportkind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '3', 'table' => 'glpi_users', 'field' => 'name',
            'name' => __('User'), 'datatype' => 'dropdown'];
        $tab[] = ['id' => '19', 'table' => $this->getTable(), 'field' => 'date_creation',
            'name' => __('Creation date'), 'datatype' => 'datetime', 'massiveaction' => false];
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
        if ($field === 'reportkind') {
            $kinds = self::getKinds();
            return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? '');
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Build report rows for a kind.
     *
     * @param int   $kind one of KIND_*
     * @param array $params date_from, date_to
     *
     * @return array {headers: string[], rows: array[]}
     */
    public static function buildRows(int $kind, array $params = []): array {
        global $DB;
        $date_from = $params['date_from'] ?? date('Y-m-d', strtotime('-90 days'));
        $date_to = $params['date_to'] ?? date('Y-m-d');

        switch ($kind) {
            case self::KIND_EQUIPMENT:
                return self::rowsFromQuery([
                    'FROM' => Equipment::getTable(),
                    'WHERE' => ['is_deleted' => 0],
                ], [
                    'id' => 'ID', 'name' => __('Name'), 'serial' => __('Serial number'),
                    'status' => __('Status'), 'criticality' => __('Criticality'),
                    'next_maintenance' => __('Next maintenance'),
                ]);

            case self::KIND_MAINTENANCE:
                return self::rowsFromQuery([
                    'FROM' => Maintenance::getTable(),
                    'WHERE' => ['date' => ['BETWEEN', [$date_from, $date_to]]],
                ], [
                    'id' => 'ID', 'name' => __('Name'), 'date' => __('Date'),
                    'maintenancekind' => __('Kind'), 'state' => __('State'), 'duration' => __('Duration (min)'),
                ]);

            case self::KIND_INVENTORY:
                return self::rowsFromQuery([
                    'FROM' => Inventory::getTable(),
                    'WHERE' => ['is_deleted' => 0],
                ], [
                    'id' => 'ID', 'name' => __('Name'), 'ref' => __('Reference'),
                    'inventorykind' => __('Kind'), 'stock_current' => __('In stock'),
                    'stock_min' => __('Minimum stock'), 'price_unit' => __('Unit price'),
                ]);

            case self::KIND_CONTRACTS:
                return self::rowsFromQuery([
                    'FROM' => Contract::getTable(),
                ], [
                    'id' => 'ID', 'name' => __('Name'), 'ref' => __('Reference'),
                    'contractkind' => __('Kind'), 'start_date' => __('Start date'),
                    'end_date' => __('End date'), 'status' => __('Status'),
                ]);

            case self::KIND_WORKORDERS:
            default:
                return self::rowsFromQuery([
                    'FROM' => WorkOrder::getTable(),
                    'WHERE' => ['DATE(date)' => ['BETWEEN', [$date_from, $date_to]]],
                ], [
                    'id' => 'ID', 'code' => __('Code'), 'name' => __('Title'),
                    'workorderkind' => __('Kind'), 'status' => __('Status'), 'priority' => __('Priority'),
                    'date' => __('Opened'), 'due_date' => __('Due date'),
                    'downtime_duration' => __('Downtime (min)'), 'cost_total' => __('Total cost'),
                ]);
        }
    }

    /**
     * Generic row builder with human readable specific values.
     *
     * @param array $query   DB criteria
     * @param array $columns column => label
     *
     * @return array
     */
    protected static function rowsFromQuery(array $query, array $columns): array {
        global $DB;
        $rows = [];
        $iterator = $DB->request($query);
        foreach ($iterator as $raw) {
            $row = [];
            foreach ($columns as $field => $label) {
                $value = $raw[$field] ?? '';
                $row[$label] = is_scalar($value) ? (string) $value : '';
            }
            $rows[] = $row;
        }
        return ['headers' => array_values($columns), 'rows' => $rows];
    }

    /**
     * Stream a CSV export and exit.
     *
     * @param int   $kind   one of KIND_*
     * @param array $params date range
     *
     * @return never
     */
    public static function exportCsv(int $kind, array $params = []): never {
        $data = self::buildRows($kind, $params);
        $filename = sprintf('medmetriccmms-report-%s-%s.csv', (string) array_search($kind, self::getKinds(), true) ?: $kind, date('Ymd-His'));

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, $data['headers'], ';');
        foreach ($data['rows'] as $row) {
            fputcsv($out, $row, ';');
        }
        fclose($out);
        exit;
    }
}
