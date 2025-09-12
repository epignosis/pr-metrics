<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

class CommitTeamDimension extends AbstractGitHubDimension
{
    public function calculate(array $params = []): string
    {
        assert(is_string($params['committer_name']));
        assert(is_string($params['committer_email']));

        $developer = $this->mappings->findDeveloper($params['committer_name'].'#'.$params['committer_email']) ?? 'Developer Unknown';

        return $this->mappings->findTeam($developer) ?? 'Team Unknown';
    }
}
