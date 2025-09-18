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

namespace Tests\HttpClient\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\InvalidArgumentException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Kevinrob\GuzzleCache\CacheMiddleware;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\Guzzle\GuzzleHttpClient;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class GuzzleHttpClientTest extends TestCase
{
    private Config $config;

    public function getGuzzleHttpClient(Client $guzzleClient): GuzzleHttpClient
    {
        $guzzleHttpClient = new GuzzleHttpClient($this->config);
        $reflector = new ReflectionObject($guzzleHttpClient);
        $property = $reflector->getProperty('client');
        $property->setValue($guzzleHttpClient, $guzzleClient);

        return $guzzleHttpClient;
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(Config::class);
        $this->config->method('get')->willReturnMap([
            ['guzzle.timeout', 5],
            ['guzzle.retry.enabled', true],
            ['guzzle.retry.max_retry_attempts', 5],
            ['guzzle.retry.retry_on_timeout', true],
            ['guzzle.retry.retry_on_status', '500,502,503,504'],
            ['guzzle.cache.enabled', true],
            ['guzzle.cache.ttl', 14400],
            ['guzzle.cache.path', 'tmp/cache'],
        ]);
    }

    /**
     * @throws Exception
     * @throws HttpClientException
     */
    public function testSend(): void
    {
        $guzzleClient = $this->createMock(Client::class);
        $guzzleClient->method('send')->willReturn(
            new Response(200, ['X-Foo' => 'Bar'], '{"data": "mock"}')
        );

        $guzzleHttpClient = $this->getGuzzleHttpClient($guzzleClient);
        $response = $guzzleHttpClient->send('GET', '/test');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"data": "mock"}', $response->getBody()->getContents());
        $this->assertFalse($guzzleHttpClient->lastResponseFromCache());
    }

    /**
     * @throws Exception
     * @throws HttpClientException
     */
    public function testSendResponseFromCache(): void
    {
        $guzzleClient = $this->createMock(Client::class);
        $guzzleClient->method('send')->willReturn(
            new Response(200, ['X-Foo' => 'Bar', CacheMiddleware::HEADER_CACHE_INFO => CacheMiddleware::HEADER_CACHE_HIT], '{"data": "mock"}')
        );

        $guzzleHttpClient = $this->getGuzzleHttpClient($guzzleClient);
        $response = $guzzleHttpClient->send('GET', '/test');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($guzzleHttpClient->lastResponseFromCache());
    }

    /**
     * @throws Exception
     */
    public function testSendWithException(): void
    {
        $this->expectException(HttpClientException::class);

        $guzzleClient = $this->createMock(Client::class);
        $guzzleClient->method('send')->willThrowException(new InvalidArgumentException());

        $guzzleHttpClient = $this->getGuzzleHttpClient($guzzleClient);
        $guzzleHttpClient->send('GET', '/test');
    }

    public function testGuzzleClientInitialization(): void
    {
        $guzzleHttpClient = new GuzzleHttpClient($this->config);
        $reflector = new ReflectionObject($guzzleHttpClient);
        $property = $reflector->getProperty('client');
        $guzzleClient = $property->getValue($guzzleHttpClient);

        $this->assertInstanceOf(Client::class, $guzzleClient);

        $reflector = new ReflectionObject($guzzleClient);
        $property = $reflector->getProperty('config');
        $config = $property->getValue($guzzleClient);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('handler', $config);
        $this->assertEquals(5, $config['timeout']);
        $this->assertInstanceOf(HandlerStack::class, $config['handler']);
    }
}