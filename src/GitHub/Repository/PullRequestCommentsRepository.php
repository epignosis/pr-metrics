<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Repository;

use JsonException;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class PullRequestCommentsRepository extends AbstractGitHubRepository
{
    /**
     * @param array<mixed> $params
     * @return array<int, array{author: int}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function collect(array $params = []): array
    {
        assert(is_string($params['repo']));
        assert(is_int($params['pr_id']));

        return $this->getIssueComments($params['repo'], $params['pr_id']);
    }

    /**
     * @return array<int, array{author: int}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function getIssueComments(string $repo, int $number): array
    {
        $headers = $this->getHeaders();

        $currentPage = 0;
        $totalPages = -1;
        $filtered = [];

        do {
            $currentPage++;

            $url = self::BASE_URL.$repo.'/issues/'.$number.'/comments?page='.$currentPage.'&per_page=100';
            $response = $this->retrieve('GET', $url, $headers);

            if ($totalPages === -1) {
                $totalPages = $this->getPages($response);
            }

            /** @var array<array<string, mixed>> $body */
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            foreach ($body as $comment) {
                assert(is_array($comment['user']));
                assert(is_int($comment['user']['id']));

                if (in_array($comment['user']['id'], $this->ignoreUsers)) {
                    continue; // Skip certain users
                }

                $filtered[] = [
                    'author' => $comment['user']['id'],
                ];
            }
        } while ($currentPage < $totalPages);

        return $filtered;
    }
}
