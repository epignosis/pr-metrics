<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\FirstReviewedAtDimension;
use TalentLMS\Metrics\GitHub\Repository\PullRequestReviewsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class FirstReviewedAtDimensionTest extends TestCase
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
                ['state' => 'COMMENTED', 'submitted_at' => '2025-01-10T10:00:00Z'],
                ['state' => 'APPROVED', 'submitted_at' => '2025-01-11T11:00:00Z'],
                ['state' => 'CHANGES_REQUESTED', 'submitted_at' => '2025-01-12T12:00:00Z'],
            ]);

        $dimension = new FirstReviewedAtDimension($this->createMock(Mappings::class), $reviewsRepository);

        $this->assertEquals('2025-01-11', $dimension->calculate());
    }

    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculateReturnsNullWhenNoReviews(): void
    {
        $reviewsRepository = $this->createMock(PullRequestReviewsRepository::class);
        $reviewsRepository->method('collect')
            ->willReturn([]);

        $dimension = new FirstReviewedAtDimension($this->createMock(Mappings::class), $reviewsRepository);

        $this->assertNull($dimension->calculate());
    }
}
