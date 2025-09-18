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

namespace Tests\HttpClient\Guzzle;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\Guzzle\RetryMiddleware;

class RetryMiddlewareTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetReturnsCorrectlyConfiguredMiddleware(): void
    {
        // 1. Arrange: Create a mock Config with specific values to test against.
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap([
            ['guzzle.retry.max_retry_attempts', 10],
            ['guzzle.retry.retry_on_timeout', false],
            ['guzzle.retry.retry_on_status', '429,500'],
        ]);

        // 2. Act: Instantiate the class.
        $retryMiddlewareFactory = new RetryMiddleware($config);

        // Use reflection to access the private property.
        $reflector = new ReflectionObject($retryMiddlewareFactory);
        $property = $reflector->getProperty('middlewareConfig');
        $middlewareConfig = $property->getValue($retryMiddlewareFactory);

        // 3. Assert: Assert that the options match the configuration we provided.
        $this->assertEquals([
            'max_retry_attempts' => 10,
            'retry_on_timeout' => false,
            'retry_on_status' => '429,500',
        ], $middlewareConfig);
    }
}
