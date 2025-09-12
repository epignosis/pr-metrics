<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\TotalCommitsDimension;
use TalentLMS\Metrics\GitHub\Repository\PullRequestCommitsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalCommitsDimensionTest extends TestCase
{
    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculate(): void
    {
        $commitsRepository = $this->createMock(PullRequestCommitsRepository::class);
        $commitsRepository->method('collect')
            ->willReturn([
                ['sha' => '123'],
                ['sha' => '456'],
                ['sha' => '789'],
            ]);

        $dimension = new TotalCommitsDimension($this->createMock(Mappings::class), $commitsRepository);

        $this->assertEquals(3, $dimension->calculate());
    }

    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculateReturnsZeroWhenNoCommits(): void
    {
        $commitsRepository = $this->createMock(PullRequestCommitsRepository::class);
        $commitsRepository->method('collect')
            ->willReturn([]);

        $dimension = new TotalCommitsDimension($this->createMock(Mappings::class), $commitsRepository);

        $this->assertEquals(0, $dimension->calculate());
    }
}
