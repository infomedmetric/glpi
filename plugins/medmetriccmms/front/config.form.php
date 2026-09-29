<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - configuration page
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;
use GlpiPlugin\Medmetriccmms\Config;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

Session::checkRight('config', UPDATE);

if (isset($_POST['update'])) {
    Session::checkCSRF($_POST);
    Config::set($_POST);
    Event::log(0, 'config', 4, 'config', sprintf(__('%1$s updates MedMetric CMMS configuration'), $_SESSION['glpiname']));
    Session::addMessageAfterRedirect(__('Configuration successfully saved'), true, INFO);
    Html::back();
}

Html::header(__('MedMetric CMMS settings'), $_SERVER['PHP_SELF'], 'config', 'medmetriccmms');

Config::showConfigForm();

Html::footer();
