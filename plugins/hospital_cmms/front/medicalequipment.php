<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Medical Equipment Front Controller
 *
 * ---------------------------------------------------------------------
 */

include("../../../inc/includes.php");

Session::checkLoginUser();

$equipment = new PluginHospitalCmmsMedicalEquipment();

if (isset($_POST["add"])) {
    $equipment->check(-1, CREATE, $_POST);
    $equipment->add($_POST);
    Html::back();
} elseif (isset($_POST["update"])) {
    $equipment->check($_POST['id'], UPDATE);
    $equipment->update($_POST);
    Html::back();
} elseif (isset($_POST["purge"])) {
    $equipment->check($_POST['id'], PURGE);
    $equipment->delete($_POST, 1);
    $equipment->redirectToList();
} elseif (isset($_POST["delete"])) {
    $equipment->check($_POST['id'], DELETE);
    $equipment->delete($_POST);
    $equipment->redirectToList();
} elseif (isset($_POST["restore"])) {
    $equipment->check($_POST['id'], PURGE);
    $equipment->restore($_POST);
    $equipment->redirectToList();
} elseif (isset($_POST["deletedirect"])) {
    $equipment->check($_POST['id'], PURGE);
    $equipment->delete($_POST, 1);
    $equipment->redirectToList();
} else {
    // Display list or form
    if (isset($_GET['id'])) {
        // Show equipment form
        $params = [
            'id' => $_GET['id']
        ];
        if (isset($_GET['withtemplate']) && $_GET['withtemplate'] > 0) {
            $params['withtemplate'] = $_GET['withtemplate'];
        }
        // Use generic display
        $equipment->display($params);
    } else {
        // Show equipment list
        $params = [];
        if (isset($_GET['itemtype'])) {
            $params['itemtype'] = $_GET['itemtype'];
        }
        if (isset($_GET['start'])) {
            $params['start'] = $_GET['start'];
        }
        $equipment->listItems($params);
    }
}
