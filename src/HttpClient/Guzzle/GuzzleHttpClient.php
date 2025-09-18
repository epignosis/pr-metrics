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
