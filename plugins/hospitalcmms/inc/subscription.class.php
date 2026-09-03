<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Subscription Management
 *
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Hospital CMMS Subscription Class
 *
 * Manages hospital subscriptions, free trials, and billing.
 */
class PluginHospitalcmmsSubscription extends CommonDBTM {

    public static $rightname = 'plugin_hospitalcmms_subscriptions';

    /**
     * Subscription statuses
     */
    const STATUS_TRIAL = 'trial';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Trial duration in days
     */
    const TRIAL_DAYS = 20;

    /**
     * Subscription plans
     */
    const PLAN_BASIC = 'basic';
    const PLAN_PROFESSIONAL = 'professional';
    const PLAN_ENTERPRISE = 'enterprise';

    /**
     * Plan details
     */
    const PLANS = [
        self::PLAN_BASIC => [
            'name'           => 'Basic',
            'price_monthly'  => 99,
            'price_yearly'   => 990,
            'max_equipment'  => 100,
            'max_users'      => 5,
            'features'       => ['equipment_tracking', 'maintenance_scheduling'],
        ],
        self::PLAN_PROFESSIONAL => [
            'name'           => 'Professional',
            'price_monthly'  => 199,
            'price_yearly'   => 1990,
            'max_equipment'  => 500,
            'max_users'      => 25,
            'features'       => ['equipment_tracking', 'maintenance_scheduling', 'calibration_tracking', 'reports'],
        ],
        self::PLAN_ENTERPRISE => [
            'name'           => 'Enterprise',
            'price_monthly'  => 499,
            'price_yearly'   => 4990,
            'max_equipment'  => -1, // Unlimited
            'max_users'      => -1, // Unlimited
            'features'       => ['equipment_tracking', 'maintenance_scheduling', 'calibration_tracking', 'reports', 'api_access', 'priority_support'],
        ],
    ];

    public function getCloneRelations(): array {
        return [];
    }

    public static function getTypeName($nb = 0) {
        return _n('Subscription', 'Subscriptions', $nb);
    }

    /**
     * Check if email already exists
     */
    public static function checkEmailExists($email) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => static::getTable(),
            'WHERE'  => ['email' => $email],
        ]);

        return count($iterator) > 0;
    }

    /**
     * Create a trial subscription
     */
    public static function createTrialSubscription($data) {
        global $DB;

        try {
            // Generate login username
            $login = self::generateLogin($data['email']);

            // Hash password
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);

            // Calculate trial end date
            $trial_start = date('Y-m-d H:i:s');
            $trial_end = date('Y-m-d H:i:s', strtotime("+{self::TRIAL_DAYS} days"));

            // Insert subscription
            $subscriptionId = $DB->insert(static::getTable(), [
                'hospital_name'  => $data['hospital_name'],
                'contact_name'   => $data['contact_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? '',
                'country'        => $data['country'] ?? '',
                'login'          => $login,
                'password_hash'  => $password_hash,
                'plan'           => self::PLAN_BASIC,
                'status'         => self::STATUS_TRIAL,
                'trial_start'    => $trial_start,
                'trial_end'      => $trial_end,
                'entities_id'    => -1,
                'is_active'      => 1,
            ]);

            if ($subscriptionId) {
                // Create GLPI user account
                $userId = self::createGlpiUser($data, $login);

                if ($userId) {
                    // Update subscription with user ID
                    $DB->update(static::getTable(), [
                        'users_id' => $userId,
                    ], [
                        'id' => $subscriptionId,
                    ]);

                    return [
                        'success' => true,
                        'login'   => $login,
                        'user_id' => $userId,
                    ];
                }
            }

            return [
                'success' => false,
                'message' => __('Failed to create subscription'),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate login from email
     */
    private static function generateLogin($email) {
        global $DB;

        $base_login = explode('@', $email)[0];
        $login = $base_login;
        $counter = 1;

        while (true) {
            $iterator = $DB->request([
                'SELECT' => ['id'],
                'FROM'   => static::getTable(),
                'WHERE'  => ['login' => $login],
            ]);

            if (count($iterator) == 0) {
                break;
            }

            $login = $base_login . $counter;
            $counter++;
        }

        return $login;
    }

    /**
     * Create GLPI user account
     */
    private static function createGlpiUser($data, $login) {
        global $DB;

        // Create user in glpi_users table
        $userId = $DB->insert('glpi_users', [
            'name'          => $login,
            'realname'      => $data['contact_name'],
            'email'         => $data['email'],
            'password'      => password_hash($data['password'], PASSWORD_DEFAULT),
            'passwordLastChange' => date('Y-m-d H:i:s'),
            'is_active'     => 1,
            'date_creation' => date('Y-m-d H:i:s'),
        ]);

        if ($userId) {
            // Assign super-admin profile to the first user
            $DB->insert('glpi_profils_users', [
                'users_id'    => $userId,
                'profiles_id' => 4, // Super-Admin profile ID
                'entities_id' => -1,
                'is_recursive' => 1,
            ]);

            // Create default department assignments
            self::createDefaultDepartmentAssignment($userId);
        }

        return $userId;
    }

    /**
     * Create default department assignment for new user
     */
    private static function createDefaultDepartmentAssignment($userId) {
        global $DB;

        // Assign user as admin to all departments
        $departments = new PluginHospitalcmmsCategory();
        $iterator = $departments->find(['is_active' => 1]);

        foreach ($iterator as $dept) {
            $DB->insert('glpi_plugin_hospitalcmms_user_departments', [
                'users_id'                      => $userId,
                'plugin_hospitalcmms_categories_id' => $dept['id'],
                'role'                          => PluginHospitalcmmsPermission::ROLE_ADMIN,
                'entities_id'                   => -1,
                'date_creation'                 => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Send confirmation email
     */
    public static function sendConfirmationEmail($email, $hospitalName, $login) {
        $subject = __('Welcome to Hospital CMMS - Your Account is Ready');

        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
                .button { display: inline-block; background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>🏥 Hospital CMMS</h1>
                    <p>Welcome to Your Free Trial!</p>
                </div>
                <div class='content'>
                    <h2>Hello {$hospitalName}!</h2>
                    <p>Thank you for registering for Hospital CMMS. Your 20-day free trial is now active!</p>
                    <p><strong>Your Login Credentials:</strong></p>
                    <p>Login: <strong>{$login}</strong></p>
                    <p>You can access your account at: <a href='" . $CFG_GLPI['root_doc'] . "/index.php'>" . $CFG_GLPI['root_doc'] . "/index.php</a></p>
                    <p>During your trial, you'll have full access to all features including:</p>
                    <ul>
                        <li>Medical equipment tracking</li>
                        <li>Preventive maintenance scheduling</li>
                        <li>Department-based access control</li>
                        <li>Calibration management</li>
                        <li>Real-time dashboard</li>
                    </ul>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='" . $CFG_GLPI['root_doc'] . "/index.php' class='button'>Access Your Dashboard</a>
                    </p>
                    <p>Need help getting started? Contact our support team at support@hospital-cmms.com</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Hospital CMMS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        // Send email
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Hospital CMMS <noreply@hospital-cmms.com>\r\n";

        mail($email, $subject, $message, $headers);
    }

    /**
     * Check and update expired trials
     */
    public static function checkExpiredTrials() {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'login', 'email'],
            'FROM'   => static::getTable(),
            'WHERE'  => [
                'status'    => self::STATUS_TRIAL,
                'trial_end' => ['<', date('Y-m-d H:i:s')],
            ],
        ]);

        foreach ($iterator as $subscription) {
            // Update status to expired
            $DB->update(static::getTable(), [
                'status' => self::STATUS_EXPIRED,
            ], [
                'id' => $subscription['id'],
            ]);

            // Disable user account
            $DB->update('glpi_users', [
                'is_active' => 0,
            ], [
                'name' => $subscription['login'],
            ]);

            // Send expiration email
            self::sendExpirationEmail($subscription['email']);
        }
    }

    /**
     * Send expiration email
     */
    private static function sendExpirationEmail($email) {
        $subject = __('Your Hospital CMMS Trial Has Expired');

        $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #ef4444; color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; }
                .button { display: inline-block; background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; font-weight: bold; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>⚠️ Trial Expired</h1>
                </div>
                <div class='content'>
                    <p>Your 20-day free trial of Hospital CMMS has expired.</p>
                    <p>To continue using the system, please upgrade to a paid plan.</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='" . $CFG_GLPI['root_doc'] . "/plugins/hospitalcmms/front/subscription.php' class='button'>Upgrade Now</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Hospital CMMS. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: Hospital CMMS <noreply@hospital-cmms.com>\r\n";

        mail($email, $subject, $message, $headers);
    }

    /**
     * Get subscription statistics
     */
    public static function getStats() {
        global $DB;

        $stats = [];

        // Total hospitals
        $iterator = $DB->request([
            'SELECT' => ['COUNT(*) AS total'],
            'FROM'   => static::getTable(),
            'WHERE'  => ['is_active' => 1],
        ]);
        $stats['total_hospitals'] = $iterator->current()['total'] ?? 0;

        // Total equipment
        $iterator = $DB->request([
            'SELECT' => ['COUNT(*) AS total'],
            'FROM'   => PluginHospitalcmmsMedicalEquipment::getTable(),
            'WHERE'  => ['is_deleted' => 0],
        ]);
        $stats['total_equipment'] = $iterator->current()['total'] ?? 0;

        // Total maintenance tasks
        $iterator = $DB->request([
            'SELECT' => ['COUNT(*) AS total'],
            'FROM'   => PluginHospitalcmmsMaintenanceTask::getTable(),
            'WHERE'  => ['is_active' => 1],
        ]);
        $stats['total_maintenance'] = $iterator->current()['total'] ?? 0;

        return $stats;
    }

    /**
     * Check if subscription is valid
     */
    public static function isValidSubscription($hospitalId) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['status', 'trial_end'],
            'FROM'   => static::getTable(),
            'WHERE'  => [
                'id'         => $hospitalId,
                'is_active'  => 1,
            ],
        ]);

        if (count($iterator)) {
            $subscription = $iterator->current();

            if ($subscription['status'] === self::STATUS_ACTIVE) {
                return true;
            }

            if ($subscription['status'] === self::STATUS_TRIAL) {
                return strtotime($subscription['trial_end']) > time();
            }
        }

        return false;
    }

    /**
     * Get remaining trial days
     */
    public static function getRemainingTrialDays($hospitalId) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['trial_end'],
            'FROM'   => static::getTable(),
            'WHERE'  => [
                'id'         => $hospitalId,
                'status'     => self::STATUS_TRIAL,
                'is_active'  => 1,
            ],
        ]);

        if (count($iterator)) {
            $subscription = $iterator->current();
            $trialEnd = strtotime($subscription['trial_end']);
            $now = time();
            $remaining = ($trialEnd - $now) / (60 * 60 * 24);
            return max(0, ceil($remaining));
        }

        return 0;
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
            'field'         => 'hospital_name',
            'name'          => __('Hospital Name'),
            'datatype'      => 'string',
        ];

        $tab[] = [
            'id'            => '4',
            'table'         => $this->getTable(),
            'field'         => 'contact_name',
            'name'          => __('Contact'),
            'datatype'      => 'string',
        ];

        $tab[] = [
            'id'            => '5',
            'table'         => $this->getTable(),
            'field'         => 'email',
            'name'          => __('Email'),
            'datatype'      => 'string',
        ];

        $tab[] = [
            'id'            => '6',
            'table'         => $this->getTable(),
            'field'         => 'status',
            'name'          => __('Status'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '7',
            'table'         => $this->getTable(),
            'field'         => 'plan',
            'name'          => __('Plan'),
            'datatype'      => 'dropdown',
        ];

        $tab[] = [
            'id'            => '8',
            'table'         => $this->getTable(),
            'field'         => 'trial_end',
            'name'          => __('Trial End Date'),
            'datatype'      => 'datetime',
        ];

        $tab[] = [
            'id'            => '9',
            'table'         => $this->getTable(),
            'field'         => 'date_creation',
            'name'          => __('Registration Date'),
            'datatype'      => 'datetime',
        ];

        return $tab;
    }

    public static function getIcon() {
        return "ti ti-credit-card";
    }
}
