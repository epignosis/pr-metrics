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

namespace Tests\Export;

use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\Export\AbstractExport;

class AbstractExportTest extends TestCase
{
    public function testSetHeadersAndAddLines(): void
    {
        $export = new class () extends AbstractExport {
            /**
             * @return array<string, string>
             */
            public function getHeaders(): array
            {
                return $this->headers;
            }

            /**
             * @return array<array<string, bool|float|int|string|null>>
             */
            public function getLines(): array
            {
                return $this->lines;
            }

            public function save(): void
            {
                // No-op for testing
            }
        };

        $headers = [
            'Header 1' => '[key1]',
            'Header 2' => '[key2]',
            'Header 3' => '[key3]', // This header will not have corresponding data in lines
        ];
        $lines = [
            [
                'key1' => 'value1.1',
                'key2' => 'value1.2',
                'key4' => 'value1.4', // This key is not in headers and should be ignored
            ],
            [
                'key1' => 'value2.1',
                'key2' => 'value2.2',
                'key3' => 'value2.3',
            ]
        ];

        $export->setHeaders($headers);
        $export->addLine($lines[0]);
        $export->addLine($lines[1]);

        $actualHeaders = $export->getHeaders();
        $actualLines = $export->getLines();

        $this->assertCount(3, $actualHeaders);
        $this->assertEquals($headers, $actualHeaders);
        $this->assertCount(2, $actualLines);
        $this->assertEquals([
            '[key1]' => 'value1.1',
            '[key2]' => 'value1.2',
            '[key3]' => '',
        ], $actualLines[0]);
        $this->assertEquals([
            '[key1]' => 'value2.1',
            '[key2]' => 'value2.2',
            '[key3]' => 'value2.3',
        ], $actualLines[1]);
    }
}
