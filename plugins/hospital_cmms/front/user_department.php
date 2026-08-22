<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - User Department Assignment Front Controller
 *
 * ---------------------------------------------------------------------
 */

include("../../../inc/includes.php");

Session::checkLoginUser();

if (isset($_POST["add_user"])) {
    // Add user to department
    $userId = $_POST['users_id'];
    $departmentId = $_POST['plugin_hospital_cmms_categories_id'];
    $role = $_POST['role'];

    PluginHospitalCmmsPermission::addUserToDepartment($userId, $departmentId, $role);
    Session::addMessageAfterRedirect(__('User added to department successfully.'));
    Html::back();

} elseif (isset($_POST["remove_user"])) {
    // Remove user from department
    $userId = $_POST['users_id'];
    $departmentId = $_POST['plugin_hospital_cmms_categories_id'];

    PluginHospitalCmmsPermission::removeUserFromDepartment($userId, $departmentId);
    Session::addMessageAfterRedirect(__('User removed from department successfully.'));
    Html::back();

} elseif (isset($_POST["update_role"])) {
    // Update user's role in department
    $userId = $_POST['users_id'];
    $departmentId = $_POST['plugin_hospital_cmms_categories_id'];
    $role = $_POST['role'];

    PluginHospitalCmmsPermission::addUserToDepartment($userId, $departmentId, $role);
    Session::addMessageAfterRedirect(__('User role updated successfully.'));
    Html::back();

} else {
    // Display user-department management form
    $departmentId = $_GET['department_id'] ?? 0;

    if ($departmentId > 0) {
        $department = new PluginHospitalCmmsCategory();
        $department->getFromDB($departmentId);

        echo "<div class='center'>\n";
        echo "<h2>" . sprintf(__('Department: %s'), htmlescape($department->fields['name'])) . "</h2>\n";
        echo "<h3>" . __('Department Members') . "</h3>\n";

        // Display current members
        echo "<table class='tab_cadre_fixe'>\n";
        echo "<tr>\n";
        echo "<th>" . __('User') . "</th>\n";
        echo "<th>" . __('Role') . "</th>\n";
        echo "<th>" . __('Actions') . "</th>\n";
        echo "</tr>\n";

        // Get users in this department
        $users = PluginHospitalCmmsPermission::getDepartmentUsers($departmentId);
        foreach ($users as $user) {
            echo "<tr>\n";
            echo "<td>" . htmlescape($user['username']) . "</td>\n";
            echo "<td>" . PluginHospitalCmmsPermission::getRoleName($user['role']) . "</td>\n";
            echo "<td>\n";

            // Update role form
            echo "<form method='post' style='display: inline;'>\n";
            echo "<input type='hidden' name='users_id' value='" . $user['id'] . "'>\n";
            echo "<input type='hidden' name='plugin_hospital_cmms_categories_id' value='" . $departmentId . "'>\n";
            echo "<select name='role'>\n";
            foreach (PluginHospitalCmmsPermission::getRoles() as $key => $label) {
                echo "<option value='" . $key . "' " . ($user['role'] === $key ? 'selected' : '') . ">" . $label . "</option>\n";
            }
            echo "</select>\n";
            echo "<input type='submit' name='update_role' value='" . __('Update') . "' class='btn btn-sm btn-primary'>\n";
            echo "</form>\n";

            // Remove form
            echo "<form method='post' style='display: inline;'>\n";
            echo "<input type='hidden' name='users_id' value='" . $user['id'] . "'>\n";
            echo "<input type='hidden' name='plugin_hospital_cmms_categories_id' value='" . $departmentId . "'>\n";
            echo "<input type='submit' name='remove_user' value='" . __('Remove') . "' class='btn btn-sm btn-danger' onclick=\"return confirm('" . __('Are you sure?') . "');\">\n";
            echo "</form>\n";

            echo "</td>\n";
            echo "</tr>\n";
        }

        echo "</table>\n";

        // Add user form
        echo "<h3>" . __('Add User to Department') . "</h3>\n";
        echo "<form method='post'>\n";
        echo "<input type='hidden' name='plugin_hospital_cmms_categories_id' value='" . $departmentId . "'>\n";

        echo "<table class='tab_cadre_fixe'>\n";
        echo "<tr>\n";
        echo "<td class='center'>" . __('User') . "</td>\n";
        echo "<td class='center'>\n";
        User::dropdown(['name' => 'users_id', 'right' => 'all', 'entity' => -1]);
        echo "</td>\n";
        echo "</tr>\n";

        echo "<tr>\n";
        echo "<td class='center'>" . __('Role') . "</td>\n";
        echo "<td class='center'>\n";
        echo "<select name='role'>\n";
        foreach (PluginHospitalCmmsPermission::getRoles() as $key => $label) {
            echo "<option value='" . $key . "'>" . $label . "</option>\n";
        }
        echo "</select>\n";
        echo "</td>\n";
        echo "</tr>\n";

        echo "<tr>\n";
        echo "<td colspan='2' class='center'>\n";
        echo "<input type='submit' name='add_user' value='" . __('Add User') . "' class='btn btn-primary'>\n";
        echo "</td>\n";
        echo "</tr>\n";
        echo "</table>\n";

        echo "</form>\n";
        echo "</div>\n";
    } else {
        // Show department selection
        echo "<div class='center'>\n";
        echo "<h2>" . __('Select Department to Manage') . "</h2>\n";

        $departments = new PluginHospitalCmmsCategory();
        $departments->dropdown([
            'name'   => 'department_id',
            'entity' => -1,
            'condition' => ['is_active' => 1],
        ]);

        echo "<br><br>\n";
        echo "<button onclick=\"window.location='user_department.php?department_id=' + document.querySelector('select[name=department_id]').value\" class='btn btn-primary'>" . __('Manage Department') . "</button>\n";
        echo "</div>\n";
    }
}
