<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - ajax: AI actions
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use GlpiPlugin\Medmetriccmms\Ai;
use GlpiPlugin\Medmetriccmms\Toolbox;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Html::header_nocache();
Session::checkCentralAccess();

$action = (string) ($_REQUEST['action'] ?? '');

switch ($action) {
    case 'status':
        Toolbox::sendJson(['configured' => Ai::isConfigured()]);

    case 'diagnose':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Toolbox::sendJson(['success' => false, 'error' => 'POST required']);
        }
        Session::checkCSRF($_REQUEST);
        $result = Ai::diagnose(
            (int) ($_REQUEST['equipments_id'] ?? 0),
            trim((string) ($_REQUEST['symptoms'] ?? ''))
        );
        Toolbox::sendJson($result);

    case 'plan':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Toolbox::sendJson(['success' => false, 'error' => 'POST required']);
        }
        Session::checkCSRF($_REQUEST);
        $result = Ai::suggestPlan(
            (int) ($_REQUEST['equipmenttypes_id'] ?? 0),
            trim((string) ($_REQUEST['usage_profile'] ?? ''))
        );
        Toolbox::sendJson($result);

    case 'summarize':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Toolbox::sendJson(['success' => false, 'error' => 'POST required']);
        }
        Session::checkCSRF($_REQUEST);
        $result = Ai::summarizeWorkOrder((int) ($_REQUEST['workorders_id'] ?? 0));
        Toolbox::sendJson($result);

    default:
        Toolbox::sendJson(['success' => false, 'error' => 'unknown action']);
}
