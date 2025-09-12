<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\HttpClient\Guzzle;

use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface;
use League\Flysystem\Local\LocalFilesystemAdapter;
use TalentLMS\Metrics\Helpers\Config;

class CacheMiddleware
{
    /** @var array<string, mixed> $middlewareConfig */
    private array $middlewareConfig;

    public function __construct(Config $config)
    {
        $this->middlewareConfig = [
            'cache_path' => $config->get('guzzle.cache.path'),
            'default_ttl' => $config->get('guzzle.cache.ttl'),
        ];
    }

    public function get(): \Kevinrob\GuzzleCache\CacheMiddleware
    {
        // Add caching middleware
        $cacheMiddleware = new \Kevinrob\GuzzleCache\CacheMiddleware(
            $this->getStrategy()
        );
        $cacheMiddleware->setHttpMethods([
            'GET' => true,
            'POST' => true
        ]);

        return $cacheMiddleware;
    }

    private function getStorage(): CacheStorageInterface
    {
        /** @var string $cachePath */
        $cachePath = $this->middlewareConfig['cache_path'];

        return new FlysystemStorage(
            new LocalFilesystemAdapter(__DIR__.'/../../../'.$cachePath)
        );
    }

    private function getStrategy(): CacheStrategyInterface
    {
        return new GreedyCacheStrategyWithRequestBody(
            $this->getStorage(),
            $this->middlewareConfig['default_ttl']
        );
    }
}
