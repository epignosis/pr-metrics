<?php

declare(strict_types=1);

namespace Tests\GitHub\Repository;

use GuzzleHttp\Psr7\Response;
use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Repository\PullRequestFilesRepository;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

class PullRequestFilesRepositoryTest extends TestCase
{
    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCollectHandlesPagination(): void
    {
        // 1. ARRANGE
        $config = $this->createMock(Config::class);
        $httpClient = $this->createMock(HttpClientInterface::class);

        $config->method('get')->willReturnMap([
            ['github.token', 'test_token'],
            ['github.ignore_users', []],
            ['github.ignore_labels', []],
        ]);

        $linkHeader = '<https://api.github.com/resource?page=2>; rel="last"';
        $page1Body = json_encode([
            ['changes' => 10],
            ['changes' => 5],
        ], JSON_THROW_ON_ERROR);
        $page2Body = json_encode([
            ['changes' => 3],
        ], JSON_THROW_ON_ERROR);

        $httpClient->expects($this->exactly(2))
            ->method('send')
            ->willReturnOnConsecutiveCalls(
                new Response(200, ['Link' => $linkHeader], $page1Body),
                new Response(200, ['Link' => $linkHeader], $page2Body)
            );

        $repository = new PullRequestFilesRepository($config, $httpClient);
        $params = ['repo' => 'test/repo', 'pr_id' => 1];

        // 2. ACT
        $results = $repository->collect($params);

        // 3. ASSERT
        $this->assertCount(3, $results);
        $this->assertEquals(10, $results[0]['changes']);
        $this->assertEquals(5, $results[1]['changes']);
        $this->assertEquals(3, $results[2]['changes']);
    }
}
