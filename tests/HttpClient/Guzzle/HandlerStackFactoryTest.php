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

use GuzzleHttp\HandlerStack;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\Guzzle\HandlerStackFactory;

class HandlerStackFactoryTest extends TestCase
{
    /**
     * @param array<int, array<int, mixed>> $configMap
     * @param bool $expectRetry
     * @param bool $expectCache
     * @throws Exception
     */
    #[DataProvider('middlewareConfigurationProvider')]
    public function testCreateConfiguresMiddlewareCorrectly(
        array $configMap,
        bool $expectRetry,
        bool $expectCache
    ): void {
        // 1. Arrange: Create a mock Config object with the specific scenario's settings.
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap($configMap);

        // 2. Act: Call the static factory method to create the HandlerStack.
        $handlerStack = HandlerStackFactory::create($config);

        // 3. Assert: Verify that the returned stack has the correct middleware.
        $this->assertInstanceOf(HandlerStack::class, $handlerStack);
        $this->assertEquals($expectRetry, $this->hasMiddleware($handlerStack, 'retry'), "Retry middleware presence mismatch.");
        $this->assertEquals($expectCache, $this->hasMiddleware($handlerStack, 'cache'), "Cache middleware presence mismatch.");
    }

    /**
     * Provides all four combinations of middleware configurations.
     *
     * @return array<string, array{0: array<int, array<int, mixed>>, 1: bool, 2: bool}>
     */
    public static function middlewareConfigurationProvider(): array
    {
        // Define a base config to avoid repetition.
        // The factory doesn't use these values, but the middleware constructors do,
        // so they need to be present as a base config.
        $baseConfig = [
            ['guzzle.retry.max_retry_attempts', 5],
            ['guzzle.retry.retry_on_timeout', true],
            ['guzzle.retry.retry_on_status', '500'],
            ['guzzle.cache.ttl', 14400],
            ['guzzle.cache.path', 'tmp/cache'],
        ];

        return [
            'All Middleware Enabled' => [
                array_merge($baseConfig, [['guzzle.retry.enabled', true], ['guzzle.cache.enabled', true]]),
                true, // expectRetry
                true  // expectCache
            ],
            'Only Retry Enabled' => [
                array_merge($baseConfig, [['guzzle.retry.enabled', true], ['guzzle.cache.enabled', false]]),
                true, // expectRetry
                false // expectCache
            ],
            'Only Cache Enabled' => [
                array_merge($baseConfig, [['guzzle.retry.enabled', false], ['guzzle.cache.enabled', true]]),
                false, // expectRetry
                true   // expectCache
            ],
            'All Middleware Disabled' => [
                array_merge($baseConfig, [['guzzle.retry.enabled', false], ['guzzle.cache.enabled', false]]),
                false, // expectRetry
                false  // expectCache
            ],
        ];
    }

    /**
     * Helper method to inspect the private stack of a HandlerStack object.
     */
    private function hasMiddleware(HandlerStack $handler, string $name): bool
    {
        $reflector = new ReflectionObject($handler);
        $stackProperty = $reflector->getProperty('stack');
        /** @var array<array<callable, string|null>> $stack */
        $stack = $stackProperty->getValue($handler);

        foreach ($stack as $middleware) {
            if (isset($middleware[1]) && $middleware[1] === $name) {
                return true;
            }
        }
        return false;
    }
}