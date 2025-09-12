<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use JsonException;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Repository\PullRequestFilesRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class TotalChangesDimension extends AbstractGitHubDimension
{
    public function __construct(Mappings $mappings, private readonly PullRequestFilesRepository $files)
    {
        parent::__construct($mappings);
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     */
    public function calculate(array $params = []): int
    {
        $files = $this->files->collect($params);

        $totalChanges = 0;

        foreach ($files as $file) {
            $totalChanges += $file['changes'];
        }

        return $totalChanges;
    }
}
