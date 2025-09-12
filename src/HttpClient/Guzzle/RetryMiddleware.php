<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\HttpClient\Guzzle;

use Closure;
use GuzzleRetry\GuzzleRetryMiddleware;
use TalentLMS\Metrics\Helpers\Config;

class RetryMiddleware
{
    /** @var array<string, mixed> $middlewareConfig */
    protected array $middlewareConfig;

    public function __construct(Config $config)
    {
        $this->middlewareConfig = [
            'max_retry_attempts' => $config->get('guzzle.retry.max_retry_attempts'),
            'retry_on_timeout' => $config->get('guzzle.retry.retry_on_timeout'),
            'retry_on_status' => $config->get('guzzle.retry.retry_on_status'),
        ];
    }

    public function get(): Closure
    {
        return GuzzleRetryMiddleware::factory($this->middlewareConfig);
    }
}
