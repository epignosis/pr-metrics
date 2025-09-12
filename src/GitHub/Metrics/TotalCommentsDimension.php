<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use JsonException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Repository\PullRequestCommentsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalCommentsDimension extends AbstractGitHubDimension
{
    public function __construct(Mappings $mappings, private readonly PullRequestCommentsRepository $comments)
    {
        parent::__construct($mappings);
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     */
    public function calculate(array $params = []): int
    {
        return count($this->comments->collect($params));
    }
}
