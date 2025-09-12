<?php

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
