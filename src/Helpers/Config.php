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

namespace TalentLMS\Metrics\Helpers;

use Dotenv\Dotenv;

class Config
{
    public const string TYPE_STRING = 'string';
    public const string TYPE_INT = 'int';
    public const string TYPE_BOOL = 'bool';
    public const string TYPE_ARRAY = 'array';

    private const array TYPE_MAP = [
        // int mappings
        'guzzle.timeout' => self::TYPE_INT,
        'guzzle.retry.max_retry_attempts' => self::TYPE_INT,
        'guzzle.cache.ttl' => self::TYPE_INT,
        // array mappings
        'guzzle.retry.retry_on_status' => self::TYPE_ARRAY,
        'github.ignore_labels' => self::TYPE_ARRAY,
        'github.ignore_users' => self::TYPE_ARRAY,
        'github.ignore_commit_messages' => self::TYPE_ARRAY,
        // bool mappings
        'guzzle.retry.enabled' => self::TYPE_BOOL,
        'guzzle.retry.retry_on_timeout' => self::TYPE_BOOL,
        'guzzle.cache.enabled' => self::TYPE_BOOL,
        'metrics.contributions' => self::TYPE_BOOL,
    ];

    public function __construct(string $path = __DIR__.'/../../')
    {
        $dotenv = Dotenv::createImmutable($path);
        $dotenv->load();
    }

    /**
     * Get an environment variable with type casting and default value support.
     *
     * @param string $key The environment variable key.
     * @param int|string|bool|array<mixed>|null $default The default value if the key is not found.
     * @return int|string|bool|array<mixed>|null The value of the environment variable, cast to the appropriate type.
     */
    public function get(string $key, int|string|bool|array|null $default = null): int|string|bool|array|null
    {
        $type = self::TYPE_MAP[$key] ?? self::TYPE_STRING;
        $value = $_ENV[$key] ?? $default;

        /** @var int|string|bool|array<mixed>|null $value */
        $value = match ($type) {
            self::TYPE_INT => is_numeric($value) ? (int) $value : $default,
            self::TYPE_BOOL => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default,
            self::TYPE_ARRAY => is_string($value) ? array_map('trim', explode(',', $value)) : (array) $value,
            default => $value,
        };

        return $value;
    }
}