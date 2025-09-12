<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use JsonException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Repository\PullRequestCommitsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalCommitsDimension extends AbstractGitHubDimension
{
    public function __construct(Mappings $mappings, private readonly PullRequestCommitsRepository $commits)
    {
        parent::__construct($mappings);
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     */
    public function calculate(array $params = []): int
    {
        return count($this->commits->collect($params));
    }
}
