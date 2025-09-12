<?php

declare(strict_types=1);

namespace TalentLMS\Metrics;

interface RepositoryInterface
{
    /**
     * Collect data based on provided parameters.
     *
     * @param array<mixed> $params Parameters required for collection.
     * @return array<mixed> Collected data.
     */
    public function collect(array $params = []): array;
}
