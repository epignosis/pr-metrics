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
use TalentLMS\Metrics\GitHub\Metrics\EndedAtDimension;

class EndedAtDimensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculateReturnsMergedAtWhenStateIsClosedAndMergedAtIsSet(): void
    {
        $dimension = new EndedAtDimension($this->createMock(Mappings::class));

        $params = [
            'state' => 'closed',
            'merged_at' => '2025-01-10T10:00:00Z',
        ];

        $this->assertEquals('2025-01-10', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsClosedAtWhenStateIsClosedAndMergedAtIsNotSet(): void
    {
        $dimension = new EndedAtDimension($this->createMock(Mappings::class));

        $params = [
            'state' => 'closed',
            'closed_at' => '2025-01-11T10:00:00Z',
        ];

        $this->assertEquals('2025-01-11', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsNullWhenStateIsOpen(): void
    {
        $dimension = new EndedAtDimension($this->createMock(Mappings::class));

        $params = [
            'state' => 'open',
        ];

        $this->assertNull($dimension->calculate($params));
    }
}
