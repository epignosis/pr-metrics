<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\TotalCommentsDimension;
use TalentLMS\Metrics\GitHub\Repository\PullRequestCommentsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalCommentsDimensionTest extends TestCase
{
    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculate(): void
    {
        $commentsRepository = $this->createMock(PullRequestCommentsRepository::class);
        $commentsRepository->method('collect')
            ->willReturn([
                ['id' => 1],
                ['id' => 2],
                ['id' => 3],
            ]);

        $dimension = new TotalCommentsDimension($this->createMock(Mappings::class), $commentsRepository);

        $this->assertEquals(3, $dimension->calculate());
    }

    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculateReturnsZeroWhenNoComments(): void
    {
        $commentsRepository = $this->createMock(PullRequestCommentsRepository::class);
        $commentsRepository->method('collect')
            ->willReturn([]);

        $dimension = new TotalCommentsDimension($this->createMock(Mappings::class), $commentsRepository);

        $this->assertEquals(0, $dimension->calculate());
    }
}
