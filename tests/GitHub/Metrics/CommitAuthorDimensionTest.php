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

namespace Tests\GitHub\Metrics;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\CommitAuthorDimension;

class CommitAuthorDimensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculate(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findDeveloper')
            ->with('John Doe#john.doe@example.com')
            ->willReturn('johndoe');

        $dimension = new CommitAuthorDimension($mappings);

        $params = [
            'sha' => '123456',
            'committer_name' => 'John Doe',
            'committer_email' => 'john.doe@example.com',
        ];

        $this->assertEquals('johndoe', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateThrowsExceptionForUnknownDeveloper(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findDeveloper')
            ->willReturn(null);

        $dimension = new CommitAuthorDimension($mappings);

        $params = [
            'sha' => '123456',
            'committer_name' => 'Unknown Developer',
            'committer_email' => 'unknown@example.com',
        ];

        $this->expectException(RuntimeException::class);
        $dimension->calculate($params);
    }
}
