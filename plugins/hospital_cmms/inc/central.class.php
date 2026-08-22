<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Central Dashboard Class
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Hospital CMMS Central Dashboard
 *
 * Displays the main dashboard for hospital equipment management.
 */
class PluginHospitalCmmsCentral extends CommonGLPI {

    public static function getTypeName($nb = 0) {
        return __('Hospital CMMS Dashboard');
    }

    /**
     * Show the central dashboard
     */
    public static function showCentral() {
        global $CFG_GLPI;

        echo "<div class='center'>";
        echo "<h2>" . __('Hospital CMMS Dashboard') . "</h2>";

        // Equipment Statistics
        self::showEquipmentStats();

        // Upcoming Maintenance
        self::showUpcomingMaintenance();

        // Equipment by Department
        self::showEquipmentByDepartment();

        // Calibration Due
        self::showCalibrationDue();

        echo "</div>";
    }

    /**
     * Show equipment statistics
     */
    private static function showEquipmentStats() {
        global $DB;

        $total = $DB->result($DB->request([
            'SELECT' => ['COUNT(*) AS total'],
            'FROM'   => 'glpi_plugin_hospital_cmms_equipments',
            'WHERE'  => ['is_deleted' => 0],
        ]), 0, 'total');

        $active = $DB->result($DB->request([
            'SELECT' => ['COUNT(*) AS total'],
            'FROM'   => 'glpi_plugin_hospital_cmms_equipments',
            'WHERE'  => [
                'is_deleted' => 0,
                'states_id'  => ['!=', 0],
            ],
        ]), 0, 'total');

        $needingMaintenance = $DB->result($DB->request([
            'SELECT' => ['COUNT(*) AS total'],
            'FROM'   => 'glpi_plugin_hospital_cmms_maintenance_tasks',
            'WHERE'  => [
                'is_active' => 1,
                'next_execution_date' => ['<=', date('Y-m-d')],
            ],
        ]), 0, 'total');

        echo "<div class='center'>";
        echo "<div style='display: inline-block; margin: 10px; padding: 20px; background: #f0f9ff; border-radius: 8px; min-width: 200px;'>";
        echo "<h3 style='margin: 0; color: #0369a1;'>" . __('Total Equipment') . "</h3>";
        echo "<p style='font-size: 32px; margin: 10px 0; color: #1e40af;'>" . $total . "</p>";
        echo "</div>";

        echo "<div style='display: inline-block; margin: 10px; padding: 20px; background: #f0fdf4; border-radius: 8px; min-width: 200px;'>";
        echo "<h3 style='margin: 0; color: #16a34a;'>" . __('Active Equipment') . "</h3>";
        echo "<p style='font-size: 32px; margin: 10px 0; color: #15803d;'>" . $active . "</p>";
        echo "</div>";

        echo "<div style='display: inline-block; margin: 10px; padding: 20px; background: #fef2f2; border-radius: 8px; min-width: 200px;'>";
        echo "<h3 style='margin: 0; color: #dc2626;'>" . __('Maintenance Due') . "</h3>";
        echo "<p style='font-size: 32px; margin: 10px 0; color: #b91c1c;'>" . $needingMaintenance . "</p>";
        echo "</div>";
        echo "</div>";
    }

    /**
     * Show upcoming maintenance tasks
     */
    private static function showUpcomingMaintenance() {
        $tasks = PluginHospitalCmmsMaintenanceTask::getUpcomingTasks(30);

        echo "<div style='margin: 20px; padding: 20px; background: white; border-radius: 8px; border: 1px solid #e5e7eb;'>";
        echo "<h3 style='margin-top: 0; color: #374151;'>" . __('Upcoming Maintenance (Next 30 Days)') . "</h3>";

        if (count($tasks) > 0) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th>" . __('Equipment') . "</th>";
            echo "<th>" . __('Task') . "</th>";
            echo "<th>" . __('Scheduled Date') . "</th>";
            echo "<th>" . __('Type') . "</th>";
            echo "</tr>";

            foreach ($tasks as $task) {
                $dateClass = '';
                if ($task['next_execution_date'] <= date('Y-m-d')) {
                    $dateClass = 'style="color: #dc2626; font-weight: bold;"';
                } elseif ($task['next_execution_date'] <= date('Y-m-d', strtotime('+7 days'))) {
                    $dateClass = 'style="color: #ea580c;"';
                }

                echo "<tr>";
                echo "<td>" . htmlescape($task['equipment_name']) . "</td>";
                echo "<td>" . htmlescape($task['name']) . "</td>";
                echo "<td {$dateClass}>" . htmlescape($task['next_execution_date']) . "</td>";
                echo "<td>" . htmlescape($task['type']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>" . __('No upcoming maintenance tasks.') . "</p>";
        }

        echo "</div>";
    }

    /**
     * Show equipment by department
     */
    private static function showEquipmentByDepartment() {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'c.id',
                'c.name',
                'COUNT(e.id) AS equipment_count',
            ],
            'FROM'       => 'glpi_plugin_hospital_cmms_categories AS c',
            'LEFT JOIN'  => [
                'glpi_plugin_hospital_cmms_equipments AS e' => [
                    'FKEY' => [
                        'c'  => 'id',
                        'e'  => 'plugin_hospital_cmms_categories_id'
                    ]
                ]
            ],
            'WHERE'      => [
                'c.is_active' => 1,
                'e.is_deleted' => 0,
            ],
            'GROUPBY'    => 'c.id',
            'ORDER'      => 'c.name ASC',
        ]);

        echo "<div style='margin: 20px; padding: 20px; background: white; border-radius: 8px; border: 1px solid #e5e7eb;'>";
        echo "<h3 style='margin-top: 0; color: #374151;'>" . __('Equipment by Department') . "</h3>";

        if (count($iterator) > 0) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th>" . __('Department') . "</th>";
            echo "<th>" . __('Equipment Count') . "</th>";
            echo "</tr>";

            foreach ($iterator as $dept) {
                echo "<tr>";
                echo "<td>" . htmlescape($dept['name']) . "</td>";
                echo "<td class='center'>" . $dept['equipment_count'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>" . __('No departments configured.') . "</p>";
        }

        echo "</div>";
    }

    /**
     * Show equipment needing calibration
     */
    private static function showCalibrationDue() {
        $equipment = PluginHospitalCmmsMedicalEquipment::getEquipmentNeedingCalibration(90);

        echo "<div style='margin: 20px; padding: 20px; background: white; border-radius: 8px; border: 1px solid #e5e7eb;'>";
        echo "<h3 style='margin-top: 0; color: #374151;'>" . __('Calibration Due (Next 90 Days)') . "</h3>";

        if (count($equipment) > 0) {
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr>";
            echo "<th>" . __('Equipment') . "</th>";
            echo "<th>" . __('Serial Number') . "</th>";
            echo "<th>" . __('Department') . "</th>";
            echo "<th>" . __('Next Calibration') . "</th>";
            echo "</tr>";

            foreach ($equipment as $item) {
                $dateClass = '';
                if ($item['next_calibration_date'] <= date('Y-m-d')) {
                    $dateClass = 'style="color: #dc2626; font-weight: bold;"';
                } elseif ($item['next_calibration_date'] <= date('Y-m-d', strtotime('+30 days'))) {
                    $dateClass = 'style="color: #ea580c;"';
                }

                echo "<tr>";
                echo "<td>" . htmlescape($item['name']) . "</td>";
                echo "<td>" . htmlescape($item['serial']) . "</td>";
                echo "<td>" . htmlescape($item['plugin_hospital_cmms_categories_id']) . "</td>";
                echo "<td {$dateClass}>" . htmlescape($item['next_calibration_date']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>" . __('No calibration due soon.') . "</p>";
        }

        echo "</div>";
    }
}
