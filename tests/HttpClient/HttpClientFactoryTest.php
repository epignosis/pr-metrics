<?php

declare(strict_types=1);

namespace Tests\HttpClient;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\Guzzle\GuzzleHttpClient;
use TalentLMS\Metrics\HttpClient\HttpClientFactory;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class HttpClientFactoryTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testGetReturnsGuzzleHttpClient(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')->willReturnMap([
            ['httpclient', 'Guzzle'],
            ['guzzle.timeout', 5],
            ['guzzle.retry.enabled', true],
            ['guzzle.retry.max_retry_attempts', 5],
            ['guzzle.retry.retry_on_timeout', true],
            ['guzzle.retry.retry_on_status', '500,502,503,504'],
            ['guzzle.cache.enabled', true],
            ['guzzle.cache.ttl', 14400],
            ['guzzle.cache.path', 'tmp/cache'],
        ]);

        $httpClient = HttpClientFactory::get($config);
        $this->assertInstanceOf(HttpClientInterface::class, $httpClient);
        $this->assertInstanceOf(GuzzleHttpClient::class, $httpClient);
    }
}
