<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Maintenance Task Front Controller
 *
 * ---------------------------------------------------------------------
 */

include("../../../inc/includes.php");

Session::checkLoginUser();

$task = new PluginHospitalCmmsMaintenanceTask();

if (isset($_POST["add"])) {
    $task->check(-1, CREATE, $_POST);
    $task->add($_POST);
    Html::back();
} elseif (isset($_POST["update"])) {
    $task->check($_POST['id'], UPDATE);
    $task->update($_POST);
    Html::back();
} elseif (isset($_POST["purge"])) {
    $task->check($_POST['id'], PURGE);
    $task->delete($_POST, 1);
    $task->redirectToList();
} elseif (isset($_POST["delete"])) {
    $task->check($_POST['id'], DELETE);
    $task->delete($_POST);
    $task->redirectToList();
} elseif (isset($_POST["restore"])) {
    $task->check($_POST['id'], PURGE);
    $task->restore($_POST);
    $task->redirectToList();
} elseif (isset($_POST["record_execution"])) {
    // Record task execution
    if (isset($_POST['task_id'])) {
        $task->recordExecution(
            $_POST['task_id'],
            $_POST['comment'] ?? '',
            $_POST['duration'] ?? 0,
            $_POST['cost'] ?? 0
        );
        Session::addMessageAfterRedirect(__('Maintenance task execution recorded.'));
    }
    Html::back();
} else {
    // Display list or form
    if (isset($_GET['id'])) {
        $params = [
            'id' => $_GET['id']
        ];
        if (isset($_GET['withtemplate']) && $_GET['withtemplate'] > 0) {
            $params['withtemplate'] = $_GET['withtemplate'];
        }
        $task->display($params);
    } else {
        $params = [];
        if (isset($_GET['start'])) {
            $params['start'] = $_GET['start'];
        }
        $task->listItems($params);
    }
}
