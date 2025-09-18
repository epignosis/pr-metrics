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

namespace TalentLMS\Metrics\GitHub;

use Carbon\Carbon;
use JsonException;
use TalentLMS\Metrics\GitHub\Metrics\CommitAuthorDimension;
use TalentLMS\Metrics\GitHub\Metrics\CommitMessageSkipDimension;
use TalentLMS\Metrics\GitHub\Metrics\CommitTeamDimension;
use TalentLMS\Metrics\GitHub\Repository\PullRequestChangesRepository;
use TalentLMS\Metrics\GitHub\Repository\PullRequestCommitsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

readonly class PullRequestContributionsCalculator
{
    public function __construct(
        private PullRequestCommitsRepository $commits,
        private PullRequestChangesRepository $changes,
        private CommitAuthorDimension        $commitAuthorDimension,
        private CommitTeamDimension          $commitTeamDimension,
        private CommitMessageSkipDimension   $commitMessageSkipDimension,
    ) {
    }

    /**
     * @param array{pr_id: int, repo: string} $prDetails
     * @return array<string, array{developer: string, team: string, date: string, total_commits: int, total_changes: int}>
     * @throws HttpClientException
     * @throws JsonException
     */
    public function calculate(array $prDetails): array
    {
        $commits = $this->commits->collect($prDetails);
        $changes = $this->changes->collect($prDetails);

        $results = [];

        foreach ($commits as $commit) {
            if ($this->commitMessageSkipDimension->calculate($commit)) {
                continue;
            }

            $developer = $this->commitAuthorDimension->calculate($commit);
            $team = $this->commitTeamDimension->calculate($commit);
            $date = Carbon::parse($commit['commit_date'])->format('Y-m-d');
            $key = $prDetails['repo'].$prDetails['pr_id'].$developer.$date;

            if (!array_key_exists($key, $results)) {
                $results[$key] = [
                    'developer' => $developer,
                    'team' => $team,
                    'date' => $date,
                    'total_commits' => 0,
                    'total_changes' => 0,
                ];
            }

            $results[$key]['total_commits']++;

            foreach ($changes as $change) {
                if ($change['sha'] !== $commit['sha']) {
                    continue;
                }

                $loc = $change['additions'] + $change['deletions'];

                if (!array_key_exists($key, $results)) {
                    $results[$key] = [
                        'developer' => $developer,
                        'team' => $team,
                        'date' => $date,
                        'total_commits' => 0,
                        'total_changes' => 0,
                    ];
                }

                $results[$key]['total_changes'] += $loc;
            }
        }

        return $results;
    }
}