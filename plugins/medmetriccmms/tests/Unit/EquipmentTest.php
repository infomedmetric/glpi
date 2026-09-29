<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - Equipment unit tests
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU _n stubs; GPL v3.
 * ---------------------------------------------------------------------
 */

declare(strict_types=1);

namespace GlpiPlugin\Medmetriccmms\Tests\Unit;

use GlpiPlugin\Medmetriccmms\Equipment;
use PHPUnit\Framework\TestCase;

/**
 * Equipment helper tests (no DB required).
 *
 * @covers \GlpiPlugin\Medmetriccmms\Equipment
 */
class EquipmentTest extends TestCase
{
    public function testStatusNameForAllStatuses(): void {
        $this->assertSame('In service', Equipment::getStatusName(Equipment::STATUS_IN_SERVICE));
        $this->assertSame('Broken', Equipment::getStatusName(Equipment::STATUS_BROKEN));
        $this->assertSame('Retired', Equipment::getStatusName(Equipment::STATUS_RETired));
    }

    public function testStatusNameFallsBackToRawValue(): void {
        $this->assertSame('42', Equipment::getStatusName(42));
    }

    public function testStatusesListIsComplete(): void {
        $statuses = Equipment::getStatuses();
        $this->assertCount(5, $statuses);
        $this->assertArrayHasKey(Equipment::STATUS_IN_SERVICE, $statuses);
        $this->assertArrayHasKey(Equipment::STATUS_OUT_SERVICE, $statuses);
        $this->assertArrayHasKey(Equipment::STATUS_BROKEN, $statuses);
        $this->assertArrayHasKey(Equipment::STATUS_MAINTENANCE, $statuses);
        $this->assertArrayHasKey(Equipment::STATUS_RETired, $statuses);
    }

    public function testCriticalitiesListIsComplete(): void {
        $crits = Equipment::getCriticalities();
        $this->assertCount(4, $crits);
        $this->assertArrayHasKey(Equipment::CRITICALITY_CRITICAL, $crits);
    }
}
