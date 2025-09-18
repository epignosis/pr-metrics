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

use Carbon\Carbon;
use JsonException;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class PullRequestsRepository extends AbstractGitHubRepository
{
    /**
     * @param array<string> $params
     * @return array<int, array{id: int, creator: int, state: string, title: string, created_at: string, closed_at: string|null, merged_at: string|null}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function collect(array $params = []): array
    {
        return $this->getAllPRs($params['repo']);
    }

    /**
     * @return array<int, array{id: int, creator: int, state: string, title: string, created_at: string, closed_at: string|null, merged_at: string|null}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    private function getAllPRs(string $repo): array
    {
        $open = $this->getOpenPRs($repo);
        $closed = $this->getClosedPRs($repo);

        return array_merge($open, $closed);
    }

    /**
     * @return array<int, array{id: int, creator: int, state: string, title: string, created_at: string, closed_at: string|null, merged_at: string|null}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    protected function getOpenPRs(string $repo): array
    {
        return $this->getPullRequests($repo, 'open', null);
    }

    /**
     * @return array<int, array{id: int, creator: int, state: string, title: string, created_at: string, closed_at: string|null, merged_at: string|null}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    protected function getClosedPRs(string $repo, ?Carbon $notBefore = null): array
    {
        return $this->getPullRequests($repo, 'closed', $notBefore ?? Carbon::now()->subDays(30));
    }

    /**
     * @return array<int, array{id: int, creator: int, state: string, title: string, created_at: string, closed_at: string|null, merged_at: string|null}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    private function getPullRequests(string $repo, string $state, ?Carbon $notBefore): array
    {
        $headers = $this->getHeaders();

        $currentPage = 0;
        $totalPages = -1;
        $filtered = [];

        do {
            $currentPage++;

            $url = self::BASE_URL.$repo.'/pulls?state='.$state.'&page='.$currentPage.'&per_page=100';
            $response = $this->retrieve('GET', $url, $headers);

            if ($totalPages === -1) {
                $totalPages = $this->getPages($response);
            }

            /** @var array<array<string, mixed>> $body */
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            foreach ($body as $pr) {
                // Validate structure using assertions
                assert(is_bool($pr['draft']));
                assert(is_array($pr['user']));
                assert(is_int($pr['user']['id']));
                assert(is_array($pr['labels']));
                assert(is_int($pr['number']));
                assert(is_string($pr['title']));
                assert(is_string($pr['created_at']));
                assert($pr['closed_at'] === null || is_string($pr['closed_at']));
                assert($pr['merged_at'] === null || is_string($pr['merged_at']));

                if ($pr['draft']) {
                    continue; // Skip draft PRs
                }

                if (in_array($pr['user']['id'], $this->ignoreUsers)) {
                    continue; // Skip certain users
                }

                if ($notBefore !== null && Carbon::parse($pr['created_at']) < $notBefore) {
                    $currentPage = $totalPages; // Stop the loop
                    break;
                }

                // Skip PRs with the "release" label
                foreach ($pr['labels'] as $label) {
                    assert(is_array($label));
                    assert(is_string($label['name']));

                    if (in_array($label['name'], $this->ignoreLabels)) {
                        continue 2;
                    }
                }

                $filtered[] = [
                    'id' => $pr['number'],
                    'creator' => $pr['user']['id'],
                    'state' => $state,
                    'title' => $pr['title'],
                    'created_at' => $pr['created_at'],
                    'closed_at' => $pr['closed_at'],
                    'merged_at' => $pr['merged_at'],
                ];
            }
        } while ($currentPage < $totalPages);

        return $filtered;
    }
}
