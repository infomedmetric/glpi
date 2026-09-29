<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - menu registration helpers
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Medmetriccmms;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Menu: centralizes plugin menu wiring used by setup.php.
 */
class Menu
{
    /**
     * Items added to the assets sector.
     *
     * @return array
     */
    public static function assets(): array {
        return [
            Equipment::class,
            Department::class,
            Structure::class,
            Inventory::class,
        ];
    }

    /**
     * Items added to the helpdesk sector.
     *
     * @return array
     */
    public static function helpdesk(): array {
        return [
            WorkOrder::class,
            Maintenance::class,
            MaintenancePlan::class,
            Notification::class,
        ];
    }

    /**
     * Items added to the management sector.
     *
     * @return array
     */
    public static function management(): array {
        return [
            Vendor::class,
            Contract::class,
            Solution::class,
        ];
    }

    /**
     * Items added to the tools sector.
     *
     * @return array
     */
    public static function tools(): array {
        return [
            Report::class,
            Analytics::class,
        ];
    }

    /**
     * Direct page links used by custom widgets/tabs.
     *
     * @return array
     */
    public static function getPages(): array {
        return [
            'equipment'        => '/plugins/medmetriccmms/front/equipment.php',
            'department'       => '/plugins/medmetriccmms/front/department.php',
            'structure'        => '/plugins/medmetriccmms/front/structure.php',
            'inventory'        => '/plugins/medmetriccmms/front/inventory.php',
            'workorder'        => '/plugins/medmetriccmms/front/workorder.php',
            'maintenance'      => '/plugins/medmetriccmms/front/maintenance.php',
            'maintenanceplan'  => '/plugins/medmetriccmms/front/maintenanceplan.php',
            'notification'     => '/plugins/medmetriccmms/front/notification.php',
            'solution'         => '/plugins/medmetriccmms/front/solution.php',
            'vendor'           => '/plugins/medmetriccmms/front/vendor.php',
            'contract'         => '/plugins/medmetriccmms/front/contract.php',
            'analytics'        => '/plugins/medmetriccmms/front/analytics.php',
            'report'           => '/plugins/medmetriccmms/front/report.php',
            'config'           => '/plugins/medmetriccmms/front/config.form.php',
        ];
    }
}
