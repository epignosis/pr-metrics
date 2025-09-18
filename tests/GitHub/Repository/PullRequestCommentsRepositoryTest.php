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
use TalentLMS\Metrics\GitHub\Repository\PullRequestCommentsRepository;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class PullRequestCommentsRepositoryTest extends TestCase
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

        $linkHeader = '<https://api.github.com/resource?page=2>; rel="last"';
        $page1Body = json_encode([
            ['user' => ['id' => 123]],
            ['user' => ['id' => 999]], // Ignored
        ], JSON_THROW_ON_ERROR);
        $page2Body = json_encode([
            ['user' => ['id' => 456]],
        ], JSON_THROW_ON_ERROR);

        $httpClient->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                new Response(200, ['Link' => $linkHeader], $page1Body),
                new Response(200, ['Link' => $linkHeader], $page2Body)
            );

        $repository = new PullRequestCommentsRepository($config, $httpClient);
        $params = ['repo' => 'test/repo', 'pr_id' => 1];

        // 2. ACT
        $results = $repository->collect($params);

        // 3. ASSERT
        $this->assertCount(2, $results);
        $this->assertEquals(123, $results[0]['author']);
        $this->assertEquals(456, $results[1]['author']);
    }
}
