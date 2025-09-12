<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\HttpClient\Guzzle;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Psr\Http\Message\ResponseInterface;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class GuzzleHttpClient implements HttpClientInterface
{
    protected Client $client;
    protected bool $lastResponseFromCache = false;

    public function __construct(Config $config)
    {
        $this->client = new Client([
            'timeout' => $config->get('guzzle.timeout'),
            'handler' => HandlerStackFactory::create($config),
        ]);
    }

    /**
     * @throws HttpClientException
     */
    public function send(string $method, string $uri, array $headers = [], string $body = null): ResponseInterface
    {
        try {
            // Send the request
            $request = new Request($method, $uri, $headers, $body);
            $response = $this->client->send($request);

            $this->lastResponseFromCache = false;

            foreach ($response->getHeaders() as $name => $values) {
                // Don't report API usage if the response was served from cache
                if (
                    strtolower($name) === strtolower(CacheMiddleware::HEADER_CACHE_INFO) &&
                    $values[0] === CacheMiddleware::HEADER_CACHE_HIT
                ) {
                    $this->lastResponseFromCache = true;
                }
            }

            return $response;
        } catch (GuzzleException $e) {
            throw new HttpClientException('HTTP request failed: '.$e->getMessage(), 0, $e);
        }
    }

    public function lastResponseFromCache(): bool
    {
        return $this->lastResponseFromCache;
    }
}
