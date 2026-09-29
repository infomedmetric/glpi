<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - ajax API integration test
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
 * Ajax API surface tests. These endpoints require an authenticated GLPI
 * session, so only file presence and structure are validated without GLPI.
 *
 * @coversNothing
 */
class ApiTest extends TestCase
{
    /**
     * Ajax endpoints required by the plugin contract.
     *
     * @return array
     */
    public static function endpointProvider(): array {
        return [
            ['equipment.php'],
            ['department.php'],
            ['workorder.php'],
            ['notification.php'],
            ['inventory.php'],
            ['analytics.php'],
            ['ai.php'],
            ['report.php'],
        ];
    }

    /**
     * @dataProvider endpointProvider
     */
    public function testAjaxEndpointExistsAndIsGuarded(string $file): void {
        $path = dirname(__DIR__, 2) . '/ajax/' . $file;
        $this->assertFileExists($path);
        $content = (string) file_get_contents($path);
        $this->assertStringContainsString("Sorry. You can't access this file directly", $content, "$file must refuse direct access");
        $this->assertStringContainsString('header_nocache', $content, "$file must disable caching");
        $this->assertStringContainsString('checkCentralAccess', $content, "$file must check permissions");
    }

    public function testFrontPagesExist(): void {
        $front_dir = dirname(__DIR__, 2) . '/front';
        $expected = [
            'config.php', 'config.form.php',
            'department.php', 'department.form.php',
            'equipment.php', 'equipment.form.php',
            'structure.php', 'structure.form.php',
            'maintenance.php', 'maintenance.form.php',
            'maintenanceplan.php', 'maintenanceplan.form.php',
            'workorder.php', 'workorder.form.php',
            'notification.php', 'notification.form.php',
            'solution.php', 'solution.form.php',
            'inventory.php', 'inventory.form.php',
            'vendor.php', 'vendor.form.php',
            'contract.php', 'contract.form.php',
            'analytics.php', 'report.php', 'export.php',
        ];
        foreach ($expected as $file) {
            $this->assertFileExists($front_dir . '/' . $file);
        }
    }
}
