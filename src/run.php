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
