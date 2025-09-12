<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\EndedAtDimension;

class EndedAtDimensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculateReturnsMergedAtWhenStateIsClosedAndMergedAtIsSet(): void
    {
        $dimension = new EndedAtDimension($this->createMock(Mappings::class));

        $params = [
            'state' => 'closed',
            'merged_at' => '2025-01-10T10:00:00Z',
        ];

        $this->assertEquals('2025-01-10', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsClosedAtWhenStateIsClosedAndMergedAtIsNotSet(): void
    {
        $dimension = new EndedAtDimension($this->createMock(Mappings::class));

        $params = [
            'state' => 'closed',
            'closed_at' => '2025-01-11T10:00:00Z',
        ];

        $this->assertEquals('2025-01-11', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsNullWhenStateIsOpen(): void
    {
        $dimension = new EndedAtDimension($this->createMock(Mappings::class));

        $params = [
            'state' => 'open',
        ];

        $this->assertNull($dimension->calculate($params));
    }
}
