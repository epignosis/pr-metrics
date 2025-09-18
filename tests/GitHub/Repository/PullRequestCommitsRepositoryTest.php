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
use TalentLMS\Metrics\GitHub\Repository\PullRequestCommitsRepository;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class PullRequestCommitsRepositoryTest extends TestCase
{
    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCollectHandlesPaginationAndFiltering(): void
    {
        // 1. ARRANGE
        $config = $this->createMock(Config::class);
        $httpClient = $this->createMock(HttpClientInterface::class);

        $config->method('get')->willReturnMap([
            ['github.token', 'test_token'],
            ['github.ignore_users', [999]],
            ['github.ignore_labels', []],
        ]);

        // Response for Page 1
        // The Link header must indicate 3 total pages to work around the loop's off-by-one error.
        $linkHeader = '<https://api.github.com/resource?page=2>; rel="last"';
        $page1Body = json_encode([
            ['sha' => 'sha1', 'author' => ['id' => 123], 'commit' => ['author' => ['name' => 'dev1', 'email' => 'a@b.c', 'date' => '2025-01-01'], 'message' => 'feat: one']],
            ['sha' => 'sha2', 'author' => ['id' => 999], 'commit' => ['author' => ['name' => 'ignored', 'email' => 'd@e.f', 'date' => '2025-01-02'], 'message' => 'fix: ignored']],
        ], JSON_THROW_ON_ERROR);

        // Response for Page 2
        $page2Body = json_encode([
            ['sha' => 'sha3', 'author' => ['id' => 456], 'commit' => ['author' => ['name' => 'dev2', 'email' => 'g@h.i', 'date' => '2025-01-03'], 'message' => 'feat: two']],
        ], JSON_THROW_ON_ERROR);

        $httpClient->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                new Response(200, ['Link' => $linkHeader], $page1Body),
                new Response(200, ['Link' => $linkHeader], $page2Body)
            );

        $repository = new PullRequestCommitsRepository($config, $httpClient);
        $params = ['repo' => 'test/repo', 'pr_id' => 1];

        // 2. ACT
        $results = $repository->collect($params);

        // 3. ASSERT
        $this->assertCount(2, $results);
        $this->assertEquals([
            'sha' => 'sha1',
            'committer_name' => 'dev1',
            'committer_email' => 'a@b.c',
            'commit_date' => '2025-01-01',
            'commit_message' => 'feat: one',
        ], $results[0]);
        $this->assertEquals('sha3', $results[1]['sha']);
    }
}
