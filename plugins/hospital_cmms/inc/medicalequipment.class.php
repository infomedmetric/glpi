<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Medical Equipment Class
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Medical Equipment Class
 *
 * Represents medical equipment in the hospital CMMS system.
 * Each piece of equipment is associated with a department, type, and model.
 */
class PluginHospitalCmmsMedicalEquipment extends CommonDBTM {

    // From CommonDBTM
    public $dohistory = true;

    // DDB fields
    public static $rightname = 'plugin_hospital_cmms_equipments';

    public function getCloneRelations(): array {
        return [
            Infocom::class,
            Contract_Item::class,
            Document_Item::class,
            Notepad::class,
            KnowbaseItem_Item::class,
            ManualLink::class,
        ];
    }

    public static function getTypeName($nb = 0) {
        return _n('Medical Equipment', 'Medical Equipment', $nb);
    }

    public static function getMenuShorcut() {
        return 'm';
    }

    public function defineTabs($options = []) {
        $ong = [];
        $this->addDefaultFormTab($ong)
            ->addStandardTab(Infocom::class, $ong, $options)
            ->addStandardTab(Contract_Item::class, $ong, $options)
            ->addStandardTab(Document_Item::class, $ong, $options)
            ->addStandardTab(Item_Ticket::class, $ong, $options)
            ->addStandardTab(KnowbaseItem_Item::class, $ong, $options)
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

    public function post_addItem() {
        parent::post_addItem();

        // Log the creation
        $this->addToHistory(
            'creation',
            sprintf(__('Medical equipment %s added'), $this->fields['name'])
        );
    }

    public function post_updateItem($history = true) {
        parent::post_updateItem($history);

        // Log the update
        $this->addToHistory(
            'update',
            sprintf(__('Medical equipment %s updated'), $this->fields['name'])
        );
    }

    /**
     * Add entry to maintenance history
     */
    private function addToHistory($action, $comment = '') {
        global $DB;

        $DB->insert('glpi_plugin_hospital_cmms_maintenance_history', [
            'plugin_hospital_cmms_equipments_id' => $this->fields['id'],
            'action'                             => $action,
            'comment'                            => $comment,
            'users_id'                           => Session::getLoginUserID(),
            'execution_date'                     => $_SESSION['glpi_currenttime'],
            'entities_id'                        => $this->fields['entities_id'],
        ]);
    }

    public function getSpecificMassiveActions($checkitem = null) {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if ($isadmin) {
            $actions += [
                'update_department' => '<i class="ti ti-building-2"></i>' . __('Update Department'),
                'update_technician' => '<i class="ti ti-user"></i>' . __('Update Technician'),
            ];

            KnowbaseItem_Item::getMassiveActionsForItemtype($actions, self::class, false, $checkitem);
        }

        return $actions;
    }

    /**
     * Get SQL WHERE clause for department filtering
     *
     * @return array SQL WHERE conditions
     */
    public function getSqlCriteria() {
        $criteria = parent::getSqlCriteria();

        // Add department filtering based on user permissions
        $userId = Session::getLoginUserID();
        $permissionFilter = PluginHospitalCmmsPermission::getEquipmentVisibilityFilter($userId);

        if (!empty($permissionFilter)) {
            $criteria = array_merge($criteria, $permissionFilter);
        }

        return $criteria;
    }

    /**
     * Raw search options for the search engine
     */
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
            'name'          => __('Name'),
            'datatype'      => 'string',
            'massiveaction' => false,
            'datatype'      => 'itemlink',
        ];

        $tab[] = [
            'id'            => '4',
            'table'         => 'glpi_plugin_hospital_cmms_categories',
            'field'         => 'completename',
            'name'          => __('Department'),
            'datatype'      => 'dropdown',
            'condition'     => ['is_active' => 1],
        ];

        $tab[] = [
            'id'            => '5',
            'table'         => 'glpi_plugin_hospital_cmms_types',
            'field'         => 'name',
            'name'          => __('Equipment Type'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '6',
            'table'         => 'glpi_plugin_hospital_cmms_models',
            'field'         => 'name',
            'name'          => __('Equipment Model'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '7',
            'table'         => 'glpi_manufacturers',
            'field'         => 'name',
            'name'          => __('Manufacturer'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '8',
            'table'         => $this->getTable(),
            'field'         => 'serial',
            'name'          => __('Serial Number'),
            'datatype'      => 'string',
        ];

        $tab[] = [
            'id'            => '9',
            'table'         => $this->getTable(),
            'field'         => 'otherserial',
            'name'          => __('Inventory Number'),
            'datatype'      => 'string',
        ];

        $tab[] = [
            'id'            => '10',
            'table'         => 'glpi_locations',
            'field'         => 'completename',
            'name'          => __('Location'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '11',
            'table'         => 'glpi_states',
            'field'         => 'completename',
            'name'          => __('Status'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '12',
            'table'         => 'glpi_users',
            'field'         => 'name',
            'linkfield'     => 'users_id_tech',
            'name'          => __('Technician in charge'),
            'datatype'      => 'dropdown',
            'right'         => 'own_ticket',
        ];

        $tab[] = [
            'id'            => '13',
            'table'         => 'glpi_groups',
            'field'         => 'completename',
            'linkfield'     => 'groups_id_tech',
            'name'          => __('Group in charge'),
            'condition'     => ['is_assign' => 1],
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '14',
            'table'         => $this->getTable(),
            'field'         => 'date_purchase',
            'name'          => __('Purchase Date'),
            'datatype'      => 'date',
        ];

        $tab[] = [
            'id'            => '15',
            'table'         => $this->getTable(),
            'field'         => 'date_warranty',
            'name'          => __('Warranty Expiration'),
            'datatype'      => 'date',
        ];

        $tab[] = [
            'id'            => '16',
            'table'         => $this->getTable(),
            'field'         => 'date_commissioning',
            'name'          => __('Commissioning Date'),
            'datatype'      => 'date',
        ];

        $tab[] = [
            'id'            => '17',
            'table'         => $this->getTable(),
            'field'         => 'value',
            'name'          => __('Value'),
            'datatype'      => 'number',
            'maybefuture'   => true,
        ];

        $tab[] = [
            'id'            => '18',
            'table'         => $this->getTable(),
            'field'         => 'last_calibration_date',
            'name'          => __('Last Calibration Date'),
            'datatype'      => 'date',
        ];

        $tab[] = [
            'id'            => '19',
            'table'         => $this->getTable(),
            'field'         => 'next_calibration_date',
            'name'          => __('Next Calibration Date'),
            'datatype'      => 'date',
        ];

        $tab[] = [
            'id'            => '20',
            'table'         => $this->getTable(),
            'field'         => 'comment',
            'name'          => __('Comment'),
            'datatype'      => 'text',
        ];

        $tab[] = [
            'id'            => '80',
            'table'         => 'glpi_entities',
            'field'         => 'completename',
            'name'          => __('Entity'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '19',
            'table'         => $this->getTable(),
            'field'         => 'date_mod',
            'name'          => __('Last update'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        $tab[] = [
            'id'            => '121',
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __('Creation date'),
            'datatype'      => 'datetime',
            'massiveaction' => false,
        ];

        return $tab;
    }

    /**
     * Get icon for this itemtype
     */
    public static function getIcon() {
        return "ti ti-heartbeat";
    }

    /**
     * Get equipment by department
     */
    public static function getEquipmentByDepartment($departmentId) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'serial', 'plugin_hospital_cmms_types_id'],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'plugin_hospital_cmms_categories_id' => $departmentId,
                'is_deleted'                         => 0,
            ],
            'ORDER'  => 'name ASC',
        ]);

        return $iterator;
    }

    /**
     * Get equipment requiring calibration soon
     */
    public static function getEquipmentNeedingCalibration($days = 30) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'id', 'name', 'serial',
                'next_calibration_date',
                'plugin_hospital_cmms_categories_id',
            ],
            'FROM'   => self::getTable(),
            'WHERE'  => [
                'is_deleted'                 => 0,
                'NOT' => ['next_calibration_date' => null],
                'next_calibration_date' => [
                    '<=', date('Y-m-d', strtotime("+{$days} days")),
                ],
            ],
            'ORDER'  => 'next_calibration_date ASC',
        ]);

        return $iterator;
    }
}
