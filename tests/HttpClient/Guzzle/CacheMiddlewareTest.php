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

use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use ReflectionObject;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\Guzzle\CacheMiddleware;
use TalentLMS\Metrics\HttpClient\Guzzle\GreedyCacheStrategyWithRequestBody;

class CacheMiddlewareTest extends TestCase
{
    /**
     * @throws ReflectionException
     * @throws Exception
     */
    public function testConstructorBuildsCorrectConfig(): void
    {
        // 1. Arrange: Create a mock Config with specific values to test against.
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap([
            ['guzzle.cache.path', 'tmp/test-cache'],
            ['guzzle.cache.ttl', 3600],
        ]);

        // 2. Act: Instantiate the class.
        $cacheMiddlewareFactory = new CacheMiddleware($config);

        // Use reflection to access the private property.
        $reflector = new ReflectionObject($cacheMiddlewareFactory);
        $property = $reflector->getProperty('middlewareConfig');
        $middlewareConfig = $property->getValue($cacheMiddlewareFactory);
        $middlewareInstance = $cacheMiddlewareFactory->get();

        // 3. Assert: Check that the config array was built correctly.
        $this->assertEquals([
            'cache_path' => 'tmp/test-cache',
            'default_ttl' => 3600,
        ], $middlewareConfig);
        $this->assertInstanceOf(\Kevinrob\GuzzleCache\CacheMiddleware::class, $middlewareInstance);

        $strategy = $middlewareInstance->getCacheStorage();
        $this->assertEquals([
            'GET' => true,
            'POST' => true
        ], $middlewareInstance->getHttpMethods());
        $this->assertInstanceOf(GreedyCacheStrategyWithRequestBody::class, $middlewareInstance->getCacheStorage());

        $strategyReflector = new ReflectionObject($strategy);
        $ttlProperty = $strategyReflector->getProperty('defaultTtl');
        $this->assertEquals(3600, $ttlProperty->getValue($strategy));

        // b) Check the storage adapter and its path
        $storageProperty = $strategyReflector->getProperty('storage');
        $storage = $storageProperty->getValue($strategy);
        $this->assertInstanceOf(FlysystemStorage::class, $storage);

        $storageReflector = new ReflectionObject($storage);
        $filesystemProperty = $storageReflector->getProperty('filesystem');
        $filesystem = $filesystemProperty->getValue($storage);
        $this->assertInstanceOf(Filesystem::class, $filesystem);

        $filesystemReflector = new ReflectionObject($filesystem);
        $adapterProperty = $filesystemReflector->getProperty('adapter');
        $adapter = $adapterProperty->getValue($filesystem);
        $this->assertInstanceOf(LocalFilesystemAdapter::class, $adapter);

        $adapterReflector = new ReflectionObject($adapter);
        $prefixProperty = $adapterReflector->getProperty('rootLocation');
        /** @var string $pathValue */
        $pathValue = $prefixProperty->getValue($adapter);
        $this->assertStringEndsWith('tmp/test-cache', $pathValue);
    }
}
