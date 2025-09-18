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

namespace TalentLMS\Metrics\HttpClient\Guzzle;

use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface;
use League\Flysystem\Local\LocalFilesystemAdapter;
use TalentLMS\Metrics\Helpers\Config;

class CacheMiddleware
{
    /** @var array<string, mixed> $middlewareConfig */
    private array $middlewareConfig;

    public function __construct(Config $config)
    {
        $this->middlewareConfig = [
            'cache_path' => $config->get('guzzle.cache.path'),
            'default_ttl' => $config->get('guzzle.cache.ttl'),
        ];
    }

    public function get(): \Kevinrob\GuzzleCache\CacheMiddleware
    {
        // Add caching middleware
        $cacheMiddleware = new \Kevinrob\GuzzleCache\CacheMiddleware(
            $this->getStrategy()
        );
        $cacheMiddleware->setHttpMethods([
            'GET' => true,
            'POST' => true
        ]);

        return $cacheMiddleware;
    }

    private function getStorage(): CacheStorageInterface
    {
        /** @var string $cachePath */
        $cachePath = $this->middlewareConfig['cache_path'];

        return new FlysystemStorage(
            new LocalFilesystemAdapter(__DIR__.'/../../../'.$cachePath)
        );
    }

    private function getStrategy(): CacheStrategyInterface
    {
        return new GreedyCacheStrategyWithRequestBody(
            $this->getStorage(),
            $this->middlewareConfig['default_ttl']
        );
    }
}
