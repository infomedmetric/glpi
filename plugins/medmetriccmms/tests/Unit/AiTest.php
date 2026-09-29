<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - AI unit tests
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

use GlpiPlugin\Medmetriccmms\Ai;
use PHPUnit\Framework\TestCase;

/**
 * AI orchestration tests. The endpoint call itself is not executed here;
 * we test the configuration gate and the public API shape.
 *
 * @covers \GlpiPlugin\Medmetriccmms\Ai
 */
class AiTest extends TestCase
{
    public function testActionsConstants(): void {
        $this->assertSame('diagnose', Ai::ACTION_DIAGNOSE);
        $this->assertSame('plan', Ai::ACTION_PLAN);
        $this->assertSame('summarize', Ai::ACTION_SUMMARIZE);
    }

    public function testChatWithoutConfigurationReturnsError(): void {
        // In unit context no DB/config is available, so resolution must fail
        // gracefully instead of throwing.
        $result = Ai::chat(
            [['role' => 'user', 'content' => 'ping']],
            Ai::ACTION_DIAGNOSE
        );
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testIsConfiguredIsBoolean(): void {
        $this->assertIsBool(Ai::isConfigured());
    }

    public function testEndpointUrlBuilding(): void {
        $reflection = new \ReflectionMethod(Ai::class, 'callEndpoint');
        $this->assertTrue($reflection->isStatic());
        $this->assertTrue($reflection->isProtected());
        $params = $reflection->getParameters();
        $this->assertCount(3, $params);
        $this->assertSame('base_url', $params[0]->getName());
        $this->assertSame('api_key', $params[1]->getName());
        $this->assertSame('payload', $params[2]->getName());
    }
}
