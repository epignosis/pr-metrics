<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use RuntimeException;

class UserDimension extends AbstractGitHubDimension
{
    public function calculate(array $params = []): string
    {
        assert(is_int($params['id']));
        assert(is_int($params['creator']));

        $developer = $this->mappings->findUser((string)$params['creator']);

        if (!$developer) {
            throw new RuntimeException('In PR #'.$params['id'].' found an unknown developer: '.$params['creator']);
        }

        return $developer;
    }
}
