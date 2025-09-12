<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use TalentLMS\Metrics\DimensionInterface;
use TalentLMS\Metrics\GitHub\Mappings;

abstract class AbstractGitHubDimension implements DimensionInterface
{
    public function __construct(protected readonly Mappings $mappings)
    {
    }
}
