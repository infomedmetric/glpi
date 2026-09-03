<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Permission Class
 *
 * Department-based access control system.
 * - Admin/Department Head: Can see all equipment in their department
 * - Regular Staff: Can only see equipment assigned to them
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Hospital CMMS Permission System
 *
 * Controls access to medical equipment based on department membership.
 */
class PluginHospitalcmmsPermission extends CommonDBTM {

    public static $rightname = 'plugin_hospitalcmms_permissions';

    /**
     * User roles
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_DEPARTMENT_HEAD = 'department_head';
    const ROLE_TECHNICIAN = 'technician';
    const ROLE_STAFF = 'staff';

    /**
     * Get user's role in a specific department
     */
    public static function getUserRole($userId, $departmentId) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['role'],
            'FROM'   => 'glpi_plugin_hospitalcmms_user_departments',
            'WHERE'  => [
                'users_id'                      => $userId,
                'plugin_hospitalcmms_categories_id' => $departmentId,
            ],
        ]);

        if (count($iterator)) {
            $data = $iterator->current();
            return $data['role'];
        }

        // Check if user is GLPI admin
        if (Session::haveRight('config', UPDATE)) {
            return self::ROLE_ADMIN;
        }

        return null;
    }

    /**
     * Get all departments where user has access
     */
    public static function getUserDepartments($userId) {
        global $DB;

        // GLPI admins have access to all departments
        if (Session::haveRight('config', UPDATE)) {
            $iterator = $DB->request([
                'SELECT' => ['id', 'name'],
                'FROM'   => PluginHospitalcmmsCategory::getTable(),
                'WHERE'  => ['is_active' => 1],
                'ORDER'  => 'name ASC',
            ]);
            return $iterator;
        }

        $iterator = $DB->request([
            'SELECT' => [
                'ud.plugin_hospitalcmms_categories_id',
                'c.name',
            ],
            'FROM'       => 'glpi_plugin_hospitalcmms_user_departments AS ud',
            'LEFT JOIN'  => [
                PluginHospitalcmmsCategory::getTable() . ' AS c' => [
                    'FKEY' => [
                        'ud' => 'plugin_hospitalcmms_categories_id',
                        'c'  => 'id'
                    ]
                ]
            ],
            'WHERE'      => [
                'ud.users_id' => $userId,
            ],
            'ORDER'      => 'c.name ASC',
        ]);

        return $iterator;
    }

    /**
     * Check if user can view equipment in a specific department
     */
    public static function canViewDepartmentEquipment($userId, $departmentId) {
        // GLPI admins can view everything
        if (Session::haveRight('config', UPDATE)) {
            return true;
        }

        $role = self::getUserRole($userId, $departmentId);

        // Department heads and technicians can view their department
        if (in_array($role, [self::ROLE_DEPARTMENT_HEAD, self::ROLE_TECHNICIAN])) {
            return true;
        }

        // Staff can only view equipment assigned to them
        return false;
    }

    /**
     * Check if user is department head
     */
    public static function isDepartmentHead($userId, $departmentId) {
        $role = self::getUserRole($userId, $departmentId);
        return $role === self::ROLE_DEPARTMENT_HEAD;
    }

    /**
     * Get department IDs where user is department head
     */
    public static function getManagedDepartments($userId) {
        global $DB;

        // GLPI admins manage all departments
        if (Session::haveRight('config', UPDATE)) {
            $iterator = $DB->request([
                'SELECT' => ['id'],
                'FROM'   => PluginHospitalcmmsCategory::getTable(),
                'WHERE'  => ['is_active' => 1],
            ]);
            return $iterator;
        }

        $iterator = $DB->request([
            'SELECT' => ['plugin_hospitalcmms_categories_id'],
            'FROM'   => 'glpi_plugin_hospitalcmms_user_departments',
            'WHERE'  => [
                'users_id' => $userId,
                'role'     => self::ROLE_DEPARTMENT_HEAD,
            ],
        ]);

        return $iterator;
    }

    /**
     * Add user to department
     */
    public static function addUserToDepartment($userId, $departmentId, $role = self::ROLE_STAFF) {
        global $DB;

        // Check if already assigned
        $existing = self::getUserRole($userId, $departmentId);
        if ($existing !== null) {
            // Update existing assignment
            $DB->update('glpi_plugin_hospitalcmms_user_departments', [
                'role' => $role,
            ], [
                'users_id'                      => $userId,
                'plugin_hospitalcmms_categories_id' => $departmentId,
            ]);
        } else {
            // Add new assignment
            $DB->insert('glpi_plugin_hospitalcmms_user_departments', [
                'users_id'                      => $userId,
                'plugin_hospitalcmms_categories_id' => $departmentId,
                'role'                          => $role,
            ]);
        }

        return true;
    }

    /**
     * Remove user from department
     */
    public static function removeUserFromDepartment($userId, $departmentId) {
        global $DB;

        $DB->delete('glpi_plugin_hospitalcmms_user_departments', [
            'users_id'                      => $userId,
            'plugin_hospitalcmms_categories_id' => $departmentId,
        ]);

        return true;
    }

    /**
     * Get SQL WHERE clause for department filtering
     *
     * @param int $userId User ID to filter for
     * @return array SQL WHERE conditions
     */
    public static function getDepartmentFilter($userId) {
        global $DB;

        // GLPI admins see everything
        if (Session::haveRight('config', UPDATE)) {
            return [];
        }

        // Get departments where user has access
        $departments = self::getUserDepartments($userId);
        $departmentIds = [];
        foreach ($departments as $dept) {
            $departmentIds[] = $dept['plugin_hospitalcmms_categories_id'];
        }

        if (empty($departmentIds)) {
            // User has no department access - return impossible condition
            return ['0 = 1'];
        }

        return [
            'plugin_hospitalcmms_categories_id' => $departmentIds,
        ];
    }

    /**
     * Get SQL WHERE clause for equipment visibility
     *
     * - Department Head/Technician: See all equipment in their departments
     * - Staff: See only equipment assigned to them
     *
     * @param int $userId User ID
     * @return array SQL WHERE conditions
     */
    public static function getEquipmentVisibilityFilter($userId) {
        global $DB;

        // GLPI admins see everything
        if (Session::haveRight('config', UPDATE)) {
            return [];
        }

        $conditions = [];

        // Check if user is department head or technician in any department
        $iterator = $DB->request([
            'SELECT' => ['plugin_hospitalcmms_categories_id', 'role'],
            'FROM'   => 'glpi_plugin_hospitalcmms_user_departments',
            'WHERE'  => [
                'users_id' => $userId,
                'role'     => [self::ROLE_DEPARTMENT_HEAD, self::ROLE_TECHNICIAN],
            ],
        ]);

        $managedDepartments = [];
        foreach ($iterator as $row) {
            $managedDepartments[] = $row['plugin_hospitalcmms_categories_id'];
        }

        if (!empty($managedDepartments)) {
            // Department heads/technicians see all equipment in their departments
            $conditions[] = [
                'plugin_hospitalcmms_categories_id' => $managedDepartments,
            ];
        }

        // Staff also see equipment assigned to them personally
        $conditions[] = [
            'OR' => [
                'users_id' => $userId,
                'users_id_tech' => $userId,
            ],
        ];

        if (empty($conditions)) {
            // User has no access at all
            return ['0 = 1'];
        }

        return ['OR' => $conditions];
    }

    /**
     * Check if user can modify equipment
     */
    public static function canModifyEquipment($userId, $equipmentId) {
        global $DB;

        // GLPI admins can modify anything
        if (Session::haveRight('config', UPDATE)) {
            return true;
        }

        // Get equipment details
        $iterator = $DB->request([
            'SELECT' => ['plugin_hospitalcmms_categories_id', 'users_id', 'users_id_tech'],
            'FROM'   => PluginHospitalcmmsMedicalEquipment::getTable(),
            'WHERE'  => ['id' => $equipmentId],
        ]);

        if (count($iterator)) {
            $equipment = $iterator->current();
            $departmentId = $equipment['plugin_hospitalcmms_categories_id'];

            // Department heads can modify equipment in their department
            if (self::isDepartmentHead($userId, $departmentId)) {
                return true;
            }

            // Technicians can modify equipment assigned to them
            $role = self::getUserRole($userId, $departmentId);
            if ($role === self::ROLE_TECHNICIAN) {
                if ($equipment['users_id'] == $userId || $equipment['users_id_tech'] == $userId) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get role display name
     */
    public static function getRoleName($role) {
        $roles = [
            self::ROLE_ADMIN           => __('Administrator'),
            self::ROLE_DEPARTMENT_HEAD => __('Department Head'),
            self::ROLE_TECHNICIAN      => __('Technician'),
            self::ROLE_STAFF           => __('Staff'),
        ];

        return $roles[$role] ?? $role;
    }

    /**
     * Get all available roles
     */
    public static function getRoles() {
        return [
            self::ROLE_ADMIN           => __('Administrator'),
            self::ROLE_DEPARTMENT_HEAD => __('Department Head'),
            self::ROLE_TECHNICIAN      => __('Technician'),
            self::ROLE_STAFF           => __('Staff'),
        ];
    }

    /**
     * Get users assigned to a department
     */
    public static function getDepartmentUsers($departmentId) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => [
                'ud.users_id',
                'ud.role',
                'u.name AS username',
                'u.realname',
                'u.firstname',
            ],
            'FROM'       => 'glpi_plugin_hospitalcmms_user_departments AS ud',
            'LEFT JOIN'  => [
                'glpi_users AS u' => [
                    'FKEY' => [
                        'ud' => 'users_id',
                        'u'  => 'id'
                    ]
                ]
            ],
            'WHERE'      => [
                'ud.plugin_hospitalcmms_categories_id' => $departmentId,
            ],
            'ORDER'      => 'u.name ASC',
        ]);

        $users = [];
        foreach ($iterator as $row) {
            $users[] = [
                'id'       => $row['users_id'],
                'username' => $row['username'],
                'name'     => trim($row['realname'] . ' ' . $row['firstname']),
                'role'     => $row['role'],
            ];
        }

        return $users;
    }
}
