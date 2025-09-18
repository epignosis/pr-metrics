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

namespace TalentLMS\Metrics\GitHub\Repository;

use JsonException;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class PullRequestCommitsRepository extends AbstractGitHubRepository
{
    /**
     * @param array<mixed> $params
     * @return array<int, array{sha: string, committer_name: string, committer_email: string, commit_date: string, commit_message: string}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function collect(array $params = []): array
    {
        assert(is_string($params['repo']));
        assert(is_int($params['pr_id']));

        return $this->getPullRequestCommits($params['repo'], $params['pr_id']);
    }

    /**
     * @return array<int, array{sha: string, committer_name: string, committer_email: string, commit_date: string, commit_message: string}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function getPullRequestCommits(string $repo, int $pullRequest): array
    {
        $headers = $this->getHeaders();

        $currentPage = 0;
        $totalPages = -1;
        $filtered = [];

        do {
            $currentPage++;

            $url = self::BASE_URL.$repo.'/pulls/'.$pullRequest.'/commits?page='.$currentPage.'&per_page=100';
            $response = $this->retrieve('GET', $url, $headers);

            if ($totalPages === -1) {
                $totalPages = $this->getPages($response);
            }

            /** @var array<array<string, mixed>> $body */
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            foreach ($body as $commit) {
                assert(is_string($commit['sha']));
                assert(is_array($commit['commit']));
                assert(is_array($commit['commit']['author']));
                assert(is_string($commit['commit']['author']['name']));
                assert(is_string($commit['commit']['author']['email']));
                assert(is_string($commit['commit']['author']['date']));
                assert(is_string($commit['commit']['message']));
                assert(is_array($commit['author']) || $commit['author'] === null);

                if (isset($commit['author']['id'])) {
                    assert(is_int($commit['author']['id']));

                    if (in_array($commit['author']['id'], $this->ignoreUsers)) {
                        continue; // Skip certain users
                    }
                }

                $filtered[] = [
                    'sha' => $commit['sha'],
                    'committer_name' => $commit['commit']['author']['name'],
                    'committer_email' => $commit['commit']['author']['email'],
                    'commit_date' => $commit['commit']['author']['date'],
                    'commit_message' => $commit['commit']['message']
                ];
            }
        } while ($currentPage < $totalPages);

        return $filtered;
    }
}