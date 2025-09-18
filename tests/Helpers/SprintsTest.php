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

namespace Tests\Helpers;

use Carbon\Carbon;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionObject;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\Helpers\Sprints;

class SprintsTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // Unfreeze time after each test to prevent side effects
        Carbon::setTestNow();
    }

    /**
     * @throws Exception
     */
    public function testConstructorCalculatesSprintsWithDefaultStartDate(): void
    {
        // 1. Arrange: Freeze time to a specific date. The default start date is 2025-01-06.
        Carbon::setTestNow(Carbon::create(2025, 1, 22)); // Wednesday, ISO Week 4

        $config = $this->createMock(Config::class);
        // Ensure the config returns null so the default is used.
        $config->method('get')->with('sprint.start_date', '2025-01-06')->willReturn('2025-01-06');

        // 2. Act
        $sprints = new Sprints($config);

        // 3. Assert
        // Weeks 2 & 3 should be Sprint 1. Weeks 4 & 5 should be Sprint 2.
        $this->assertEquals('2025 Sprint 2', $sprints->getCurrentSprintId());

        // Also assert the internal state is correct using reflection.
        $reflector = new ReflectionObject($sprints);
        $sprintsProperty = $reflector->getProperty('sprints');
        $internalSprints = $sprintsProperty->getValue($sprints);

        $expectedSprintsMap = [
            '2025_2' => '2025 Sprint 1',
            '2025_3' => '2025 Sprint 1',
            '2025_4' => '2025 Sprint 2',
        ];
        $this->assertEquals($expectedSprintsMap, $internalSprints);
    }

    /**
     * @throws Exception
     */
    public function testConstructorCalculatesSprintsWithCustomStartDate(): void
    {
        // 1. Arrange: Freeze time and provide a custom start date.
        $customStartDate = '2025-02-03'; // Monday, ISO Week 6
        Carbon::setTestNow(Carbon::create(2025, 2, 19)); // Wednesday, ISO Week 8

        $config = $this->createMock(Config::class);
        $config->method('get')->with('sprint.start_date', '2025-01-06')->willReturn($customStartDate);

        // 2. Act
        $sprints = new Sprints($config);

        // 3. Assert
        // Weeks 6 & 7 are Sprint 1. Weeks 8 & 9 are Sprint 2.
        $this->assertEquals('2025 Sprint 2', $sprints->getCurrentSprintId());
    }

    /**
     * @throws Exception
     */
    public function testConstructorHandlesYearBoundaryCorrectly(): void
    {
        // 1. Arrange: Start late in 2025 and freeze time in early 2026.
        $customStartDate = '2025-12-08'; // Monday, ISO Week 50 of 2025
        Carbon::setTestNow(Carbon::create(2026, 1, 28)); // Wednesday, ISO Week 5 of 2026

        $config = $this->createMock(Config::class);
        $config->method('get')->with('sprint.start_date', '2025-01-06')->willReturn($customStartDate);

        // 2. Act
        $sprints = new Sprints($config);

        // 3. Assert
        $this->assertEquals('2026 Sprint 2', $sprints->getCurrentSprintId());

        $reflector = new ReflectionObject($sprints);
        $sprintsProperty = $reflector->getProperty('sprints');
        $internalSprints = $sprintsProperty->getValue($sprints);

        $expectedSprintsMap = [
            '2025_50' => '2025 Sprint 1',
            '2025_51' => '2025 Sprint 1',
            '2025_52' => '2025 Sprint 2',
            '2026_1' => '2025 Sprint 2',
            '2026_2' => '2026 Sprint 1',
            '2026_3' => '2026 Sprint 1',
            '2026_4' => '2026 Sprint 2',
            '2026_5' => '2026 Sprint 2',
        ];
        $this->assertEquals($expectedSprintsMap, $internalSprints);
    }
}