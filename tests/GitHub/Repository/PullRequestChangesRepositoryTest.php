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
use TalentLMS\Metrics\GitHub\Repository\PullRequestChangesRepository;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class PullRequestChangesRepositoryTest extends TestCase
{
    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCollectHandlesGraphQLPagination(): void
    {
        // 1. ARRANGE
        $config = $this->createMock(Config::class);
        $httpClient = $this->createMock(HttpClientInterface::class);

        $config->method('get')->willReturnMap([
            ['github.token', 'test_token'],
            ['github.ignore_users', []],
            ['github.ignore_labels', []],
        ]);

        // Page 1 Response
        $page1Body = json_encode([
            'data' => ['repository' => ['pullRequest' => ['commits' => [
                'nodes' => [['commit' => ['oid' => 'sha1', 'additions' => 10, 'deletions' => 5]]],
                'pageInfo' => ['endCursor' => 'cursor1', 'hasNextPage' => true],
            ]]]],
        ], JSON_THROW_ON_ERROR);

        // Page 2 Response
        $page2Body = json_encode([
            'data' => ['repository' => ['pullRequest' => ['commits' => [
                'nodes' => [['commit' => ['oid' => 'sha2', 'additions' => 8, 'deletions' => 2]]],
                'pageInfo' => ['endCursor' => 'cursor2', 'hasNextPage' => false],
            ]]]],
        ], JSON_THROW_ON_ERROR);

        $httpClient->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                new Response(200, [], $page1Body),
                new Response(200, [], $page2Body)
            );

        $repository = new PullRequestChangesRepository($config, $httpClient);
        $params = ['repo' => 'test/repo', 'pr_id' => 1];

        // 2. ACT
        $results = $repository->collect($params);

        // 3. ASSERT
        $this->assertCount(2, $results);
        $this->assertEquals('sha1', $results[0]['sha']);
        $this->assertEquals(10, $results[0]['additions']);
        $this->assertEquals(5, $results[0]['deletions']);
        $this->assertEquals('sha2', $results[1]['sha']);
        $this->assertEquals(8, $results[1]['additions']);
        $this->assertEquals(2, $results[1]['deletions']);
    }
}
