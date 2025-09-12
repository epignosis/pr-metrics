<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Repository;

use JsonException;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class PullRequestFilesRepository extends AbstractGitHubRepository
{
    /**
     * @param array<mixed> $params
     * @return array<int, array{changes: int}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function collect(array $params = []): array
    {
        assert(is_string($params['repo']));
        assert(is_int($params['pr_id']));

        return $this->getPullRequestFiles($params['repo'], $params['pr_id']);
    }

    /**
     * We get the files just to calculate the total number of changes (additions + deletions).
     *
     * @return array<int, array{changes: int}>
     *
     * @throws HttpClientException
     * @throws JsonException
     */
    public function getPullRequestFiles(string $repo, int $pullRequest): array
    {
        $headers = $this->getHeaders();

        $currentPage = 0;
        $totalPages = -1;
        $filtered = [];

        do {
            $currentPage++;

            $url = self::BASE_URL.$repo.'/pulls/'.$pullRequest.'/files?page='.$currentPage.'&per_page=30';
            $response = $this->retrieve('GET', $url, $headers);

            if ($totalPages === -1) {
                $totalPages = $this->getPages($response);
            }

            /** @var array<array<string, mixed>> $body */
            $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            foreach ($body as $file) {
                assert(is_int($file['changes']));

                $filtered[] = ['changes' => $file['changes']];
            }
        } while ($currentPage < $totalPages);

        return $filtered;
    }
}
