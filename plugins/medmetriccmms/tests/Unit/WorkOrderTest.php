<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - WorkOrder unit tests
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

namespace GlpiPlugin\Medmetriccmms\Tests\Unit;

use GlpiPlugin\Medmetriccmms\Toolbox;
use GlpiPlugin\Medmetriccmms\WorkOrder;
use PHPUnit\Framework\TestCase;

/**
 * WorkOrder + Toolbox helper tests (no DB required).
 *
 * @covers \GlpiPlugin\Medmetriccmms\WorkOrder
 */
class WorkOrderTest extends TestCase
{
    public function testStatusesCount(): void {
        $this->assertCount(7, WorkOrder::getStatuses());
    }

    public function testKindsCount(): void {
        $this->assertCount(4, WorkOrder::getKinds());
    }

    public function testToolboxBadgeClassMapping(): void {
        $this->assertSame('medmetric-badge--new', Toolbox::statusBadgeClass(WorkOrder::STATUS_NEW));
        $this->assertSame('medmetric-badge--progress', Toolbox::statusBadgeClass(WorkOrder::STATUS_IN_PROGRESS));
        $this->assertSame('medmetric-badge--done', Toolbox::statusBadgeClass(WorkOrder::STATUS_DONE));
        $this->assertSame('medmetric-badge--cancelled', Toolbox::statusBadgeClass(WorkOrder::STATUS_CANCELLED));
    }

    public function testToolboxTruncate(): void {
        $long = str_repeat('a', 200);
        $this->assertSame(120, mb_strlen(Toolbox::truncate($long, 120)));
        $this->assertSame('short', Toolbox::truncate('short'));
    }

    public function testToolboxFormatDuration(): void {
        $this->assertStringContainsString('minute', Toolbox::formatDuration(30));
        $this->assertStringContainsString('hour', Toolbox::formatDuration(90));
        $this->assertStringContainsString('day', Toolbox::formatDuration(60 * 24 * 2));
    }

    public function testToolboxGenerateCodeUsesPrefix(): void {
        // No DB in unit context: generateCode relies on $DB so we only
        // assert the class exists and the method is static-callable signature-wise.
        $reflection = new \ReflectionMethod(Toolbox::class, 'generateCode');
        $this->assertTrue($reflection->isStatic());
        $this->assertSame(['string', 'string', 'string'], array_map(
            static fn($p) => (string) $p->getType(),
            $reflection->getParameters()
        ));
    }
}
