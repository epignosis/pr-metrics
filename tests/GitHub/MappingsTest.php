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

namespace Tests\GitHub;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\Helpers\Config;

class MappingsTest extends TestCase
{
    private string $tempFile = '';
    private string $tempFullPath = '';

    protected function setUp(): void
    {
        parent::setUp();

        // Define a predictable path within the project's tmp directory
        $this->tempFile = 'tmp/test_mappings.json';
        $this->tempFullPath = __DIR__.'/../../'.$this->tempFile;
        // Ensure the directory exists
        if (!is_dir(dirname($this->tempFullPath))) {
            mkdir(dirname($this->tempFullPath));
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (!empty($this->tempFullPath) && file_exists($this->tempFullPath)) {
            unlink($this->tempFullPath);
            $this->tempFile = '';
            $this->tempFullPath = '';
        }
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function testConstructorWithValidMappingFile(): void
    {
        // 1. Arrange: Create a valid mapping file at the predictable path.
        $mappingsData = [
            'user_index' => [
                'github_user_1' => 'developer_a',
            ],
            'developer_index' => [
                'Developer A#dev.a@example.com' => 'developer_a',
            ],
            'team_index' => [
                'developer_a' => 'Team Alpha',
            ],
        ];
        file_put_contents($this->tempFullPath, json_encode($mappingsData));

        // Sanity check to ensure the file exists before the test runs.
        $this->assertFileExists($this->tempFullPath);

        $config = $this->createMock(Config::class);
        // The path must be relative to the project root.
        $config->method('get')->with('github.mapping_file')->willReturn($this->tempFile);

        // 2. Act: Instantiate the class.
        $mappings = new Mappings($config);

        // 3. Assert: Check all find methods.
        $this->assertEquals('developer_a', $mappings->findUser('github_user_1'));
        $this->assertNull($mappings->findUser('unknown_user'));
        $this->assertEquals('default', $mappings->findUser('unknown_user', 'default'));
        $this->assertEquals('developer_a', $mappings->findDeveloper('Developer A#dev.a@example.com'));
        $this->assertNull($mappings->findDeveloper('unknown_dev'));
        $this->assertEquals('Team Alpha', $mappings->findTeam('developer_a'));
        $this->assertNull($mappings->findTeam('unknown_team'));
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function testConstructorWithNonExistentMappingFile(): void
    {
        // 1. Arrange: Configure a path to a file that does not exist.
        $config = $this->createMock(Config::class);
        $config->method('get')->with('github.mapping_file')->willReturn('non_existent_file.json');

        // 2. Act: Instantiate the class.
        $mappings = new Mappings($config);

        // 3. Assert: All find methods should return the default value.
        $this->assertNull($mappings->findUser('any_user'));
        $this->assertNull($mappings->findDeveloper('any_dev'));
        $this->assertNull($mappings->findTeam('any_team'));
    }

    /**
     * @throws Exception
     * @throws JsonException
     */
    public function testConstructorWithEmptyMappingFile(): void
    {
        // 1. Arrange: Create an empty file.
        file_put_contents($this->tempFullPath, '');
        $this->assertFileExists($this->tempFullPath);

        $config = $this->createMock(Config::class);
        $config->method('get')->with('github.mapping_file')->willReturn($this->tempFile);

        // 2. Act: Instantiate the class.
        $mappings = new Mappings($config);

        // 3. Assert: All find methods should return the default value.
        $this->assertNull($mappings->findUser('any_user'));
        $this->assertNull($mappings->findDeveloper('any_dev'));
        $this->assertNull($mappings->findTeam('any_team'));
    }
}