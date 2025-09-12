<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\TeamDimension;

class TeamDimensionTest extends TestCase
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
        $mappings->method('findTeam')
            ->with('johndoe')
            ->willReturn('Team A');

        $dimension = new TeamDimension($mappings);

        $params = [
            'creator' => 12345,
        ];

        $this->assertEquals('Team A', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsUnknownTeamWhenDeveloperIsNotFound(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findUser')
            ->willReturn(null);

        $dimension = new TeamDimension($mappings);

        $params = [
            'creator' => 12345,
        ];

        $this->assertEquals('Team Unknown', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsUnknownTeamWhenTeamIsNotFound(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findUser')
            ->willReturn('johndoe');
        $mappings->method('findTeam')
            ->willReturn(null);

        $dimension = new TeamDimension($mappings);

        $params = [
            'creator' => 12345,
        ];

        $this->assertEquals('Team Unknown', $dimension->calculate($params));
    }
}
