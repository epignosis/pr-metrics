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
use TalentLMS\Metrics\GitHub\Metrics\CommitTeamDimension;

class CommitTeamDimensionTest extends TestCase
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
        $mappings->method('findTeam')
            ->with('johndoe')
            ->willReturn('Team A');

        $dimension = new CommitTeamDimension($mappings);

        $params = [
            'committer_name' => 'John Doe',
            'committer_email' => 'john.doe@example.com',
        ];

        $this->assertEquals('Team A', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsUnknownTeamWhenDeveloperIsNotFound(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findDeveloper')
            ->willReturn(null);

        $dimension = new CommitTeamDimension($mappings);

        $params = [
            'committer_name' => 'Unknown Developer',
            'committer_email' => 'unknown@example.com',
        ];

        $this->assertEquals('Team Unknown', $dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsUnknownTeamWhenTeamIsNotFound(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $mappings->method('findDeveloper')
            ->willReturn('johndoe');
        $mappings->method('findTeam')
            ->willReturn(null);

        $dimension = new CommitTeamDimension($mappings);

        $params = [
            'committer_name' => 'John Doe',
            'committer_email' => 'john.doe@example.com',
        ];

        $this->assertEquals('Team Unknown', $dimension->calculate($params));
    }
}
