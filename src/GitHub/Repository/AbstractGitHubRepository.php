<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub\Repository;

use Psr\Http\Message\ResponseInterface;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;
use TalentLMS\Metrics\RepositoryInterface;

abstract class AbstractGitHubRepository implements RepositoryInterface
{
    protected const string BASE_URL = 'https://api.github.com/repos/';
    protected const string GRAPHQL_URL = 'https://api.github.com/graphql';
    private const string HEADER_LINK = 'link';
    private const string HEADER_RATE_LIMIT_REMAINING = 'x-ratelimit-remaining';
    private string $token;
    /** @var array<mixed> */
    protected array $ignoreUsers;
    /** @var array<mixed> */
    protected array $ignoreLabels;

    public function __construct(Config $config, private readonly HttpClientInterface $client)
    {
        $token = $config->get('github.token');
        $ignoreUsers = $config->get('github.ignore_users');
        $ignoreLabels = $config->get('github.ignore_labels');
        assert(is_string($token));
        assert(is_array($ignoreUsers));
        assert(is_array($ignoreLabels));

        $this->token = $token;
        $this->ignoreUsers = $ignoreUsers;
        $this->ignoreLabels = $ignoreLabels;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array<string, string> $headers
     * @param string|null $body
     * @return ResponseInterface
     * @throws HttpClientException
     */
    protected function retrieve(string $method, string $uri, array $headers = [], ?string $body = null): ResponseInterface
    {
        $response = $this->client->send($method, $uri, $headers, $body);

        if (!$this->client->lastResponseFromCache()) {
            $remainingApiUsage = -1;

            foreach ($response->getHeaders() as $name => $values) {
                if (strtolower($name) === self::HEADER_RATE_LIMIT_REMAINING) {
                    $remainingApiUsage = (int) $values[0];
                }
            }

            if ($remainingApiUsage >= 0) {
                if ($remainingApiUsage % 100 === 0) {
                    echo 'NOTICE: GitHub API remaining calls are '.$remainingApiUsage.PHP_EOL;
                }
            }
        }

        return $response;
    }

    protected function getPages(ResponseInterface $response): int
    {
        foreach ($response->getHeaders() as $name => $values) {
            if (strtolower($name) === self::HEADER_LINK) {
                if (preg_match('/page=(\d+)>; rel="last"$/m', $values[0], $matches)) {
                    return (int)$matches[1]; // The total number of pages
                }
            }
        }

        return 1; // Only a single page exists
    }

    /**
     * @return array<string, string>
     */
    protected function getHeaders(): array
    {
        return [
            'User-Agent' => 'PR-Metrics-Action',
            'Authorization' => 'Bearer '.$this->token,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }
}
