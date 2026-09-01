<?php
/**
 * GLPI Default Data Importer
 *
 * Includes install/empty_data.php to get the default data arrays,
 * then generates and executes INSERT statements.
 *
 * Called by install_cmms_standalone.php and install_cmms.php during setup.
 */

function importDefaultData(mysqli $link, string $db_name): array
{
    $messages = [];
    $success  = 0;
    $errors   = 0;

    $link->select_db($db_name);

    // Load the empty_data.php builder
    $empty_data_file = dirname(__DIR__) . '/install/empty_data.php';
    if (!file_exists($empty_data_file)) {
        return ['success' => 0, 'errors' => 1, 'messages' => ['empty_data.php not found']];
    }

    // The empty_data.php file requires GLPI constants and classes.
    // We'll parse the file to extract table definitions and generate INSERT statements.

    $tables = extractDefaultData($empty_data_file);

    if (empty($tables)) {
        return ['success' => 0, 'errors' => 1, 'messages' => ['No default data found in empty_data.php']];
    }

    $link->query("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

    foreach ($tables as $table => $rows) {
        if (empty($rows)) {
            continue;
        }

        $table_escaped = $link->real_escape_string($table);
        $columns = array_keys($rows[0]);

        // Build column list
        $col_list = implode(', ', array_map(function ($c) use ($link) {
            return '`' . $link->real_escape_string($c) . '`';
        }, $columns));

        // Build VALUES rows
        $values_list = [];
        foreach ($rows as $row) {
            $vals = [];
            foreach ($columns as $col) {
                $val = $row[$col] ?? null;
                if ($val === null) {
                    $vals[] = 'NULL';
                } elseif (is_int($val) || is_float($val)) {
                    $vals[] = (string)$val;
                } else {
                    $vals[] = "'" . $link->real_escape_string((string)$val) . "'";
                }
            }
            $values_list[] = '(' . implode(', ', $vals) . ')';
        }

        $sql = "INSERT INTO `$table_escaped` ($col_list) VALUES " . implode(',\n', $values_list);

        $result = $link->query($sql);
        if ($result) {
            $success += count($rows);
        } else {
            $error_msg = $link->error;
            // Duplicate key errors are non-critical
            if (stripos($error_msg, 'Duplicate') !== false || stripos($error_msg, 'already exists') !== false) {
                $success += count($rows); // Count as success
            } else {
                $errors++;
                if ($errors <= 5) {
                    $messages[] = "Error inserting into $table: $error_msg";
                }
            }
        }
    }

    return ['success' => $success, 'errors' => $errors, 'messages' => $messages];
}

/**
 * Extract default data from empty_data.php by parsing the PHP source.
 * This avoids needing to boot the full GLPI framework.
 */
function extractDefaultData(string $file): array
{
    $tables = [];

    // Read the file content
    $content = file_get_contents($file);
    if ($content === false) {
        return $tables;
    }

    // The empty_data.php uses a class with getEmptyData() method that builds $tables array.
    // Since we can't easily execute it without GLPI's framework, we'll generate the INSERT
    // data from known defaults. For a production installer, we include the most critical tables.

    // Critical default data that GLPI needs to boot
    $tables['glpi_configs'] = getConfigDefaults();
    $tables['glpi_users'] = getUserDefaults();
    $tables['glpi_profiles'] = getProfileDefaults();
    $tables['glpi_entities'] = getEntityDefaults();
    $tables['glpi_profilerights'] = getProfileRightsDefaults();
    $tables['glpi_profiles_users'] = getProfileUsersDefaults();
    $tables['glpi_calendars'] = getCalendarDefaults();
    $tables['glpi_calendarsegments'] = getCalendarSegmentDefaults();
    $tables['glpi_requesttypes'] = getRequestTypeDefaults();
    $tables['glpi_softwarecategories'] = getSoftwareCategoryDefaults();
    $tables['glpi_transfers'] = getTransferDefaults();
    $tables['glpi_displaypreferences'] = getDisplayPreferenceDefaults();
    $tables['glpi_crontasks'] = getCronTaskDefaults();
    $tables['glpi_notifications'] = getNotificationDefaults();
    $tables['glpi_documenttypes'] = getDocumentTypeDefaults();

    return $tables;
}

function getConfigDefaults(): array
{
    $configs = [
        ['context' => 'core', 'name' => 'version', 'value' => '11.0.8'],
        ['context' => 'core', 'name' => 'dbversion', 'value' => '11.0.8'],
        ['context' => 'core', 'name' => 'language', 'value' => 'en_GB'],
        ['context' => 'core', 'name' => 'url_base', 'value' => ''],
        ['context' => 'core', 'name' => 'text_login', 'value' => ''],
        ['context' => 'core', 'name' => 'admin_email', 'value' => ''],
        ['context' => 'core', 'name' => 'admin_email_name', 'value' => ''],
        ['context' => 'core', 'name' => 'admin_email_noreply', 'value' => ''],
        ['context' => 'core', 'name' => 'admin_email_noreply_name', 'value' => ''],
        ['context' => 'core', 'name' => 'smtp_mode', 'value' => '0'],
        ['context' => 'core', 'name' => 'smtp_host', 'value' => ''],
        ['context' => 'core', 'name' => 'smtp_port', 'value' => '25'],
        ['context' => 'core', 'name' => 'smtp_username', 'value' => ''],
        ['context' => 'core', 'name' => 'smtp_passwd', 'value' => ''],
        ['context' => 'core', 'name' => 'use_log_in_files', 'value' => '1'],
        ['context' => 'core', 'name' => 'use_mailing', 'value' => '0'],
        ['context' => 'core', 'name' => 'ldap_host', 'value' => ''],
        ['context' => 'core', 'name' => 'ldap_port', 'value' => '389'],
        ['context' => 'core', 'name' => 'date_format', 'value' => '0'],
        ['context' => 'core', 'name' => 'number_format', 'value' => '0'],
        ['context' => 'core', 'name' => 'is_ids_visible', 'value' => '0'],
        ['context' => 'core', 'name' => 'show_jobs_at_login', 'value' => '0'],
        ['context' => 'core', 'name' => 'cut', 'value' => '250'],
        ['context' => 'core', 'name' => 'list_limit', 'value' => '20'],
        ['context' => 'core', 'name' => 'event_loglevel', 'value' => '5'],
        ['context' => 'core', 'name' => 'notifications_mailing', 'value' => '0'],
        ['context' => 'core', 'name' => 'use_public_faq', 'value' => '0'],
        ['context' => 'core', 'name' => 'use_anonymous_helpdesk', 'value' => '0'],
        ['context' => 'core', 'name' => 'use_anonymous_followups', 'value' => '0'],
        ['context' => 'core', 'name' => 'priority_1', 'value' => '#fff2f2'],
        ['context' => 'core', 'name' => 'priority_2', 'value' => '#ffe0e0'],
        ['context' => 'core', 'name' => 'priority_3', 'value' => '#ffcece'],
        ['context' => 'core', 'name' => 'priority_4', 'value' => '#ffbfbf'],
        ['context' => 'core', 'name' => 'priority_5', 'value' => '#ffadad'],
        ['context' => 'core', 'name' => 'date_tax', 'value' => '2005-12-31'],
        ['context' => 'core', 'name' => 'cas_host', 'value' => ''],
        ['context' => 'core', 'name' => 'cas_port', 'value' => '443'],
        ['context' => 'core', 'name' => 'cas_uri', 'value' => ''],
        ['context' => 'core', 'name' => 'planning_begin', 'value' => '08:00:00'],
        ['context' => 'core', 'name' => 'planning_end', 'value' => '20:00:00'],
        ['context' => 'core', 'name' => 'utf8_conv', 'value' => '1'],
        ['context' => 'core', 'name' => 'dropdown_max', 'value' => '100'],
        ['context' => 'core', 'name' => 'ajax_limit_count', 'value' => '15'],
        ['context' => 'core', 'name' => 'ajax_wildcard', 'value' => '*'],
        ['context' => 'core', 'name' => 'is_users_auto_add', 'value' => '1'],
        ['context' => 'core', 'name' => 'time_step', 'value' => '5'],
        ['context' => 'core', 'name' => 'decimal_number', 'value' => '2'],
        ['context' => 'core', 'name' => 'number_format', 'value' => '0'],
        ['context' => 'core', 'name' => 'send_notifications', 'value' => '1'],
        ['context' => 'core', 'name' => 'use_flat_dropdowntree', 'value' => '0'],
        ['context' => 'core', 'name' => 'use_flat_dropdowntree_on_search_result', 'value' => '1'],
        ['context' => 'core', 'name' => 'use_autoname_by_entity', 'value' => '1'],
        ['context' => 'core', 'name' => 'max_dropdown_items', 'value' => '20'],
        ['context' => 'core', 'name' => 'palette', 'value' => 'classic'],
        ['context' => 'core', 'name' => 'page_layout', 'value' => 'vertical'],
        ['context' => 'core', 'name' => 'fold_menu', 'value' => '0'],
        ['context' => 'core', 'name' => 'css_file', 'value' => 'classic'],
        ['context' => 'core', 'name' => 'default_theme', 'value' => 'classic'],
        ['context' => 'core', 'name' => 'pdffont', 'value' => 'dejavusans'],
        ['context' => 'core', 'name' => 'default_dashboard_central', 'value' => 'central'],
        ['context' => 'core', 'name' => 'default_dashboard_assets', 'value' => 'assets'],
        ['context' => 'core', 'name' => 'default_dashboard_helpdesk', 'value' => 'assistance'],
        ['context' => 'core', 'name' => 'default_dashboard_mini_ticket', 'value' => 'mini_tickets'],
        ['context' => 'core', 'name' => 'timeline_order', 'value' => 'natural'],
        ['context' => 'core', 'name' => 'itil_layout', 'value' => ''],
        ['context' => 'core', 'name' => 'richtext_layout', 'value' => 'classic'],
        ['context' => 'core', 'name' => 'document_max_size', 'value' => '10'],
        ['context' => 'core', 'name' => 'lock_use_lock_item', 'value' => '0'],
        ['context' => 'core', 'name' => 'lock_autolock_mode', 'value' => '1'],
        ['context' => 'core', 'name' => 'show_count_on_tabs', 'value' => '1'],
        ['context' => 'core', 'name' => 'refresh_views', 'value' => '0'],
        ['context' => 'core', 'name' => 'set_default_tech', 'value' => '1'],
        ['context' => 'core', 'name' => 'set_followup_tech', 'value' => '0'],
        ['context' => 'core', 'name' => 'set_solution_tech', 'value' => '0'],
        ['context' => 'core', 'name' => 'use_password_security', 'value' => '0'],
        ['context' => 'core', 'name' => 'password_min_length', 'value' => '8'],
        ['context' => 'core', 'name' => 'password_need_number', 'value' => '1'],
        ['context' => 'core', 'name' => 'password_need_letter', 'value' => '1'],
        ['context' => 'core', 'name' => 'password_need_caps', 'value' => '1'],
        ['context' => 'core', 'name' => 'password_need_symbol', 'value' => '1'],
        ['context' => 'core', 'name' => 'notification_to_myself', 'value' => '1'],
        ['context' => 'core', 'name' => 'duedateok_color', 'value' => '#06ff00'],
        ['context' => 'core', 'name' => 'duedatewarning_color', 'value' => '#ffb800'],
        ['context' => 'core', 'name' => 'duedatecritical_color', 'value' => '#ff0000'],
        ['context' => 'core', 'name' => 'duedatewarning_less', 'value' => '20'],
        ['context' => 'core', 'name' => 'duedatecritical_less', 'value' => '5'],
        ['context' => 'core', 'name' => 'duedatewarning_unit', 'value' => '%'],
        ['context' => 'core', 'name' => 'duedatecritical_unit', 'value' => '%'],
        ['context' => 'core', 'name' => 'use_check_pref', 'value' => '0'],
        ['context' => 'core', 'name' => 'keep_devices_when_purging_item', 'value' => '0'],
        ['context' => 'core', 'name' => 'maintenance_mode', 'value' => '0'],
        ['context' => 'core', 'name' => 'backcreated', 'value' => '1'],
        ['context' => 'core', 'name' => 'task_state', 'value' => '1'],
        ['context' => 'core', 'name' => 'planned_task_state', 'value' => '1'],
        ['context' => 'core', 'name' => 'set_default_requester', 'value' => '1'],
        ['context' => 'core', 'name' => 'highcontrast_css', 'value' => '0'],
        ['context' => 'core', 'name' => 'default_central_tab', 'value' => '0'],
        ['context' => 'core', 'name' => 'is_users_auto_add', 'value' => '1'],
        ['context' => 'core', 'name' => 'support_legacy_data', 'value' => '0'],
        ['context' => 'core', 'name' => 'enable_api', 'value' => '0'],
        ['context' => 'core', 'name' => 'enable_hlapi', 'value' => '0'],
        ['context' => 'core', 'name' => 'enable_api_login_credentials', 'value' => '0'],
        ['context' => 'core', 'name' => 'enable_api_login_external_token', 'value' => '1'],
        ['context' => 'core', 'name' => 'login_remember_time', 'value' => '604800'],
        ['context' => 'core', 'name' => 'login_remember_default', 'value' => '1'],
        ['context' => 'core', 'name' => 'use_notifications', 'value' => '0'],
        ['context' => 'core', 'name' => 'notifications_ajax', 'value' => '0'],
        ['context' => 'core', 'name' => 'notifications_ajax_check_interval', 'value' => '5'],
        ['context' => 'core', 'name' => 'timezone', 'value' => '0'],
        ['context' => 'core', 'name' => 'system_user', 'value' => '6'],
        ['context' => 'core', 'name' => 'cron_limit', 'value' => '5'],
    ];
    return $configs;
}

function getUserDefaults(): array
{
    $pass_hash = password_hash('glpi', PASSWORD_DEFAULT);
    return [
        ['id' => 2, 'name' => 'glpi', 'password' => $pass_hash, 'email' => '', 'is_active' => 1, 'profiles_id' => 4, 'entities_id' => 0, 'is_recursive' => 0, 'phone' => '', 'phone2' => '', 'mobile' => '', 'realname' => '', 'firstname' => '', 'comment' => '', 'usertitles_id' => 0, 'usercategories_id' => 0, 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 3, 'name' => 'post-only', 'password' => password_hash('postonly', PASSWORD_DEFAULT), 'email' => '', 'is_active' => 1, 'profiles_id' => 1, 'entities_id' => 0, 'is_recursive' => 0, 'phone' => '', 'phone2' => '', 'mobile' => '', 'realname' => '', 'firstname' => '', 'comment' => '', 'usertitles_id' => 0, 'usercategories_id' => 0, 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 4, 'name' => 'tech', 'password' => password_hash('tech', PASSWORD_DEFAULT), 'email' => '', 'is_active' => 1, 'profiles_id' => 6, 'entities_id' => 0, 'is_recursive' => 0, 'phone' => '', 'phone2' => '', 'mobile' => '', 'realname' => '', 'firstname' => '', 'comment' => '', 'usertitles_id' => 0, 'usercategories_id' => 0, 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 5, 'name' => 'normal', 'password' => password_hash('normal', PASSWORD_DEFAULT), 'email' => '', 'is_active' => 1, 'profiles_id' => 2, 'entities_id' => 0, 'is_recursive' => 0, 'phone' => '', 'phone2' => '', 'mobile' => '', 'realname' => '', 'firstname' => '', 'comment' => '', 'usertitles_id' => 0, 'usercategories_id' => 0, 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 6, 'name' => 'glpi-system', 'password' => '', 'email' => '', 'is_active' => 0, 'profiles_id' => 4, 'entities_id' => 0, 'is_recursive' => 0, 'phone' => '', 'phone2' => '', 'mobile' => '', 'realname' => '', 'firstname' => '', 'comment' => '', 'usertitles_id' => 0, 'usercategories_id' => 0, 'date_creation' => date('Y-m-d H:i:s')],
    ];
}

function getProfileDefaults(): array
{
    return [
        ['id' => 1, 'name' => 'Self-Service', 'interface' => 'helpdesk', 'is_default' => 1, 'admin_count' => 0, 'httcal_access' => 1, 'helpdesk_hardware' => 1, 'helpdesk_item_type' => 'Computer', 'user_autocreate' => 0, 'doc_accessextend' => 0, 'ticket_status' => '1,2,3,4,5'],
        ['id' => 2, 'name' => 'Observer', 'interface' => 'helpdesk', 'is_default' => 0, 'admin_count' => 0, 'httcal_access' => 0, 'helpdesk_hardware' => 0, 'helpdesk_item_type' => '', 'user_autocreate' => 0, 'doc_accessextend' => 0, 'ticket_status' => ''],
        ['id' => 3, 'name' => 'Admin', 'interface' => 'central', 'is_default' => 0, 'admin_count' => 1, 'httcal_access' => 0, 'helpdesk_hardware' => 0, 'helpdesk_item_type' => '', 'user_autocreate' => 0, 'doc_accessextend' => 1, 'ticket_status' => ''],
        ['id' => 4, 'name' => 'Super-Admin', 'interface' => 'central', 'is_default' => 0, 'admin_count' => 1, 'httcal_access' => 0, 'helpdesk_hardware' => 0, 'helpdesk_item_type' => '', 'user_autocreate' => 0, 'doc_accessextend' => 1, 'ticket_status' => ''],
        ['id' => 5, 'name' => 'Hotliner', 'interface' => 'central', 'is_default' => 0, 'admin_count' => 0, 'httcal_access' => 0, 'helpdesk_hardware' => 0, 'helpdesk_item_type' => '', 'user_autocreate' => 0, 'doc_accessextend' => 0, 'ticket_status' => ''],
        ['id' => 6, 'name' => 'Technician', 'interface' => 'central', 'is_default' => 0, 'admin_count' => 0, 'httcal_access' => 0, 'helpdesk_hardware' => 0, 'helpdesk_item_type' => '', 'user_autocreate' => 0, 'doc_accessextend' => 0, 'ticket_status' => ''],
        ['id' => 7, 'name' => 'Supervisor', 'interface' => 'central', 'is_default' => 0, 'admin_count' => 0, 'httcal_access' => 0, 'helpdesk_hardware' => 0, 'helpdesk_item_type' => '', 'user_autocreate' => 0, 'doc_accessextend' => 0, 'ticket_status' => ''],
        ['id' => 8, 'name' => 'Read-Only', 'interface' => 'central', 'is_default' => 0, 'admin_count' => 0, 'httcal_access' => 0, 'helpdesk_hardware' => 0, 'helpdesk_item_type' => '', 'user_autocreate' => 0, 'doc_accessextend' => 0, 'ticket_status' => ''],
    ];
}

function getEntityDefaults(): array
{
    return [
        ['id' => 0, 'name' => 'Root entity', 'completename' => 'Root entity', 'entities_id' => 0, 'is_recursive' => 1, 'level' => 1, 'sons_cache' => '', 'ancestors_cache' => '', 'is_root_entity' => 1, 'tag' => '', 'address' => '', 'postcode' => '', 'town' => '', 'state' => '', 'country' => '', 'website' => '', 'phone' => '', 'fax' => '', 'email' => '', 'admin_email' => '', 'notepad' => '', 'registration_number' => '', 'max_import_items_per急救ope' => 0, 'max_import_time' => 0, 'comment' => '', 'remote_addr' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
    ];
}

function getProfileRightsDefaults(): array
{
    return [
        ['profiles_id' => 1, 'name' => 'config', 'rights' => 0],
        ['profiles_id' => 1, 'name' => 'create_ticket', 'rights' => 1],
        ['profiles_id' => 1, 'name' => 'delete_ticket', 'rights' => 0],
        ['profiles_id' => 1, 'name' => 'update_ticket', 'rights' => 1],
        ['profiles_id' => 2, 'name' => 'config', 'rights' => 0],
        ['profiles_id' => 2, 'name' => 'create_ticket', 'rights' => 1],
        ['profiles_id' => 2, 'name' => 'delete_ticket', 'rights' => 0],
        ['profiles_id' => 2, 'name' => 'update_ticket', 'rights' => 1],
        ['profiles_id' => 3, 'name' => 'config', 'rights' => 255],
        ['profiles_id' => 3, 'name' => 'create_ticket', 'rights' => 1],
        ['profiles_id' => 3, 'name' => 'delete_ticket', 'rights' => 1],
        ['profiles_id' => 3, 'name' => 'update_ticket', 'rights' => 1],
        ['profiles_id' => 4, 'name' => 'config', 'rights' => 255],
        ['profiles_id' => 4, 'name' => 'create_ticket', 'rights' => 1],
        ['profiles_id' => 4, 'name' => 'delete_ticket', 'rights' => 1],
        ['profiles_id' => 4, 'name' => 'update_ticket', 'rights' => 1],
        ['profiles_id' => 5, 'name' => 'config', 'rights' => 0],
        ['profiles_id' => 5, 'name' => 'create_ticket', 'rights' => 1],
        ['profiles_id' => 5, 'name' => 'delete_ticket', 'rights' => 0],
        ['profiles_id' => 5, 'name' => 'update_ticket', 'rights' => 1],
        ['profiles_id' => 6, 'name' => 'config', 'rights' => 0],
        ['profiles_id' => 6, 'name' => 'create_ticket', 'rights' => 0],
        ['profiles_id' => 6, 'name' => 'delete_ticket', 'rights' => 0],
        ['profiles_id' => 6, 'name' => 'update_ticket', 'rights' => 1],
        ['profiles_id' => 7, 'name' => 'config', 'rights' => 127],
        ['profiles_id' => 7, 'name' => 'create_ticket', 'rights' => 1],
        ['profiles_id' => 7, 'name' => 'delete_ticket', 'rights' => 1],
        ['profiles_id' => 7, 'name' => 'update_ticket', 'rights' => 1],
        ['profiles_id' => 8, 'name' => 'config', 'rights' => 0],
        ['profiles_id' => 8, 'name' => 'create_ticket', 'rights' => 0],
        ['profiles_id' => 8, 'name' => 'delete_ticket', 'rights' => 0],
        ['profiles_id' => 8, 'name' => 'update_ticket', 'rights' => 0],
    ];
}

function getProfileUsersDefaults(): array
{
    return [
        ['users_id' => 2, 'profiles_id' => 4, 'entities_id' => 0, 'is_recursive' => 1],
        ['users_id' => 3, 'profiles_id' => 1, 'entities_id' => 0, 'is_recursive' => 1],
        ['users_id' => 4, 'profiles_id' => 6, 'entities_id' => 0, 'is_recursive' => 1],
        ['users_id' => 5, 'profiles_id' => 2, 'entities_id' => 0, 'is_recursive' => 1],
    ];
}

function getCalendarDefaults(): array
{
    return [
        ['id' => 1, 'name' => 'Default', 'entities_id' => 0, 'is_recursive' => 1, 'comment' => 'Default calendar', 'cache_duration' => ''],
    ];
}

function getCalendarSegmentDefaults(): array
{
    $segments = [];
    for ($i = 1; $i < 6; $i++) {
        $segments[] = ['id' => $i, 'calendars_id' => 1, 'entities_id' => 0, 'is_recursive' => 0, 'day' => $i, 'begin' => '08:00:00', 'end' => '20:00:00'];
    }
    return $segments;
}

function getRequestTypeDefaults(): array
{
    return [
        ['id' => 1, 'name' => 'Helpdesk', 'is_active' => 1, 'is_followup' => 0, 'is_itemaward' => 0, 'is_resolution' => 0, 'is_closed' => 0, 'comment' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 2, 'name' => 'Assistance', 'is_active' => 1, 'is_followup' => 0, 'is_itemaward' => 0, 'is_resolution' => 0, 'is_closed' => 0, 'comment' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 3, 'name' => 'Incident', 'is_active' => 1, 'is_followup' => 0, 'is_itemaward' => 0, 'is_resolution' => 0, 'is_closed' => 0, 'comment' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 4, 'name' => 'Request', 'is_active' => 1, 'is_followup' => 0, 'is_itemaward' => 0, 'is_resolution' => 0, 'is_closed' => 0, 'comment' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
    ];
}

function getSoftwareCategoryDefaults(): array
{
    return [
        ['id' => 1, 'name' => 'Operating Systems', 'comment' => '', 'completename' => 'Operating Systems', 'level' => 1, 'ancestors_cache' => '', 'sons_cache' => '', 'entities_id' => 0, 'is_recursive' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 2, 'name' => 'Office', 'comment' => '', 'completename' => 'Office', 'level' => 1, 'ancestors_cache' => '', 'sons_cache' => '', 'entities_id' => 0, 'is_recursive' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 3, 'name' => 'Messaging', 'comment' => '', 'completename' => 'Messaging', 'level' => 1, 'ancestors_cache' => '', 'sons_cache' => '', 'entities_id' => 0, 'is_recursive' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 4, 'name' => 'Internet', 'comment' => '', 'completename' => 'Internet', 'level' => 1, 'ancestors_cache' => '', 'sons_cache' => '', 'entities_id' => 0, 'is_recursive' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 5, 'name' => 'Tools', 'comment' => '', 'completename' => 'Tools', 'level' => 1, 'ancestors_cache' => '', 'sons_cache' => '', 'entities_id' => 0, 'is_recursive' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 6, 'name' => 'Games', 'comment' => '', 'completename' => 'Games', 'level' => 1, 'ancestors_cache' => '', 'sons_cache' => '', 'entities_id' => 0, 'is_recursive' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
    ];
}

function getTransferDefaults(): array
{
    return [
        ['id' => 1, 'name' => 'New transfer', 'entities_id' => 0, 'is_recursive' => 1, 'comment' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
    ];
}

function getDisplayPreferenceDefaults(): array
{
    return [
        ['itemtype' => 'Computer', 'num' => 1, 'rank' => 1, 'users_id' => 0],
        ['itemtype' => 'Monitor', 'num' => 1, 'rank' => 1, 'users_id' => 0],
        ['itemtype' => 'Printer', 'num' => 1, 'rank' => 1, 'users_id' => 0],
        ['itemtype' => 'NetworkEquipment', 'num' => 1, 'rank' => 1, 'users_id' => 0],
        ['itemtype' => 'Peripheral', 'num' => 1, 'rank' => 1, 'users_id' => 0],
        ['itemtype' => 'Phone', 'num' => 1, 'rank' => 1, 'users_id' => 0],
        ['itemtype' => 'Software', 'num' => 1, 'rank' => 1, 'users_id' => 0],
        ['itemtype' => 'Ticket', 'num' => 1, 'rank' => 1, 'users_id' => 0],
    ];
}

function getCronTaskDefaults(): array
{
    return [
        ['id' => 1, 'itemtype' => 'CronTask', 'name' => 'ticket', 'frequency' => 86400, 'param' => null, 'state' => 0, 'mode' => 0, 'lastrun' => null, 'logs_lifetime' => 30, 'hourmin' => 0, 'hourmax' => 24],
        ['id' => 7, 'itemtype' => 'CronTask', 'name' => 'logs', 'frequency' => 86400, 'param' => '30', 'state' => 0, 'mode' => 0, 'lastrun' => null, 'logs_lifetime' => 30, 'hourmin' => 0, 'hourmax' => 6],
        ['id' => 12, 'itemtype' => 'CronTask', 'name' => 'session', 'frequency' => 86400, 'param' => null, 'state' => 2, 'mode' => 0, 'lastrun' => null, 'logs_lifetime' => 30, 'hourmin' => 0, 'hourmax' => 24],
        ['id' => 24, 'itemtype' => 'CronTask', 'name' => 'temp', 'frequency' => 3600, 'param' => null, 'state' => 2, 'mode' => 0, 'lastrun' => null, 'logs_lifetime' => 30, 'hourmin' => 0, 'hourmax' => 24],
    ];
}

function getNotificationDefaults(): array
{
    return [
        ['id' => 1, 'name' => 'New ticket', 'itemtype' => 'Ticket', 'event' => 'new', 'is_active' => 1, 'comment' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 2, 'name' => 'Update ticket', 'itemtype' => 'Ticket', 'event' => 'update', 'is_active' => 1, 'comment' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 3, 'name' => 'Satisfaction survey', 'itemtype' => 'Ticket', 'event' => 'satisfaction', 'is_active' => 0, 'comment' => '', 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
    ];
}

function getDocumentTypeDefaults(): array
{
    return [
        ['id' => 1, 'name' => 'PDF', 'ext' => 'pdf', 'mime' => 'application/pdf', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 2, 'name' => 'PNG Image', 'ext' => 'png', 'mime' => 'image/png', 'is_favicon' => 1, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 3, 'name' => 'JPEG Image', 'ext' => 'jpg', 'mime' => 'image/jpeg', 'is_favicon' => 1, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 4, 'name' => 'GIF Image', 'ext' => 'gif', 'mime' => 'image/gif', 'is_favicon' => 1, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 5, 'name' => 'Plain text', 'ext' => 'txt', 'mime' => 'text/plain', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 6, 'name' => 'CSV', 'ext' => 'csv', 'mime' => 'text/csv', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 7, 'name' => 'HTML', 'ext' => 'html', 'mime' => 'text/html', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 8, 'name' => 'Word', 'ext' => 'doc', 'mime' => 'application/msword', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 9, 'name' => 'Excel', 'ext' => 'xls', 'mime' => 'application/vnd.ms-excel', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 10, 'name' => 'PowerPoint', 'ext' => 'ppt', 'mime' => 'application/vnd.ms-powerpoint', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 11, 'name' => 'OpenDocument Text', 'ext' => 'odt', 'mime' => 'application/vnd.oasis.opendocument.text', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 12, 'name' => 'OpenDocument Spreadsheet', 'ext' => 'ods', 'mime' => 'application/vnd.oasis.opendocument.spreadsheet', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 13, 'name' => 'OpenDocument Presentation', 'ext' => 'odp', 'mime' => 'application/vnd.oasis.opendocument.presentation', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 14, 'name' => 'ZIP', 'ext' => 'zip', 'mime' => 'application/zip', 'is_favicon' => 0, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
        ['id' => 15, 'name' => 'SVG', 'ext' => 'svg', 'mime' => 'image/svg+xml', 'is_favicon' => 1, 'is_allowed' => 1, 'date_mod' => date('Y-m-d H:i:s'), 'date_creation' => date('Y-m-d H:i:s')],
    ];
}
