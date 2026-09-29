<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - KPIs, dashboards, reports
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

use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Analytics: KPI computation and dashboard widgets.
 */
class Analytics
{
    /**
     * Menu entry for the Tools menu.
     *
     * Analytics is a plain helper class and does not extend CommonDBTM, so it
     * does not inherit CommonGLPI::getMenuContent(). GLPI calls
     * `$type::getMenuContent()` on every class listed in the MENU_TOADD hook
     * (src/Html.php), so this method is mandatory for that registration.
     *
     * @return array|false
     */
    public static function getMenuContent() {
        if (!Session::haveRight(Profile::RIGHTNAME, READ)) {
            return false;
        }

        return [
            'title' => __('Analytics'),
            'page'  => '/plugins/medmetriccmms/front/analytics.php',
            'icon'  => 'ti-chart-bar',
        ];
    }

    /**
     * KPI snapshot for a period.
     *
     * @param string $date_from Y-m-d
     * @param string $date_to   Y-m-d
     *
     * @return array
     */
    public static function getKpis(string $date_from, string $date_to): array {
        global $DB;

        $wo_table = WorkOrder::getTable();
        $eq_table = Equipment::getTable();
        $sol_table = Solution::getTable();

        $count = function (array $criteria) use ($DB): int {
            $iterator = $DB->request($criteria);
            return count($iterator) ? (int) $iterator->current()['c'] : 0;
        };

        return [
            'open_workorders' => $count([
                'COUNT' => 'c', 'FROM' => $wo_table,
                'WHERE' => ['status' => [WorkOrder::STATUS_NEW, WorkOrder::STATUS_ASSIGNED,
                    WorkOrder::STATUS_IN_PROGRESS, WorkOrder::STATUS_WAITING_PARTS]],
            ]),
            'overdue_workorders' => $count([
                'COUNT' => 'c', 'FROM' => $wo_table,
                'WHERE' => [
                    'status' => [WorkOrder::STATUS_NEW, WorkOrder::STATUS_ASSIGNED,
                        WorkOrder::STATUS_IN_PROGRESS, WorkOrder::STATUS_WAITING_PARTS],
                    'NOT' => ['due_date' => null],
                    'due_date' => ['<', date('Y-m-d')],
                ],
            ]),
            'created_workorders' => $count([
                'COUNT' => 'c', 'FROM' => $wo_table,
                'WHERE' => ['DATE(date)' => ['BETWEEN', [$date_from, $date_to]]],
            ]),
            'closed_workorders' => $count([
                'COUNT' => 'c', 'FROM' => $wo_table,
                'WHERE' => ['status' => [WorkOrder::STATUS_DONE, WorkOrder::STATUS_CLOSED],
                    'DATE(date_closed)' => ['BETWEEN', [$date_from, $date_to]]],
            ]),
            'broken_equipment' => $count([
                'COUNT' => 'c', 'FROM' => $eq_table,
                'WHERE' => ['is_deleted' => 0, 'status' => Equipment::STATUS_BROKEN],
            ]),
            'equipment_total' => $count([
                'COUNT' => 'c', 'FROM' => $eq_table, 'WHERE' => ['is_deleted' => 0],
            ]),
            'mttr_minutes' => self::getMttr($date_from, $date_to),
            'downtime_minutes' => self::getTotalDowntime($date_from, $date_to),
            'low_stock_items' => count(Inventory::getLowStock()),
            'expiring_contracts' => count(Contract::getExpiring(60)),
        ];
    }

    /**
     * Mean time to repair in minutes over closed work orders.
     *
     * @param string $date_from Y-m-d
     * @param string $date_to   Y-m-d
     *
     * @return int
     */
    public static function getMttr(string $date_from, string $date_to): int {
        global $DB;
        $iterator = $DB->request([
            'SELECT' => ['AVG' => 'TIMESTAMPDIFF(MINUTE, date, date_closed) AS avg_minutes'],
            'FROM'   => WorkOrder::getTable(),
            'WHERE'  => [
                'status' => [WorkOrder::STATUS_DONE, WorkOrder::STATUS_CLOSED],
                'NOT' => ['date_closed' => null, 'date' => null],
                'DATE(date_closed)' => ['BETWEEN', [$date_from, $date_to]],
            ],
        ]);
        if (!count($iterator)) {
            return 0;
        }
        return (int) round((float) ($iterator->current()['avg_minutes'] ?? 0));
    }

    /**
     * Total equipment downtime in minutes.
     *
     * @param string $date_from Y-m-d
     * @param string $date_to   Y-m-d
     *
     * @return int
     */
    public static function getTotalDowntime(string $date_from, string $date_to): int {
        global $DB;
        $iterator = $DB->request([
            'SELECT' => ['SUM' => 'downtime_duration AS total'],
            'FROM'   => WorkOrder::getTable(),
            'WHERE'  => ['DATE(date)' => ['BETWEEN', [$date_from, $date_to]]],
        ]);
        if (!count($iterator)) {
            return 0;
        }
        return (int) ($iterator->current()['total'] ?? 0);
    }

    /**
     * Work orders grouped by status for charts.
     *
     * @return array status => count
     */
    public static function getWorkOrdersByStatus(): array {
        global $DB;
        $out = [];
        foreach (array_keys(WorkOrder::getStatuses()) as $status) {
            $out[$status] = 0;
        }
        $iterator = $DB->request([
            'SELECT' => ['status', 'COUNT' => 'c'],
            'FROM'   => WorkOrder::getTable(),
            'GROUPBY' => 'status',
        ]);
        foreach ($iterator as $row) {
            $out[(int) $row['status']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * Corrective vs preventive split.
     *
     * @return array kind => count
     */
    public static function getWorkOrdersByKind(): array {
        global $DB;
        $out = [];
        foreach (array_keys(WorkOrder::getKinds()) as $kind) {
            $out[$kind] = 0;
        }
        $iterator = $DB->request([
            'SELECT' => ['workorderkind', 'COUNT' => 'c'],
            'FROM'   => WorkOrder::getTable(),
            'GROUPBY' => 'workorderkind',
        ]);
        foreach ($iterator as $row) {
            $out[(int) $row['workorderkind']] = (int) $row['c'];
        }
        return $out;
    }

    /**
     * Top equipment by number of work orders.
     *
     * @param int $limit rows
     *
     * @return array
     */
    public static function getTopEquipment(int $limit = 10): array {
        global $DB;
        $rows = [];
        $iterator = $DB->request([
            'SELECT' => ['e.id', 'e.name', 'COUNT' => 'c'],
            'FROM'   => WorkOrder::getTable() . ' AS wo',
            'LEFT JOIN' => [
                Equipment::getTable() . ' AS e' => ['ON' => ['wo' => 'plugin_medmetriccmms_equipments_id', 'e' => 'id']],
            ],
            'GROUPBY' => ['e.id', 'e.name'],
            'ORDER'  => ['c DESC'],
            'LIMIT'  => $limit,
        ]);
        foreach ($iterator as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Render the central dashboard widget (DISPLAY_CENTRAL hook).
     *
     * @return void
     */
    public static function showCentralWidget(): void {
        if (!Session::haveRight('plugin_medmetriccmms', READ)) {
            return;
        }
        $kpis = self::getKpis(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));

        echo "<div class='medmetric-widget medmetric-card'>";
        echo "<h3 class='medmetric-card__title'>MedMetric CMMS</h3>";
        echo "<div class='medmetric-kpis'>";
        self::renderKpi(__('Open work orders'), (string) $kpis['open_workorders'], 'medmetric-badge--progress');
        self::renderKpi(__('Overdue'), (string) $kpis['overdue_workorders'],
            $kpis['overdue_workorders'] > 0 ? 'medmetric-badge--cancelled' : 'medmetric-badge--done');
        self::renderKpi(__('Broken equipment'), (string) $kpis['broken_equipment'],
            $kpis['broken_equipment'] > 0 ? 'medmetric-badge--cancelled' : 'medmetric-badge--done');
        self::renderKpi(__('MTTR (min)'), (string) $kpis['mttr_minutes'], 'medmetric-badge--neutral');
        self::renderKpi(__('Low stock'), (string) $kpis['low_stock_items'],
            $kpis['low_stock_items'] > 0 ? 'medmetric-badge--waiting' : 'medmetric-badge--done');
        echo "</div>";
        printf(
            "<p class='medmetric-muted'><a href='%s'>%s</a></p>",
            htmlescape('/plugins/medmetriccmms/front/analytics.php'),
            htmlescape(__('Open analytics dashboard'))
        );
        echo "</div>";
    }

    /**
     * Render one KPI tile.
     *
     * @param string $label label
     * @param string $value value
     * @param string $badge badge class
     *
     * @return void
     */
    protected static function renderKpi(string $label, string $value, string $badge): void {
        printf(
            "<div class='medmetric-kpi'><div class='medmetric-kpi__value medmetric-badge %s'>%s</div><div class='medmetric-kpi__label'>%s</div></div>",
            htmlescape($badge),
            htmlescape($value),
            htmlescape($label)
        );
    }

    /**
     * Render the full analytics dashboard page.
     *
     * @return void
     */
    public static function showDashboard(): void {
        $from = date('Y-m-d', strtotime('-90 days'));
        $to = date('Y-m-d');
        $kpis = self::getKpis($from, $to);
        $by_status = self::getWorkOrdersByStatus();
        $by_kind = self::getWorkOrdersByKind();
        $top = self::getTopEquipment(8);

        echo "<div class='medmetric-card'>";
        echo "<h2 class='medmetric-card__title'>" . htmlescape(__('CMMS analytics')) . "</h2>";
        echo "<div class='medmetric-kpis'>";
        self::renderKpi(__('Equipment'), (string) $kpis['equipment_total'], 'medmetric-badge--neutral');
        self::renderKpi(__('Open work orders'), (string) $kpis['open_workorders'], 'medmetric-badge--progress');
        self::renderKpi(__('Overdue'), (string) $kpis['overdue_workorders'],
            $kpis['overdue_workorders'] > 0 ? 'medmetric-badge--cancelled' : 'medmetric-badge--done');
        self::renderKpi(__('Closed (90d)'), (string) $kpis['closed_workorders'], 'medmetric-badge--done');
        self::renderKpi(__('MTTR (min)'), (string) $kpis['mttr_minutes'], 'medmetric-badge--neutral');
        self::renderKpi(__('Downtime (min)'), (string) $kpis['downtime_minutes'], 'medmetric-badge--waiting');
        self::renderKpi(__('Low stock'), (string) $kpis['low_stock_items'],
            $kpis['low_stock_items'] > 0 ? 'medmetric-badge--waiting' : 'medmetric-badge--done');
        self::renderKpi(__('Contracts expiring'), (string) $kpis['expiring_contracts'], 'medmetric-badge--neutral');
        echo "</div></div>";

        // Status bars
        echo "<div class='medmetric-card'><h3 class='medmetric-card__title'>" . htmlescape(__('Work orders by status')) . "</h3>";
        $max = max(1, max($by_status));
        echo "<div class='medmetric-bars'>";
        foreach (WorkOrder::getStatuses() as $status => $label) {
            $count = $by_status[$status] ?? 0;
            $pct = (int) round($count / $max * 100);
            printf(
                "<div class='medmetric-bar'><span class='medmetric-bar__label'>%s</span><span class='medmetric-bar__track'><span class='medmetric-bar__fill' style='width:%d%%'></span></span><span class='medmetric-bar__value'>%d</span></div>",
                htmlescape($label),
                $pct,
                $count
            );
        }
        echo "</div></div>";

        // Kind split
        echo "<div class='medmetric-card'><h3 class='medmetric-card__title'>" . htmlescape(__('Corrective vs preventive')) . "</h3><div class='medmetric-bars'>";
        $max_kind = max(1, max($by_kind));
        foreach (WorkOrder::getKinds() as $kind => $label) {
            $count = $by_kind[$kind] ?? 0;
            $pct = (int) round($count / $max_kind * 100);
            printf(
                "<div class='medmetric-bar'><span class='medmetric-bar__label'>%s</span><span class='medmetric-bar__track'><span class='medmetric-bar__fill medmetric-bar__fill--alt' style='width:%d%%'></span></span><span class='medmetric-bar__value'>%d</span></div>",
                htmlescape($label),
                $pct,
                $count
            );
        }
        echo "</div></div>";

        // Top equipment
        echo "<div class='medmetric-card'><h3 class='medmetric-card__title'>" . htmlescape(__('Most failing equipment')) . "</h3>";
        if ($top === []) {
            echo "<p class='medmetric-muted'>" . htmlescape(__('No work orders yet.')) . "</p></div>";
        } else {
            echo "<table class='tab_cadre_fixehov medmetric-table'><tr><th>" . htmlescape(Equipment::getTypeName(1))
                . "</th><th>" . htmlescape(__('Work orders')) . "</th></tr>";
            foreach ($top as $row) {
                $name = $row['name'] !== null ? (string) $row['name'] : __('(deleted)');
                $url = Equipment::getFormURLWithID((int) $row['id']);
                printf(
                    "<tr><td><a href='%s'>%s</a></td><td>%d</td></tr>",
                    htmlescape($url),
                    htmlescape($name),
                    (int) $row['c']
                );
            }
            echo "</table></div>";
        }
    }

    /**
     * JSON payload for the analytics ajax endpoint.
     *
     * @return array
     */
    public static function getJsonSummary(): array {
        $kpis = self::getKpis(date('Y-m-d', strtotime('-30 days')), date('Y-m-d'));
        return [
            'kpis'      => $kpis,
            'by_status' => self::getWorkOrdersByStatus(),
            'by_kind'   => self::getWorkOrdersByKind(),
        ];
    }
}
