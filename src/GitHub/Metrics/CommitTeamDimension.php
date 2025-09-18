<?php

// SPDX-License-Identifier: Apache-2.0
//
// Copyright 2025 Epignosis LLC
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
