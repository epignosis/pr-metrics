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

namespace Tests\GitHub\Repository;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Repository\PullRequestsRepository;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class PullRequestsRepositoryTest extends TestCase
{
    /**
     * @throws HttpClientException
     * @throws Exception
     * @throws JsonException
     */
    public function testCollectMergesAndFiltersPullRequests(): void
    {
        // 1. ARRANGE
        Carbon::setTestNow(Carbon::create(2025, 2)); // Freeze time for date comparisons

        $config = $this->createMock(Config::class);
        $httpClient = $this->createMock(HttpClientInterface::class);

        $config->method('get')->willReturnMap([
            ['github.token', 'test_token'],
            ['github.ignore_users', [999]],
            ['github.ignore_labels', ['release']],
        ]);

        // Mock data for OPEN PRs
        $openPRs = json_encode([
            ['number' => 1, 'title' => 'feat: Valid Open PR', 'draft' => false, 'user' => ['id' => 123], 'labels' => [], 'created_at' => '2025-01-15', 'closed_at' => null, 'merged_at' => null],
            ['number' => 2, 'title' => 'fix: Draft PR', 'draft' => true, 'user' => ['id' => 123], 'labels' => [], 'created_at' => '2025-01-15', 'closed_at' => null, 'merged_at' => null],
            ['number' => 3, 'title' => 'feat: Ignored User PR', 'draft' => false, 'user' => ['id' => 999], 'labels' => [], 'created_at' => '2025-01-15', 'closed_at' => null, 'merged_at' => null],
            ['number' => 4, 'title' => 'chore: Ignored Label PR', 'draft' => false, 'user' => ['id' => 123], 'labels' => [['name' => 'release']], 'created_at' => '2025-01-15', 'closed_at' => null, 'merged_at' => null],
        ], JSON_THROW_ON_ERROR);

        // Mock data for CLOSED PRs
        $closedPRs = json_encode([
            ['number' => 5, 'title' => 'fix: Valid Closed PR', 'draft' => false, 'user' => ['id' => 456], 'labels' => [], 'created_at' => '2025-01-10', 'closed_at' => null, 'merged_at' => '2025-01-12'],
            ['number' => 6, 'title' => 'ref: Too Old Closed PR', 'draft' => false, 'user' => ['id' => 456], 'labels' => [], 'created_at' => '2024-12-01', 'closed_at' => '2024-12-03', 'merged_at' => null], // Should be filtered by date
        ], JSON_THROW_ON_ERROR);

        $httpClient->method('send')->willReturnCallback(function (string $method, string $uri) use ($openPRs, $closedPRs) {
            if (str_contains($uri, 'state=open')) {
                return new Response(200, [], $openPRs);
            }
            if (str_contains($uri, 'state=closed')) {
                return new Response(200, [], $closedPRs);
            }
            return new Response(404, [], '');
        });

        $repository = new PullRequestsRepository($config, $httpClient);

        // 2. ACT
        $results = $repository->collect(['repo' => 'test/repo']);

        // 3. ASSERT
        $this->assertCount(2, $results, 'Should only include the two valid PRs');

        $resultNumbers = array_map(fn ($pr) => $pr['id'], $results);
        $this->assertContains(1, $resultNumbers, "Valid Open PR should be present");
        $this->assertContains(5, $resultNumbers, "Valid Closed PR should be present");

        $this->assertEquals('open', $results[0]['state']);
        $this->assertEquals('closed', $results[1]['state']);

        $this->assertEquals([
            'id' => 5,
            'creator' => 456,
            'state' => 'closed',
            'title' => 'fix: Valid Closed PR',
            'created_at' => '2025-01-10',
            'closed_at' => null,
            'merged_at' => '2025-01-12',
        ], $results[1]);

        Carbon::setTestNow(); // Unfreeze time
    }
}
