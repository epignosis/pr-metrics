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

namespace Tests\HttpClient;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\Guzzle\GuzzleHttpClient;
use TalentLMS\Metrics\HttpClient\HttpClientFactory;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class HttpClientFactoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetReturnsGuzzleHttpClient(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap([
            ['httpclient', 'Guzzle'],
            ['guzzle.timeout', 5],
            ['guzzle.retry.enabled', true],
            ['guzzle.retry.max_retry_attempts', 5],
            ['guzzle.retry.retry_on_timeout', true],
            ['guzzle.retry.retry_on_status', '500,502,503,504'],
            ['guzzle.cache.enabled', true],
            ['guzzle.cache.ttl', 14400],
            ['guzzle.cache.path', 'tmp/cache'],
        ]);

        $httpClient = HttpClientFactory::get($config);
        $this->assertInstanceOf(HttpClientInterface::class, $httpClient);
        $this->assertInstanceOf(GuzzleHttpClient::class, $httpClient);
    }
}