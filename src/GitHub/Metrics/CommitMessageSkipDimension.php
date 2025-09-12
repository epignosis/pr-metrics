<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Metrics;

use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\Helpers\Config;

class CommitMessageSkipDimension extends AbstractGitHubDimension
{
    public function __construct(Mappings $mappings, private readonly Config $config)
    {
        parent::__construct($mappings);
    }

    public function calculate(array $params = []): bool
    {
        assert(is_string($params['commit_message']));

        $skipCommit = false;
        /** @var array<string> $messages */
        $messages = $this->config->get('github.ignore_commit_messages', []);

        foreach ($messages as $message) {
            if (str_contains($params['commit_message'], $message)) {
                $skipCommit = true;
                break;
            }
        }

        return $skipCommit;
    }
}
