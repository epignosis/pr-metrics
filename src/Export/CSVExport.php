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
