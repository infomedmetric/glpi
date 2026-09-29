<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - install integration test
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

namespace GlpiPlugin\Medmetriccmms\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * Install integration test. Requires a bootable GLPI (DB configured);
 * skipped automatically in plain unit runs.
 *
 * @coversNothing
 */
class InstallTest extends TestCase
{
    public static function setUpBeforeClass(): void {
        if (!defined('GLPI_ROOT') || !is_file(GLPI_ROOT . '/inc/includes.php') || !class_exists('DBmysql', false)) {
            self::markTestSkipped('Requires a bootable GLPI instance with a configured database.');
        }
    }

    public function testInstallSqlFileExistsAndIsNotEmpty(): void {
        $file = dirname(__DIR__, 2) . '/sql/install.sql';
        $this->assertFileExists($file);
        $content = (string) file_get_contents($file);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `glpi_plugin_medmetriccmms_equipments`', $content);
        $this->assertStringContainsString('glpi_plugin_medmetriccmms_workorders', $content);
    }

    public function testUninstallSqlDropsAllPluginTables(): void {
        $file = dirname(__DIR__, 2) . '/sql/uninstall.sql';
        $content = (string) file_get_contents($file);
        $expected = [
            'glpi_plugin_medmetriccmms_equipments',
            'glpi_plugin_medmetriccmms_workorders',
            'glpi_plugin_medmetriccmms_maintenances',
            'glpi_plugin_medmetriccmms_maintenanceplans',
            'glpi_plugin_medmetriccmms_departments',
            'glpi_plugin_medmetriccmms_structures',
            'glpi_plugin_medmetriccmms_notifications',
            'glpi_plugin_medmetriccmms_solutions',
            'glpi_plugin_medmetriccmms_inventories',
            'glpi_plugin_medmetriccmms_vendors',
            'glpi_plugin_medmetriccmms_contracts',
            'glpi_plugin_medmetriccmms_aimodels',
            'glpi_plugin_medmetriccmms_logs',
            'glpi_plugin_medmetriccmms_reports',
        ];
        foreach ($expected as $table) {
            $this->assertStringContainsString("DROP TABLE IF EXISTS `$table`", $content, "$table must be dropped");
        }
    }

    public function testUpgradeFilesExist(): void {
        $dir = dirname(__DIR__, 2) . '/sql/upgrade';
        $this->assertFileExists($dir . '/1.0.0_to_1.0.1.sql');
        $this->assertFileExists($dir . '/1.0.1_to_1.0.2.sql');
        $this->assertFileExists($dir . '/1.0.2_to_1.0.3.sql');
    }
}
