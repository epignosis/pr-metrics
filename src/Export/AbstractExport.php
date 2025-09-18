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

namespace TalentLMS\Metrics\Export;

use Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Main class that the other export methods/class should override.
 *
 * @package TalentLMS\Metrics\Export
 */
abstract class AbstractExport
{
    /** @var array<string, string> $headers */
    protected array $headers = [];
    /** @var array<array<string, bool|float|int|string|null>> $lines */
    protected array $lines = [];
    protected PropertyAccessorInterface $propertyAccessor;

    public function __construct()
    {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableExceptionOnInvalidIndex()
            ->getPropertyAccessor();
    }

    /**
     * The headers are in a form: 'Header Label' => '[column_key]'. This is used to map the data
     * based on the column_key and not based on the order of the line data.
     *
     * @param array<string, string> $headers
     * @return void
     */
    public function setHeaders(array $headers): void
    {
        $this->headers = $headers;
    }

    /**
     * @param array<string, bool|float|int|string|null> $lineData
     * @return void
     */
    public function addLine(array $lineData): void
    {
        $lineData = $this->mapData($lineData);
        $this->lines[] = $lineData;
    }

    /**
     * @param array<string, bool|float|int|string|null> $lineData
     * @return array<string, bool|float|int|string|null>
     */
    protected function mapData(array $lineData): array
    {

        $mappedLine = [];
        foreach ($this->headers as $headerKey) {
            try {
                /** @var bool|float|int|string|null $value */
                $value = $this->propertyAccessor->getValue($lineData, $headerKey);
                $mappedLine[$headerKey] = $value;
            } catch (Exception) {
                $mappedLine[$headerKey] = '';
            }
        }
        return $mappedLine;
    }

    abstract public function save(): void;
}
