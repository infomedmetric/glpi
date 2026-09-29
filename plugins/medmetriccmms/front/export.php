<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - CSV export
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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Session::checkCentralAccess();

$kind = (int) ($_GET['reportkind'] ?? Report::KIND_WORKORDERS);
Report::exportCsv($kind, [
    'date_from' => (string) ($_GET['date_from'] ?? date('Y-m-d', strtotime('-90 days'))),
    'date_to'   => (string) ($_GET['date_to'] ?? date('Y-m-d')),
]);
