<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - report page
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

Html::header(Report::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'tools', 'medmetriccmms');

$kind = (int) ($_GET['reportkind'] ?? Report::KIND_WORKORDERS);
$date_from = (string) ($_GET['date_from'] ?? date('Y-m-d', strtotime('-90 days')));
$date_to = (string) ($_GET['date_to'] ?? date('Y-m-d'));

echo "<div class='medmetric-card'>";
echo "<h2 class='medmetric-card__title'>" . htmlescape(__('Build a report')) . "</h2>";
echo "<form method='get' action='report.php'>";
echo "<table class='tab_cadre_fixe medmetric-table'>";

echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Report type')) . "</td><td>";
Html::select('reportkind', Report::getKinds(), ['value' => $kind, 'display' => true]);
echo "</td><td></td><td></td></tr>";

echo "<tr class='tab_bg_1'><td>" . htmlescape(__('From')) . "</td><td>";
Html::showDateField('date_from', ['value' => $date_from]);
echo "</td><td>" . htmlescape(__('To')) . "</td><td>";
Html::showDateField('date_to', ['value' => $date_to]);
echo "</td></tr>";

echo "<tr class='tab_bg_1'><td colspan='2'>";
echo "<button type='submit' class='btn btn-primary'>" . htmlescape(__('Generate')) . "</button>";
echo "</td><td colspan='2'>";
$export_url = 'export.php?' . http_build_query(['reportkind' => $kind, 'date_from' => $date_from, 'date_to' => $date_to]);
echo "<a class='btn btn-secondary' href='" . htmlescape($export_url) . "'>" . htmlescape(__('Export CSV')) . "</a>";
echo "</td></tr>";

echo "</table></form></div>";

$data = Report::buildRows($kind, ['date_from' => $date_from, 'date_to' => $date_to]);
echo "<div class='medmetric-card'>";
printf(
    "<h3 class='medmetric-card__title'>%s (%d %s)</h3>",
    htmlescape(Report::getKinds()[$kind] ?? __('Report')),
    count($data['rows']),
    htmlescape(__('rows'))
);
if ($data['rows'] === []) {
    echo "<p class='medmetric-muted'>" . htmlescape(__('No data for the selected period.')) . "</p>";
} else {
    echo "<table class='tab_cadre_fixehov medmetric-table'><tr>";
    foreach ($data['headers'] as $header) {
        echo "<th>" . htmlescape($header) . "</th>";
    }
    echo "</tr>";
    foreach (array_slice($data['rows'], 0, 200) as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlescape($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    if (count($data['rows']) > 200) {
        echo "<p class='medmetric-muted'>" . htmlescape(__('Showing first 200 rows - use CSV export for the full set.')) . "</p>";
    }
}
echo "</div>";

Html::footer();
