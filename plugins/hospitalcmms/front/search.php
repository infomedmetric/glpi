<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Search Front Controller
 *
 * ---------------------------------------------------------------------
 */

include("../../../inc/includes.php");

Session::checkLoginUser();

// Search for medical equipment
Search::show('PluginHospitalcmmsMedicalEquipment');
