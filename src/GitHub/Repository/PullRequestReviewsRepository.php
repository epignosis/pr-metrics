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

class PullRequestReviewsRepository extends AbstractGitHubRepository
{
    /**
     * @param array<mixed> $params
     * @return array<int, array{approver: int, state: string, submitted_at: string, commit_id: string}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function collect(array $params = []): array
    {
        assert(is_string($params['repo']));
        assert(is_int($params['pr_id']));

        return $this->getPullRequestReviews($params['repo'], $params['pr_id']);
    }

    /**
     * @return array<int, array{approver: int, state: string, submitted_at: string, commit_id: string}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function getPullRequestReviews(string $repo, int $pullRequest): array
    {
        $headers = $this->getHeaders();

        $currentPage = 0;
        $totalPages = -1;
        $filtered = [];

        do {
            $currentPage++;

            $url = self::BASE_URL.$repo.'/pulls/'.$pullRequest.'/reviews?page='.$currentPage.'&per_page=100';
            $response = $this->retrieve('GET', $url, $headers);

            if ($totalPages === -1) {
                $totalPages = $this->getPages($response);
            }

            /** @var array<array<string, mixed>> $body */
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            foreach ($body as $review) {
                if (!isset($review['submitted_at'])) {
                    continue; // Skip pending reviews
                }

                // Validate structure
                assert(is_array($review['user']));
                assert(is_int($review['user']['id']));
                assert(is_string($review['state']));
                assert(is_string($review['submitted_at']));
                assert(is_string($review['commit_id']));

                $filtered[] = [
                    'approver' => $review['user']['id'],
                    'state' => $review['state'],
                    'submitted_at' => $review['submitted_at'],
                    'commit_id' => $review['commit_id'],
                ];
            }
        } while ($currentPage < $totalPages);

        return $filtered;
    }
}
