<?php

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
