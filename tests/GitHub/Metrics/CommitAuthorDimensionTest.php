<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\CommitAuthorDimension;

class CommitAuthorDimensionTest extends TestCase
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

        $dimension = new CommitAuthorDimension($mappings);

        $params = [
            'sha' => '123456',
            'committer_name' => 'John Doe',
            'committer_email' => 'john.doe@example.com',
        ];

        $this->assertEquals('johndoe', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateThrowsExceptionForUnknownDeveloper(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findDeveloper')
            ->willReturn(null);

        $dimension = new CommitAuthorDimension($mappings);

        $params = [
            'sha' => '123456',
            'committer_name' => 'Unknown Developer',
            'committer_email' => 'unknown@example.com',
        ];

        $this->expectException(RuntimeException::class);
        $dimension->calculate($params);
    }
}
