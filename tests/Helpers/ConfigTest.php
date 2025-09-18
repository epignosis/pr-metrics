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

namespace Helpers;

use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\Helpers\Config;

class ConfigTest extends TestCase
{
    private Config $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock environment variables
        $_ENV = [
            'httpclient' => 'Guzzle',
            'guzzle.timeout' => '30',
            'guzzle.retry.enabled' => 'true',
            'guzzle.retry.retry_on_status' => '500,502,504',
        ];
        $this->config = new Config();
    }

    public function testGetString(): void
    {
        $this->assertSame('Guzzle', $this->config->get('httpclient'));
    }

    public function testGetInt(): void
    {
        $this->assertSame(30, $this->config->get('guzzle.timeout'));
    }

    public function testGetBool(): void
    {
        $this->assertTrue($this->config->get('guzzle.retry.enabled'));
    }

    public function testGetArray(): void
    {
        $this->assertSame(['500', '502', '504'], $this->config->get('guzzle.retry.retry_on_status'));
    }

    public function testGetNull(): void
    {
        $this->assertNull($this->config->get('nonexistent_key'));
    }

    public function testGetWithDefault(): void
    {
        $this->assertSame('default_value', $this->config->get('nonexistent_key', 'default_value'));
    }
}
