<?php

// SPDX-License-Identifier: Apache-2.0
//
// Copyright 2025 Epignosis, Inc.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.

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