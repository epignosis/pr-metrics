<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\HttpClient;

use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\Guzzle\GuzzleHttpClient;

class HttpClientFactory
{
    //private const string GUZZLE = 'guzzle';

    public static function get(Config $config): HttpClientInterface
    {
        $httpClient = $config->get('httpclient');

        return match ($httpClient) {
            default => new GuzzleHttpClient($config),
        };
    }
}
