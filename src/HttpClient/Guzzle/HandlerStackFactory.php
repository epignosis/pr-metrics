<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\HttpClient\Guzzle;

use GuzzleHttp\HandlerStack;
use TalentLMS\Metrics\Helpers\Config;

class HandlerStackFactory
{
    public static function create(Config $config): HandlerStack
    {
        // Create default HandlerStack
        $stack = HandlerStack::create();

        if ($config->get('guzzle.retry.enabled')) {
            // Add retry middleware
            $retryMiddleware = new RetryMiddleware($config);
            $stack->push($retryMiddleware->get(), 'retry');
        }

        if ($config->get('guzzle.cache.enabled')) {
            // Add caching middleware
            $cacheMiddleware = new CacheMiddleware($config);
            $stack->push($cacheMiddleware->get(), 'cache');
        }

        return $stack;
    }
}
