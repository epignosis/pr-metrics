<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use Carbon\Carbon;

class EndedAtDimension extends AbstractGitHubDimension
{
    public function calculate(array $params = []): ?string
    {
        $endDate = null;

        assert(is_string($params['state']));
        assert(!isset($params['merged_at']) || is_string($params['merged_at']));
        assert(!isset($params['closed_at']) || is_string($params['closed_at']));

        if ($params['state'] === 'closed') {
            /** @var string $closedOrMergedAt */
            $closedOrMergedAt = $params['merged_at'] ?? $params['closed_at'];
            $endDate = Carbon::parse($closedOrMergedAt)->format('Y-m-d');
        }

        return $endDate;
    }
}
