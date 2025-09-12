<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use JsonException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Repository\PullRequestReviewsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalReviewCyclesDimension extends AbstractGitHubDimension
{
    public function __construct(Mappings $mappings, private readonly PullRequestReviewsRepository $reviews)
    {
        parent::__construct($mappings);
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     */
    public function calculate(array $params = []): int
    {
        $reviews = $this->reviews->collect($params);

        $totalReviews = 0;

        foreach ($reviews as $review) {
            if ($review['state'] === 'APPROVED' || $review['state'] === 'CHANGES_REQUESTED') {
                $totalReviews++;
            }
        }

        return $totalReviews;
    }
}
