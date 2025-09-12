<?php

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
