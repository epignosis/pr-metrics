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

namespace TalentLMS\Metrics\HttpClient\Guzzle;

use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;

class GreedyCacheStrategyWithRequestBody extends GreedyCacheStrategy
{
    protected function getCacheKey(RequestInterface $request, ?KeyValueHttpHeader $varyHeaders = null): string
    {
        if (null === $varyHeaders || $varyHeaders->isEmpty()) {
            return hash(
                'sha256',
                'greedy'.$request->getMethod().$request->getUri().$request->getBody()
            );
        }

        $cacheHeaders = [];
        /** @var string $key */
        foreach ($varyHeaders as $key => $value) {
            if ($request->hasHeader($key)) {
                $cacheHeaders[$key] = $request->getHeader($key);
            }
        }

        return hash(
            'sha256',
            'greedy'.$request->getMethod().$request->getUri().json_encode($cacheHeaders).$request->getBody()
        );
    }
}
