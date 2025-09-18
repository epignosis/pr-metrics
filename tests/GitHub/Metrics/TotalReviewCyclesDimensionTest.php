<?php

// SPDX-License-Identifier: Apache-2.0
//
// Copyright 2025 Epignosis, Inc.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\TotalReviewCyclesDimension;
use TalentLMS\Metrics\GitHub\Repository\PullRequestReviewsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalReviewCyclesDimensionTest extends TestCase
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

        $dimension = new TotalReviewCyclesDimension($this->createMock(Mappings::class), $reviewsRepository);

        $this->assertEquals(2, $dimension->calculate());
    }

    /**
     * @throws HttpClientException
     * @throws Exception
     * @throws JsonException
     */
    public function testCalculateReturnsZeroWhenNoReviews(): void
    {
        $reviewsRepository = $this->createMock(PullRequestReviewsRepository::class);
        $reviewsRepository->method('collect')
            ->willReturn([]);

        $dimension = new TotalReviewCyclesDimension($this->createMock(Mappings::class), $reviewsRepository);

        $this->assertEquals(0, $dimension->calculate());
    }
}
