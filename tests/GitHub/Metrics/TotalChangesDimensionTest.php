<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\TotalChangesDimension;
use TalentLMS\Metrics\GitHub\Repository\PullRequestFilesRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalChangesDimensionTest extends TestCase
{
    /**
     * @throws HttpClientException
     * @throws Exception
     * @throws JsonException
     */
    public function testCalculate(): void
    {
        $filesRepository = $this->createMock(PullRequestFilesRepository::class);
        $filesRepository->method('collect')
            ->willReturn([
                ['changes' => 10],
                ['changes' => 5],
                ['changes' => 3],
            ]);

        $dimension = new TotalChangesDimension($this->createMock(Mappings::class), $filesRepository);

        $this->assertEquals(18, $dimension->calculate());
    }

    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculateReturnsZeroWhenNoFiles(): void
    {
        $filesRepository = $this->createMock(PullRequestFilesRepository::class);
        $filesRepository->method('collect')
            ->willReturn([]);

        $dimension = new TotalChangesDimension($this->createMock(Mappings::class), $filesRepository);

        $this->assertEquals(0, $dimension->calculate());
    }
}
