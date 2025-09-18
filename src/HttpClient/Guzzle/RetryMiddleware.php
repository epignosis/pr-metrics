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

use Closure;
use GuzzleRetry\GuzzleRetryMiddleware;
use TalentLMS\Metrics\Helpers\Config;

class RetryMiddleware
{
    /** @var array<string, mixed> $middlewareConfig */
    protected array $middlewareConfig;

    public function __construct(Config $config)
    {
        $this->middlewareConfig = [
            'max_retry_attempts' => $config->get('guzzle.retry.max_retry_attempts'),
            'retry_on_timeout' => $config->get('guzzle.retry.retry_on_timeout'),
            'retry_on_status' => $config->get('guzzle.retry.retry_on_status'),
        ];
    }

    public function get(): Closure
    {
        return GuzzleRetryMiddleware::factory($this->middlewareConfig);
    }
}
