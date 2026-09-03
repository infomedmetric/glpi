<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Department Front Controller
 *
 * ---------------------------------------------------------------------
 */

include("../../../inc/includes.php");

Session::checkLoginUser();

$dropdown = new PluginHospitalcmmsCategory();

include(GLPI_ROOT . "/front/dropdown.common.php");
