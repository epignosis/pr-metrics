<?php

declare(strict_types=1);

namespace TalentLMS\Metrics;

interface DimensionInterface
{
    /**
     * Calculate metric based on provided parameters.
     *
     * @param array<mixed> $params Parameters required for calculation.
     * @return mixed Calculated metric value.
     */
    public function calculate(array $params = []): mixed;
}
