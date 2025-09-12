<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\UserDimension;

class UserDimensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculate(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findUser')
            ->with('12345')
            ->willReturn('johndoe');

        $dimension = new UserDimension($mappings);

        $params = [
            'id' => 1,
            'creator' => 12345,
        ];

        $this->assertEquals('johndoe', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateThrowsExceptionForUnknownUser(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findUser')
            ->willReturn(null);

        $dimension = new UserDimension($mappings);

        $params = [
            'id' => 1,
            'creator' => 12345,
        ];

        $this->expectException(RuntimeException::class);
        $dimension->calculate($params);
    }
}
