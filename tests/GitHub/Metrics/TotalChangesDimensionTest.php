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
