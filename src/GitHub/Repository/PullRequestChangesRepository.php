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

namespace TalentLMS\Metrics\GitHub\Repository;

use JsonException;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class PullRequestChangesRepository extends AbstractGitHubRepository
{
    /**
     * @param array<mixed> $params
     * @return array<int, array{sha: string, additions: int, deletions: int}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function collect(array $params = []): array
    {
        assert(is_string($params['repo']));
        assert(is_int($params['pr_id']));

        return $this->getCommitChanges($params['repo'], $params['pr_id']);
    }

    /**
     * @return array<int, array{sha: string, additions: int, deletions: int}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function getCommitChanges(string $repo, int $pullRequest): array
    {
        $headers = $this->getHeaders();

        $repoParts = explode('/', $repo);
        $filtered = [];
        $after = 'null';

        do {
            $query = <<<GRAPHQL
query {
  repository(owner: "{$repoParts[0]}", name: "{$repoParts[1]}") {
    pullRequest(number: {$pullRequest}) {
      commits(first: 100, after: {$after}) {
        nodes {
          commit {
            oid
            additions,
            deletions
          }
        }
        pageInfo {
          endCursor
          hasNextPage
          hasPreviousPage
        }
      }
    }
  }
}
GRAPHQL;

            $body = json_encode(['query' => $query]);
            assert(is_string($body));

            $response = $this->retrieve('POST', self::GRAPHQL_URL, $headers, $body);

            /** @var array<string, mixed> $commits */
            $commits = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            assert(is_array($commits['data']));
            assert(is_array($commits['data']['repository']));
            assert(is_array($commits['data']['repository']['pullRequest']));
            assert(is_array($commits['data']['repository']['pullRequest']['commits']));
            assert(is_array($commits['data']['repository']['pullRequest']['commits']['nodes']));
            assert(is_array($commits['data']['repository']['pullRequest']['commits']['pageInfo']));
            assert(is_bool($commits['data']['repository']['pullRequest']['commits']['pageInfo']['hasNextPage']));
            assert(
                is_null($commits['data']['repository']['pullRequest']['commits']['pageInfo']['endCursor']) ||
                is_string($commits['data']['repository']['pullRequest']['commits']['pageInfo']['endCursor'])
            );

            foreach ($commits['data']['repository']['pullRequest']['commits']['nodes'] as $commit) {
                assert(is_array($commit));
                assert(is_array($commit['commit']));
                assert(is_string($commit['commit']['oid']));
                assert(is_int($commit['commit']['additions']));
                assert(is_int($commit['commit']['deletions']));

                $filtered[] = [
                    'sha' => $commit['commit']['oid'],
                    'additions' => $commit['commit']['additions'],
                    'deletions' => $commit['commit']['deletions'],
                ];
            }

            $hasNextPage = $commits['data']['repository']['pullRequest']['commits']['pageInfo']['hasNextPage'];
            $after = '"'.$commits['data']['repository']['pullRequest']['commits']['pageInfo']['endCursor'].'"';
        } while ($hasNextPage);

        return $filtered;
    }
}
