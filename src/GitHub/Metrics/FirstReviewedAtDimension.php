<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use Carbon\Carbon;
use JsonException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Repository\PullRequestReviewsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class FirstReviewedAtDimension extends AbstractGitHubDimension
{
    public function __construct(Mappings $mappings, private readonly PullRequestReviewsRepository $reviews)
    {
        parent::__construct($mappings);
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     */
    public function calculate(array $params = []): ?string
    {
        $reviews = $this->reviews->collect($params);

        foreach ($reviews as $review) {
            if ($review['state'] === 'APPROVED' || $review['state'] === 'CHANGES_REQUESTED') {
                return Carbon::parse($review['submitted_at'])->format('Y-m-d');
            }
        }

        return null;
    }
}
