<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Maintenance Task Class
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Maintenance Task Class
 *
 * Manages preventive maintenance schedules for medical equipment.
 * Tracks frequency, execution history, and upcoming maintenance.
 */
class PluginHospitalCmmsMaintenanceTask extends CommonDBTM {

    // From CommonDBTM
    public $dohistory = true;

    public static $rightname = 'plugin_hospital_cmms_maintenance_tasks';

    public static function getTypeName($nb = 0) {
        return _n('Maintenance Task', 'Maintenance Tasks', $nb);
    }

    public function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong)
            ->addStandardTab(Notepad::class, $ong, $options)
            ->addStandardTab(Log::class, $ong, $options);

        return $ong;
    }

    public function prepareInputForAdd($input) {
        if (isset($input["id"]) && ($input["id"] > 0)) {
            $input["_oldID"] = $input["id"];
        }
        unset($input['id']);
        return $input;
    }

    /**
     * Get upcoming maintenance tasks
     */
    public static function getUpcomingTasks($days = 30) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'mt.id',
                'mt.name',
                'mt.next_execution_date',
                'mt.type',
                'mt.frequency',
                'mt.frequency_unit',
                'mt.users_id_tech',
                'me.name AS equipment_name',
                'me.serial AS equipment_serial',
            ],
            'FROM'       => 'glpi_plugin_hospital_cmms_maintenance_tasks AS mt',
            'LEFT JOIN'  => [
                'glpi_plugin_hospital_cmms_equipments AS me' => [
                    'FKEY' => [
                        'mt'  => 'plugin_hospital_cmms_equipments_id',
                        'me'  => 'id'
                    ]
                ]
            ],
            'WHERE'      => [
                'mt.is_active'              => 1,
                'NOT' => ['mt.next_execution_date' => null],
                'mt.next_execution_date' => [
                    '<=', date('Y-m-d', strtotime("+{$days} days")),
                ],
            ],
            'ORDER'      => 'mt.next_execution_date ASC',
        ]);

        return $iterator;
    }

    /**
     * Get overdue maintenance tasks
     */
    public static function getOverdueTasks() {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'mt.id',
                'mt.name',
                'mt.next_execution_date',
                'mt.type',
                'me.name AS equipment_name',
            ],
            'FROM'       => 'glpi_plugin_hospital_cmms_maintenance_tasks AS mt',
            'LEFT JOIN'  => [
                'glpi_plugin_hospital_cmms_equipments AS me' => [
                    'FKEY' => [
                        'mt'  => 'plugin_hospital_cmms_equipments_id',
                        'me'  => 'id'
                    ]
                ]
            ],
            'WHERE'      => [
                'mt.is_active'              => 1,
                'NOT' => ['mt.next_execution_date' => null],
                'mt.next_execution_date' => ['<', date('Y-m-d')],
            ],
            'ORDER'      => 'mt.next_execution_date ASC',
        ]);

        return $iterator;
    }

    /**
     * Record maintenance execution
     */
    public function recordExecution($taskId, $comment = '', $duration = 0, $cost = 0) {
        global $DB;

        $task = new self();
        $task->getFromDB($taskId);

        if ($task->getID() > 0) {
            // Record in history
            $DB->insert('glpi_plugin_hospital_cmms_maintenance_history', [
                'plugin_hospital_cmms_equipments_id'    => $task->fields['plugin_hospital_cmms_equipments_id'],
                'plugin_hospital_cmms_maintenance_tasks_id' => $taskId,
                'action'                                => 'completed',
                'comment'                               => $comment,
                'users_id'                              => Session::getLoginUserID(),
                'execution_date'                        => $_SESSION['glpi_currenttime'],
                'duration'                              => $duration,
                'cost'                                  => $cost,
                'entities_id'                           => $task->fields['entities_id'],
            ]);

            // Update task dates
            $task->update([
                'id'                    => $taskId,
                'last_execution_date'   => date('Y-m-d'),
                'next_execution_date'   => $this->calculateNextDate(
                    date('Y-m-d'),
                    $task->fields['frequency'],
                    $task->fields['frequency_unit']
                ),
            ]);

            return true;
        }

        return false;
    }

    /**
     * Calculate next maintenance date
     */
    private function calculateNextDate($startDate, $frequency, $unit) {
        $date = new DateTime($startDate);

        switch ($unit) {
            case 'day':
                $date->modify("+{$frequency} days");
                break;
            case 'week':
                $date->modify("+{$frequency} weeks");
                break;
            case 'month':
                $date->modify("+{$frequency} months");
                break;
            case 'year':
                $date->modify("+{$frequency} years");
                break;
        }

        return $date->format('Y-m-d');
    }

    public function rawSearchOptions() {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'            => '2',
            'table'         => $this->getTable(),
            'field'         => 'id',
            'name'          => __('ID'),
            'massiveaction' => false,
            'datatype'      => 'number',
        ];

        $tab[] = [
            'id'            => '3',
            'table'         => $this->getTable(),
            'field'         => 'name',
            'name'          => __('Task Name'),
            'datatype'      => 'string',
        ];

        $tab[] = [
            'id'            => '4',
            'table'         => 'glpi_plugin_hospital_cmms_equipments',
            'field'         => 'name',
            'name'          => __('Equipment'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '5',
            'table'         => $this->getTable(),
            'field'         => 'type',
            'name'          => __('Task Type'),
            'datatype'      => 'dropdown',
            'searchunit'    => 'Dropdown',
            'maybefuture'   => true,
            'tokensearch'   => true,
        ];

        $tab[] = [
            'id'            => '6',
            'table'         => $this->getTable(),
            'field'         => 'frequency',
            'name'          => __('Frequency'),
            'datatype'      => 'number',
        ];

        $tab[] = [
            'id'            => '7',
            'table'         => $this->getTable(),
            'field'         => 'next_execution_date',
            'name'          => __('Next Execution'),
            'datatype'      => 'date',
        ];

        $tab[] = [
            'id'            => '8',
            'table'         => $this->getTable(),
            'field'         => 'last_execution_date',
            'name'          => __('Last Execution'),
            'datatype'      => 'date',
        ];

        $tab[] = [
            'id'            => '9',
            'table'         => 'glpi_users',
            'field'         => 'name',
            'linkfield'     => 'users_id_tech',
            'name'          => __('Technician in charge'),
            'datatype'      => 'dropdown',
            'right'         => 'own_ticket',
        ];

        $tab[] = [
            'id'            => '10',
            'table'         => $this->getTable(),
            'field'         => 'is_active',
            'name'          => __('Active'),
            'datatype'      => 'bool',
        ];

        $tab[] = [
            'id'            => '11',
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
    }

    /**
     * Get icon for this itemtype
     */
    public static function getIcon() {
        return "ti ti-calendar-star";
    }
}
