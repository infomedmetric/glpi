<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - maintenance plan list
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Medmetriccmms\MaintenancePlan;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Session::checkCentralAccess();

Html::header(MaintenancePlan::getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], 'helpdesk', 'medmetriccmms');

Search::show(MaintenancePlan::class);

Html::footer();
