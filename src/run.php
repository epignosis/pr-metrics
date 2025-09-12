<?php

declare(strict_types=1);

use DI\Container;
use TalentLMS\Metrics\Export\AbstractExport;
use TalentLMS\Metrics\Export\CSVExport;
use TalentLMS\Metrics\GatherGitHubMetrics;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientFactory;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

require __DIR__.'/bootstrap.php';

$container = new Container();

try {
    /** @var Config $config */
    $config = $container->get(Config::class);
    $container->set(HttpClientInterface::class, HttpClientFactory::get($config));

    $ghMetricsFile = __DIR__.'/../tmp/metrics.github.csv';
    $container->set(AbstractExport::class, new CSVExport($ghMetricsFile));

    /** @var GatherGitHubMetrics $ghMetrics */
    $ghMetrics = $container->get(GatherGitHubMetrics::class);
    $ghMetrics->compile();

    /** @var string $githubOutput */
    $githubOutput = $config->get('GITHUB_OUTPUT');
    file_put_contents($githubOutput, "github-metrics-file={$ghMetricsFile}\n", FILE_APPEND);

    exit(0);
} catch (Exception $e) {
    echo 'Error: '.$e->getMessage().PHP_EOL;
    exit(1);
}
