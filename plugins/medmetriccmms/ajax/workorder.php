<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - ajax: work order data
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Medmetriccmms\Analytics;
use GlpiPlugin\Medmetriccmms\Toolbox;
use GlpiPlugin\Medmetriccmms\WorkOrder;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Html::header_nocache();
Session::checkCentralAccess();

global $DB;
$action = (string) ($_REQUEST['action'] ?? '');

switch ($action) {
    case 'by_status':
        $by_status = Analytics::getWorkOrdersByStatus();
        $out = [];
        foreach (WorkOrder::getStatuses() as $status => $label) {
            $out[] = ['status' => $status, 'label' => $label, 'count' => $by_status[$status] ?? 0];
        }
        Toolbox::sendJson(['statuses' => $out]);

    case 'list_for_equipment':
        $equipments_id = (int) ($_REQUEST['equipments_id'] ?? 0);
        $iterator = $DB->request([
            'FROM'  => WorkOrder::getTable(),
            'WHERE' => ['plugin_medmetriccmms_equipments_id' => $equipments_id],
            'ORDER' => ['date DESC'],
            'LIMIT' => 50,
        ]);
        $rows = [];
        foreach ($iterator as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'name' => (string) $row['name'],
                'status' => WorkOrder::getStatuses()[(int) $row['status']] ?? '',
                'date' => (string) $row['date'],
            ];
        }
        Toolbox::sendJson(['workorders' => $rows]);

    default:
        Toolbox::sendJson(['error' => 'unknown action']);
}
