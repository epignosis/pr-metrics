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
use RuntimeException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\UserDimension;

class UserDimensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculate(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findUser')
            ->with('12345')
            ->willReturn('johndoe');

        $dimension = new UserDimension($mappings);

        $params = [
            'id' => 1,
            'creator' => 12345,
        ];

        $this->assertEquals('johndoe', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateThrowsExceptionForUnknownUser(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findUser')
            ->willReturn(null);

        $dimension = new UserDimension($mappings);

        $params = [
            'id' => 1,
            'creator' => 12345,
        ];

        $this->expectException(RuntimeException::class);
        $dimension->calculate($params);
    }
}