<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\TotalReviewsDimension;
use TalentLMS\Metrics\GitHub\Repository\PullRequestReviewsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalReviewsDimensionTest extends TestCase
{
    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculate(): void
    {
        $reviewsRepository = $this->createMock(PullRequestReviewsRepository::class);
        $reviewsRepository->method('collect')
            ->willReturn([
                ['state' => 'COMMENTED'],
                ['state' => 'APPROVED'],
                ['state' => 'CHANGES_REQUESTED'],
            ]);

        $dimension = new TotalReviewsDimension($this->createMock(Mappings::class), $reviewsRepository);

        $this->assertEquals(2, $dimension->calculate());
    }

    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculateReturnsZeroWhenNoReviews(): void
    {
        $reviewsRepository = $this->createMock(PullRequestReviewsRepository::class);
        $reviewsRepository->method('collect')
            ->willReturn([]);

        $dimension = new TotalReviewsDimension($this->createMock(Mappings::class), $reviewsRepository);

        $this->assertEquals(0, $dimension->calculate());
    }
}
