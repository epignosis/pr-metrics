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

use GuzzleHttp\Psr7\Response;
use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Repository\PullRequestReviewsRepository;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class PullRequestReviewsRepositoryTest extends TestCase
{
    /**
     * @throws HttpClientException
     * @throws Exception
     * @throws JsonException
     */
    public function testCollectHandlesPaginationAndSkipsPending(): void
    {
        // 1. ARRANGE
        $config = $this->createMock(Config::class);
        $httpClient = $this->createMock(HttpClientInterface::class);

        $config->method('get')->willReturnMap([
            ['github.token', 'test_token'],
            ['github.ignore_users', []],
            ['github.ignore_labels', []],
        ]);

        $linkHeader = '<https://api.github.com/resource?page=2>; rel="last"';
        $page1Body = json_encode([
            ['user' => ['id' => 123], 'state' => 'APPROVED', 'submitted_at' => '2025-01-01', 'commit_id' => 'sha1'],
            ['user' => ['id' => 456], 'state' => 'PENDING'], // No 'submitted_at', should be skipped
        ], JSON_THROW_ON_ERROR);
        $page2Body = json_encode([
            ['user' => ['id' => 789], 'state' => 'CHANGES_REQUESTED', 'submitted_at' => '2025-01-02', 'commit_id' => 'sha2'],
        ], JSON_THROW_ON_ERROR);

        $httpClient->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                new Response(200, ['Link' => $linkHeader], $page1Body),
                new Response(200, ['Link' => $linkHeader], $page2Body)
            );

        $repository = new PullRequestReviewsRepository($config, $httpClient);
        $params = ['repo' => 'test/repo', 'pr_id' => 1];

        // 2. ACT
        $results = $repository->collect($params);

        // 3. ASSERT
        $this->assertCount(2, $results);
        $this->assertEquals(123, $results[0]['approver']);
        $this->assertEquals('APPROVED', $results[0]['state']);
        $this->assertEquals(789, $results[1]['approver']);
        $this->assertEquals('CHANGES_REQUESTED', $results[1]['state']);
    }
}
