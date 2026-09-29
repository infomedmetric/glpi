<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - ajax: report data
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Medmetriccmms\Report;
use GlpiPlugin\Medmetriccmms\Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Html::header_nocache();
Session::checkCentralAccess();

$kind = (int) ($_REQUEST['reportkind'] ?? Report::KIND_WORKORDERS);
$data = Report::buildRows($kind, [
    'date_from' => (string) ($_REQUEST['date_from'] ?? date('Y-m-d', strtotime('-90 days'))),
    'date_to'   => (string) ($_REQUEST['date_to'] ?? date('Y-m-d')),
]);

Toolbox::sendJson([
    'kind' => $kind,
    'headers' => $data['headers'],
    'rows' => array_slice($data['rows'], 0, 500),
    'total' => count($data['rows']),
]);
