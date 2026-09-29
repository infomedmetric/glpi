<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - ajax: equipment lookups
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Medmetriccmms\Equipment;
use GlpiPlugin\Medmetriccmms\Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Html::header_nocache();
Session::checkCentralAccess();

$action = (string) ($_REQUEST['action'] ?? '');

switch ($action) {
    case 'search':
        $term = trim((string) ($_REQUEST['term'] ?? ''));
        if (mb_strlen($term) < 2) {
            Toolbox::sendJson(['results' => []]);
        }
        global $DB;
        $like = '%' . $term . '%';
        $iterator = $DB->request([
            'FROM'  => Equipment::getTable(),
            'WHERE' => [
                'is_deleted' => 0,
                'OR' => [
                    ['name' => ['LIKE', $like]],
                    ['serial' => ['LIKE', $like]],
                    ['asset_tag' => ['LIKE', $like]],
                ],
            ],
            'ORDER' => ['name'],
            'LIMIT' => 20,
        ]);
        $results = [];
        foreach ($iterator as $row) {
            $results[] = [
                'id'     => (int) $row['id'],
                'name'   => (string) $row['name'],
                'serial' => (string) $row['serial'],
                'status' => Equipment::getStatusName((int) $row['status']),
                'text'   => trim((string) $row['name'] . ' (' . (string) $row['serial'] . ')'),
            ];
        }
        Toolbox::sendJson(['results' => $results]);

    case 'due':
        $days = max(1, min(365, (int) ($_REQUEST['days'] ?? 30)));
        $rows = [];
        foreach (Equipment::getDueForMaintenance($days) as $equipment) {
            $rows[] = [
                'id' => (int) $equipment['id'],
                'name' => (string) $equipment['name'],
                'next_maintenance' => (string) $equipment['next_maintenance'],
                'status' => Equipment::getStatusName((int) $equipment['status']),
            ];
        }
        Toolbox::sendJson(['due' => $rows, 'count' => count($rows)]);

    default:
        Toolbox::sendJson(['error' => 'unknown action']);
}
