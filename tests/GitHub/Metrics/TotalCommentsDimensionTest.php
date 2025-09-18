<?php

// SPDX-License-Identifier: Apache-2.0
//
// Copyright 2025 Epignosis LLC
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
