<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use RuntimeException;

class CommitAuthorDimension extends AbstractGitHubDimension
{
    public function calculate(array $params = []): string
    {
        assert(is_string($params['sha']));
        assert(is_string($params['committer_name']));
        assert(is_string($params['committer_email']));

        $developerAlias = $params['committer_name'].'#'.$params['committer_email'];
        $developer = $this->mappings->findDeveloper($developerAlias);

        if (!$developer) {
            throw new RuntimeException('In commit '.$params['sha'].' found an unknown developer: '.$developerAlias);
        }

        return $developer;
    }
}
