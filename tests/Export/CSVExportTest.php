<?php

declare(strict_types=1);

namespace Tests\Export;

use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\Export\CSVExport;

class CSVExportTest extends TestCase
{
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();

        $this->filename = tempnam(sys_get_temp_dir(), 'csv');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists($this->filename)) {
            unlink($this->filename);
        }
    }

    public function testSave(): void
    {
        $export = new CSVExport($this->filename);

        $headers = [
            'Header 1' => '[key1]',
            'Header 2' => '[key2]',
        ];
        $export->setHeaders($headers);

        $lineData = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];
        $export->addLine($lineData);

        $export->save();

        $this->assertFileExists($this->filename);

        $expected = [
            ['Header 1', 'Header 2'],
            ['value1', 'value2'],
        ];

        $actual = [];
        if (($handle = fopen($this->filename, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000)) !== false) {
                $actual[] = $data;
            }
            fclose($handle);
        }

        $this->assertEquals($expected, $actual);
    }
}
