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

namespace Tests\GitHub\Metrics;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\CommitMessageSkipDimension;
use TalentLMS\Metrics\Helpers\Config;

class CommitMessageSkipDimensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculateReturnsTrueWhenMessageShouldBeSkipped(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('github.ignore_commit_messages', [])
            ->willReturn(['Merge branch', 'Merge pull request']);

        $dimension = new CommitMessageSkipDimension($mappings, $config);

        $params = [
            'commit_message' => 'Merge branch "feature/test"',
        ];

        $this->assertTrue($dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsFalseWhenMessageShouldNotBeSkipped(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('github.ignore_commit_messages', [])
            ->willReturn(['Merge branch', 'Merge pull request']);

        $dimension = new CommitMessageSkipDimension($mappings, $config);

        $params = [
            'commit_message' => 'Fix bug',
        ];

        $this->assertFalse($dimension->calculate($params));
    }
}
