<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - test bootstrap
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', dirname(__DIR__, 3));
}
if (!defined('GLPI_VERSION')) {
    define('GLPI_VERSION', '11.0.9-dev');
}
if (!defined('PLUGIN_MEDMETRICCMMS_VERSION')) {
    define('PLUGIN_MEDMETRICCMMS_VERSION', '1.0.2');
}
if (!defined('PLUGIN_MEDMETRICCMMS_MIN_GLPI')) {
    define('PLUGIN_MEDMETRICCMMS_MIN_GLPI', '11.0.0');
}
if (!defined('PLUGIN_MEDMETRICCMMS_MIN_PHP')) {
    define('PLUGIN_MEDMETRICCMMS_MIN_PHP', '8.2');
}

// ---------------------------------------------------------------------------
// Minimal GLPI function/class stubs so unit tests run without a full install.
// Integration tests (tests/Integration) are skipped unless GLPI is bootable.
// ---------------------------------------------------------------------------
if (!function_exists('__')) {
    function __($msg, $domain = 'glpi') {
        return $msg;
    }
}
if (!function_exists('_n')) {
    function _n($sing, $plural, $nb, $domain = 'glpi') {
        return $nb === 1 ? $sing : $plural;
    }
}
if (!function_exists('_x')) {
    function _x($context, $msg, $domain = 'glpi') {
        return $msg;
    }
}
if (!function_exists('htmlescape')) {
    function htmlescape($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('getSonsOf')) {
    function getSonsOf($table, $id) {
        return [$id];
    }
}
if (!function_exists('getTableForItemType')) {
    function getTableForItemType($itemtype) {
        $parts = explode('\\', $itemtype);
        $short = end($parts);
        $short = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $short));
        return 'glpi_' . str_replace('_plugin', '_plugin', $short) . 's';
    }
}
if (!class_exists('CommonDBTM')) {
    class_alias(stdClass::class, 'CommonDBTM');
}
if (!class_exists('CommonDropdown')) {
    class_alias(stdClass::class, 'CommonDropdown');
}
if (!class_exists('CronTask')) {
    class_alias(stdClass::class, 'CronTask');
}

// Load plugin classes needed by unit tests. Classes extending CommonDBTM
// still load fine because CommonDBTM is aliased above.
$inc_dir = __DIR__ . '/../inc';
$classes = [
    'toolbox', 'config', 'log', 'aimodel', 'ai',
    'equipment', 'equipmenttype', 'equipmentmodel',
    'department', 'structure', 'maintenance', 'maintenanceplan',
    'workorder', 'notification', 'solution', 'inventory',
    'vendor', 'contract', 'analytics', 'report',
];
foreach ($classes as $file) {
    $path = $inc_dir . '/' . $file . '.class.php';
    if (file_exists($path)) {
        require_once $path;
    }
}
