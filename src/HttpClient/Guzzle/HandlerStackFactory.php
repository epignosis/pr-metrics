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

namespace TalentLMS\Metrics\HttpClient\Guzzle;

use GuzzleHttp\HandlerStack;
use TalentLMS\Metrics\Helpers\Config;

class HandlerStackFactory
{
    public static function create(Config $config): HandlerStack
    {
        // Create default HandlerStack
        $stack = HandlerStack::create();

        if ($config->get('guzzle.retry.enabled')) {
            // Add retry middleware
            $retryMiddleware = new RetryMiddleware($config);
            $stack->push($retryMiddleware->get(), 'retry');
        }

        if ($config->get('guzzle.cache.enabled')) {
            // Add caching middleware
            $cacheMiddleware = new CacheMiddleware($config);
            $stack->push($cacheMiddleware->get(), 'cache');
        }

        return $stack;
    }
}
