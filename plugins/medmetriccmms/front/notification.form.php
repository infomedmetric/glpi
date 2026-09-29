<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - notification form
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;
use GlpiPlugin\Medmetriccmms\Notification;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Session::checkCentralAccess();

if (empty($_GET['id'])) {
    $_GET['id'] = 0;
}
$item = new Notification();

if (isset($_POST['add'])) {
    Session::checkCSRF($_POST);
    $item->check(-1, CREATE, $_POST);
    if ($newID = $item->add($_POST)) {
        Event::log($newID, 'notification', 4, 'helpdesk', sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['name'] ?? ''));
        Session::addMessageAfterRedirect(__('Item successfully added'), true, INFO);
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    Session::checkCSRF($_POST);
    $item->check($_POST['id'], UPDATE);
    $item->update($_POST);
    Event::log($_POST['id'], 'notification', 4, 'helpdesk', sprintf(__('%1$s updates the item %2$s'), $_SESSION['glpiname'], $item->fields['name'] ?? ''));
    Session::addMessageAfterRedirect(__('Item successfully updated'), true, INFO);
    Html::back();
} elseif (isset($_POST['ack'])) {
    Session::checkCSRF($_POST);
    $item->check($_POST['id'], UPDATE);
    $item->update([
        'id' => $_POST['id'],
        'status' => Notification::STATUS_ACK,
        'users_id_ack' => $_SESSION['glpiID'] ?? 0,
        'date_ack' => date('Y-m-d H:i:s'),
    ]);
    Event::log($_POST['id'], 'notification', 4, 'helpdesk', sprintf(__('%1$s acknowledges the item %2$s'), $_SESSION['glpiname'], $item->fields['name'] ?? ''));
    Session::addMessageAfterRedirect(__('Notification acknowledged'), true, INFO);
    Html::back();
} elseif (isset($_POST['purge'])) {
    Session::checkCSRF($_POST);
    $item->check($_POST['id'], PURGE);
    $item->delete($_POST, 1);
    Event::log($_POST['id'], 'notification', 5, 'helpdesk', sprintf(__('%1$s purges the item %2$s'), $_SESSION['glpiname'], $item->fields['name'] ?? ''));
    Html::redirect($item->getSearchURL(false));
}

$options = $_GET;
$item->display($options);

Html::footer();
