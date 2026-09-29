<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - ajax: notifications
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Medmetriccmms\Notification;
use GlpiPlugin\Medmetriccmms\Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Html::header_nocache();
Session::checkCentralAccess();

global $DB;
$action = (string) ($_REQUEST['action'] ?? '');

switch ($action) {
    case 'count':
        $iterator = $DB->request([
            'COUNT' => 'c',
            'FROM'  => Notification::getTable(),
            'WHERE' => ['status' => Notification::STATUS_OPEN],
        ]);
        $count = count($iterator) ? (int) $iterator->current()['c'] : 0;
        Toolbox::sendJson(['open' => $count]);

    case 'latest':
        $limit = max(1, min(50, (int) ($_REQUEST['limit'] ?? 10)));
        $iterator = $DB->request([
            'FROM'  => Notification::getTable(),
            'ORDER' => ['date_mod DESC'],
            'LIMIT' => $limit,
        ]);
        $rows = [];
        foreach ($iterator as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'kind' => Notification::getKinds()[(int) $row['notificationkind']] ?? '',
                'status' => Notification::getStatuses()[(int) $row['status']] ?? '',
                'date' => (string) $row['date_mod'],
            ];
        }
        Toolbox::sendJson(['notifications' => $rows]);

    default:
        Toolbox::sendJson(['error' => 'unknown action']);
}
