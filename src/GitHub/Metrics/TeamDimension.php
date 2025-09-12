<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

class TeamDimension extends AbstractGitHubDimension
{
    public function calculate(array $params = []): string
    {
        assert(is_int($params['creator']));

        $developer = $this->mappings->findUser((string)$params['creator']) ?? 'Developer Unknown';

        return $this->mappings->findTeam($developer) ?? 'Team Unknown';
    }
}
