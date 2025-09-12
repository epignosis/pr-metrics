<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\HttpClient;

use Psr\Http\Message\ResponseInterface;

interface HttpClientInterface
{
    /**
     * @param string $method
     * @param string $uri
     * @param array<string, string> $headers
     * @param string|null $body
     * @return ResponseInterface
     * @throws HttpClientException
     */
    public function send(string $method, string $uri, array $headers = [], string $body = null): ResponseInterface;

    public function lastResponseFromCache(): bool;
}
