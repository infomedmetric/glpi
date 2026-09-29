<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - ajax: department data
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Medmetriccmms\Department;
use GlpiPlugin\Medmetriccmms\Equipment;
use GlpiPlugin\Medmetriccmms\Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Html::header_nocache();
Session::checkCentralAccess();

global $DB;
$action = (string) ($_REQUEST['action'] ?? '');

switch ($action) {
    case 'equipment':
        $departments_id = (int) ($_REQUEST['departments_id'] ?? 0);
        $iterator = $DB->request([
            'FROM'  => Equipment::getTable(),
            'WHERE' => [
                'plugin_medmetriccmms_departments_id' => $departments_id,
                'is_deleted' => 0,
            ],
            'ORDER' => ['name'],
        ]);
        $results = [];
        foreach ($iterator as $row) {
            $results[] = [
                'id'     => (int) $row['id'],
                'name'   => (string) $row['name'],
                'status' => Equipment::getStatusName((int) $row['status']),
            ];
        }
        Toolbox::sendJson(['results' => $results]);

    case 'counts':
        $iterator = $DB->request([
            'SELECT' => ['d.id', 'd.name', 'COUNT' => 'c'],
            'FROM'   => Department::getTable() . ' AS d',
            'LEFT JOIN' => [
                Equipment::getTable() . ' AS e' => [
                    'ON' => ['d' => 'id', 'e' => 'plugin_medmetriccmms_departments_id'],
                ],
            ],
            'WHERE'  => ['e.is_deleted' => 0],
            'GROUPBY' => ['d.id', 'd.name'],
            'ORDER'  => ['c DESC'],
        ]);
        $results = [];
        foreach ($iterator as $row) {
            $results[] = ['id' => (int) $row['id'], 'name' => (string) $row['name'], 'count' => (int) $row['c']];
        }
        Toolbox::sendJson(['counts' => $results]);

    default:
        Toolbox::sendJson(['error' => 'unknown action']);
}
