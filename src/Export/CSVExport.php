<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\Export;

use RuntimeException;

final class CSVExport extends AbstractExport
{
    /** @var resource $file */
    private mixed $file;

    public function __construct(private readonly string $filename)
    {
        parent::__construct();
    }

    public function save(): void
    {
        $this->openFile();
        $this->addData(array_keys($this->headers));

        foreach ($this->lines as $line) {
            $this->addData($line);
        }

        $this->closeFile();
    }

    private function openFile(): void
    {
        $file = fopen($this->filename, 'w');

        if (!$file) {
            throw new RuntimeException("Could not open file: {$this->filename}");
        }

        $this->file = $file;
    }

    /**
     * @param array<bool|float|int|string|null> $row
     * @return void
     */
    private function addData(array $row): void
    {
        $return = fputcsv($this->file, $row);

        if ($return === false) {
            throw new RuntimeException("Could not write to file: {$this->filename}");
        }
    }

    private function closeFile(): void
    {
        fclose($this->file);
    }
}
