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
