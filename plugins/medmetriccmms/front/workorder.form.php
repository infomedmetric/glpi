<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - work order form
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
use GlpiPlugin\Medmetriccmms\Ai;
use GlpiPlugin\Medmetriccmms\Equipment;
use GlpiPlugin\Medmetriccmms\Inventory;
use GlpiPlugin\Medmetriccmms\Notification;
use GlpiPlugin\Medmetriccmms\Solution;
use GlpiPlugin\Medmetriccmms\Toolbox;
use GlpiPlugin\Medmetriccmms\WorkOrder;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Session::checkCentralAccess();

if (empty($_GET['id'])) {
    $_GET['id'] = 0;
}
$item = new WorkOrder();

if (isset($_POST['add'])) {
    Session::checkCSRF($_POST);
    $item->check(-1, CREATE, $_POST);
    if ($newID = $item->add($_POST)) {
        Event::log($newID, 'workorder', 4, 'helpdesk', sprintf(__('%1$s adds the item %2$s'), $_SESSION['glpiname'], $_POST['name'] ?? ''));
        Session::addMessageAfterRedirect(__('Item successfully added'), true, INFO);
    }
    Html::back();
} elseif (isset($_POST['update'])) {
    Session::checkCSRF($_POST);
    $item->check($_POST['id'], UPDATE);
    $item->update($_POST);
    Event::log($_POST['id'], 'workorder', 4, 'helpdesk', sprintf(__('%1$s updates the item %2$s'), $_SESSION['glpiname'], $item->fields['name'] ?? ''));
    Session::addMessageAfterRedirect(__('Item successfully updated'), true, INFO);

    // Capture knowledge when closing with a solution
    if ((int) ($_POST['status'] ?? 0) === WorkOrder::STATUS_DONE
        && trim((string) ($_POST['solution_description'] ?? '')) !== '') {
        Solution::createFromWorkOrder($item);
    }
    Html::back();
} elseif (isset($_POST['purge'])) {
    Session::checkCSRF($_POST);
    $item->check($_POST['id'], PURGE);
    $item->delete($_POST, 1);
    Event::log($_POST['id'], 'workorder', 5, 'helpdesk', sprintf(__('%1$s purges the item %2$s'), $_SESSION['glpiname'], $item->fields['name'] ?? ''));
    Html::redirect($item->getSearchURL(false));
} elseif (isset($_POST['consume_part'])) {
    Session::checkCSRF($_POST);
    $item->check((int) ($_POST['plugin_medmetriccmms_workorders_id'] ?? 0), UPDATE);
    Inventory::consumeForWorkOrder(
        (int) ($_POST['plugin_medmetriccmms_workorders_id'] ?? 0),
        (int) ($_POST['plugin_medmetriccmms_inventories_id'] ?? 0),
        (int) ($_POST['quantity'] ?? 1)
    );
    Session::addMessageAfterRedirect(__('Spare part consumed'), true, INFO);
    Html::back();
} elseif (isset($_POST['ai_diagnose'])) {
    Session::checkCSRF($_POST);
    $item->check((int) ($_POST['plugin_medmetriccmms_equipments_id'] ?? 0), READ);
    $result = Ai::diagnose(
        (int) ($_POST['plugin_medmetriccmms_equipments_id'] ?? 0),
        (string) ($_POST['symptoms'] ?? '')
    );
    if ($result['success']) {
        Session::addMessageAfterRedirect(Toolbox::truncate($result['content'], 400), true, INFO);
    } else {
        Session::addMessageAfterRedirect($result['error'] ?? __('AI request failed'), false, ERROR);
    }
    Html::back();
}

// AI panel + parts panel on existing items
if (isset($_GET['id']) && (int) $_GET['id'] > 0) {
    if ($item->getFromDB((int) $_GET['id'])) {
        echo "<div class='medmetric-grid-2col'>";

        echo "<div class='medmetric-card'>";
        echo "<h3 class='medmetric-card__title'>" . htmlescape(__('AI assistance')) . "</h3>";
        if (Ai::isConfigured()) {
            echo "<form method='post' action='" . htmlescape($item->getFormURLWithID((int) $item->fields['id'])) . "'>";
            echo Html::hidden('plugin_medmetriccmms_equipments_id', ['value' => $item->fields['plugin_medmetriccmms_equipments_id'] ?? 0]);
            echo Html::hidden('ai_diagnose', ['value' => '1']);
            echo "<label class='medmetric-label'>" . htmlescape(__('Describe the symptoms')) . "</label>";
            Html::textarea(['name' => 'symptoms', 'value' => '', 'rows' => 3]);
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo "<button type='submit' class='btn btn-primary mt-2'>" . htmlescape(__('Ask AI diagnosis')) . "</button>";
            echo "</form>";
        } else {
            echo "<p class='medmetric-muted'>" . htmlescape(__('Configure an AI provider key in MedMetric CMMS settings to enable diagnosis and summaries.')) . "</p>";
        }
        echo "</div>";

        echo "<div class='medmetric-card'>";
        echo "<h3 class='medmetric-card__title'>" . htmlescape(__('Consumed spare parts')) . "</h3>";
        $parts = $item->getParts();
        if ($parts === []) {
            echo "<p class='medmetric-muted'>" . htmlescape(__('No spare parts consumed yet.')) . "</p>";
        } else {
            echo "<table class='medmetric-table'><tr><th>" . htmlescape(__('Name')) . "</th><th>" . htmlescape(__('Quantity')) . "</th><th>" . htmlescape(__('Unit price')) . "</th></tr>";
            foreach ($parts as $part) {
                printf(
                    "<tr><td>%s</td><td>%d</td><td>%.2f</td></tr>",
                    htmlescape((string) $part['name']),
                    (int) $part['quantity'],
                    (float) $part['price_unit']
                );
            }
            echo "</table>";
        }
        echo "<form method='post' action='" . htmlescape($item->getFormURLWithID((int) $item->fields['id'])) . "' class='mt-2'>";
        echo Html::hidden('plugin_medmetriccmms_workorders_id', ['value' => $item->fields['id'] ?? 0]);
        echo Html::hidden('consume_part', ['value' => '1']);
        Inventory::dropdown(['name' => 'plugin_medmetriccmms_inventories_id', 'value' => 0]);
        Html::input('quantity', ['value' => 1, 'size' => 4]);
        echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
        echo "<button type='submit' class='btn btn-secondary mt-2'>" . htmlescape(__('Consume part')) . "</button>";
        echo "</form>";
        echo "</div>";

        echo "</div>";
    }
}

$options = $_GET;
$item->display($options);

Html::footer();
