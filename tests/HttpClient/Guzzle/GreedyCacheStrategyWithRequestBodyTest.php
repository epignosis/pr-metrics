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

namespace Tests\HttpClient\Guzzle;

use GuzzleHttp\Psr7\Request;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use TalentLMS\Metrics\HttpClient\Guzzle\GreedyCacheStrategyWithRequestBody;

class GreedyCacheStrategyWithRequestBodyTest extends TestCase
{
    /**
     * This is the single test method, driven by the data provider below.
     * It runs once for each dataset returned by cacheKeyDataProvider.
     */
    #[DataProvider('cacheKeyDataProvider')]
    public function testGetCacheKey(
        string $description,
        RequestInterface $request,
        ?KeyValueHttpHeader $varyHeaders,
        string $expectedKey
    ): void {
        // The test-specific subclass is created once here.
        $strategy = new class () extends GreedyCacheStrategyWithRequestBody {
            public function callGetCacheKey(RequestInterface $request, ?KeyValueHttpHeader $varyHeaders = null): string
            {
                return $this->getCacheKey($request, $varyHeaders);
            }
        };

        $cacheKey = $strategy->callGetCacheKey($request, $varyHeaders);

        $this->assertEquals($expectedKey, $cacheKey, "Test failed for case: {$description}");
    }

    /**
     * This data provider supplies all the test cases.
     * It's easy to add new scenarios here without writing more test logic.
     *
     * @return array<string, array{string, RequestInterface, KeyValueHttpHeader|null, string}>
     */
    public static function cacheKeyDataProvider(): array
    {
        // Case 1: Simple POST request with a body
        $request1 = new Request('POST', 'https://test.com/path', [], '{"foo":"bar"}');
        $expectedKey1 = hash('sha256', 'greedy'.$request1->getMethod().$request1->getUri().$request1->getBody());

        // Case 2: GET request with headers, varying by one of them
        $request2 = new Request('GET', 'https://test.com/path', ['Authorization' => 'Bearer token', 'Accept' => 'application/json']);
        $varyHeaders2 = new KeyValueHttpHeader(['Accept']); // Only vary by 'Accept'
        $cacheHeaders2 = ['Accept' => ['application/json']];
        $expectedKey2 = hash('sha256', 'greedy'.$request2->getMethod().$request2->getUri().json_encode($cacheHeaders2).$request2->getBody());

        // Case 3: Simple GET request without body and no vary headers
        $request3 = new Request('GET', 'https://test.com/path');
        $expectedKey3 = hash('sha256', 'greedy'.$request3->getMethod().$request3->getUri().$request3->getBody());

        // Case 4: GET request where a vary header is requested but not present in the request
        $request4 = new Request('GET', 'https://test.com/path', ['Accept' => 'application/json']);
        $varyHeaders4 = new KeyValueHttpHeader(['Accept', 'Authorization']); // Vary by 'Authorization' too
        $cacheHeaders4 = ['Accept' => ['application/json']]; // 'Authorization' is correctly ignored
        $expectedKey4 = hash('sha256', 'greedy'.$request4->getMethod().$request4->getUri().json_encode($cacheHeaders4).$request4->getBody());

        return [
            'POST with body' => ['POST with body', $request1, null, $expectedKey1],
            'GET with vary header' => ['GET with vary header', $request2, $varyHeaders2, $expectedKey2],
            'Simple GET' => ['Simple GET', $request3, null, $expectedKey3],
            'GET with missing vary header' => ['GET with missing vary header', $request4, $varyHeaders4, $expectedKey4],
        ];
    }
}