<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\CommitTeamDimension;

class CommitTeamDimensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculate(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findDeveloper')
            ->with('John Doe#john.doe@example.com')
            ->willReturn('johndoe');
        $mappings->method('findTeam')
            ->with('johndoe')
            ->willReturn('Team A');

        $dimension = new CommitTeamDimension($mappings);

        $params = [
            'committer_name' => 'John Doe',
            'committer_email' => 'john.doe@example.com',
        ];

        $this->assertEquals('Team A', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsUnknownTeamWhenDeveloperIsNotFound(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findDeveloper')
            ->willReturn(null);

        $dimension = new CommitTeamDimension($mappings);

        $params = [
            'committer_name' => 'Unknown Developer',
            'committer_email' => 'unknown@example.com',
        ];

        $this->assertEquals('Team Unknown', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsUnknownTeamWhenTeamIsNotFound(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findDeveloper')
            ->willReturn('johndoe');
        $mappings->method('findTeam')
            ->willReturn(null);

        $dimension = new CommitTeamDimension($mappings);

        $params = [
            'committer_name' => 'John Doe',
            'committer_email' => 'john.doe@example.com',
        ];

        $this->assertEquals('Team Unknown', $dimension->calculate($params));
    }
}
