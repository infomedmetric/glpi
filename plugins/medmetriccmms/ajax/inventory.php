<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - ajax: inventory / stock
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Medmetriccmms\Inventory;
use GlpiPlugin\Medmetriccmms\Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Html::header_nocache();
Session::checkCentralAccess();

global $DB;
$action = (string) ($_REQUEST['action'] ?? '');

switch ($action) {
    case 'low_stock':
        $rows = [];
        foreach (Inventory::getLowStock() as $row) {
            $rows[] = [
                'id' => (int) $row['id'],
                'name' => (string) $row['name'],
                'ref' => (string) $row['ref'],
                'stock' => (int) $row['stock_current'],
                'min' => (int) $row['stock_min'],
            ];
        }
        Toolbox::sendJson(['items' => $rows, 'count' => count($rows)]);

    case 'stock':
        $inventories_id = (int) ($_REQUEST['inventories_id'] ?? 0);
        $part = new Inventory();
        if (!$part->getFromDB($inventories_id)) {
            Toolbox::sendJson(['error' => 'not found']);
        }
        Toolbox::sendJson([
            'id' => (int) $part->fields['id'],
            'name' => (string) $part->fields['name'],
            'stock' => (int) $part->fields['stock_current'],
            'price' => (float) $part->fields['price_unit'],
        ]);

    default:
        Toolbox::sendJson(['error' => 'unknown action']);
}
