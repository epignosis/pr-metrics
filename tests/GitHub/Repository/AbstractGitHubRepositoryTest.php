<?php

declare(strict_types=1);

namespace Tests\GitHub\Repository;

use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use TalentLMS\Metrics\GitHub\Repository\AbstractGitHubRepository;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\HttpClient\HttpClientException;
use TalentLMS\Metrics\HttpClient\HttpClientInterface;

/**
 * A test-specific subclass to expose protected methods for testing.
 */
class TestableGitHubRepository extends AbstractGitHubRepository
{
    /**
     * @param string $method
     * @param string $uri
     * @param array<string, string> $headers
     * @param string|null $body
     * @return ResponseInterface
     * @throws HttpClientException
     */
    public function testRetrieve(string $method, string $uri, array $headers = [], string $body = null): ResponseInterface
    {
        return $this->retrieve($method, $uri, $headers, $body);
    }

    public function testGetPages(ResponseInterface $response): int
    {
        return $this->getPages($response);
    }

    /**
     * @return array<string, string>
     */
    public function testGetHeaders(): array
    {
        return $this->getHeaders();
    }

    public function collect(array $params = []): array
    {
        return []; // Not used in this test
    }
}

class AbstractGitHubRepositoryTest extends TestCase
{
    private Config $config;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(Config::class);
        $this->config->method('get')->willReturnMap([
            ['github.token', 'test_token'],
            ['github.ignore_users', [123, 456]],
            ['github.ignore_labels', ['release', 'wip']],
        ]);
    }

    /**
     * @throws Exception
     */
    public function testConstructorSetsPropertiesCorrectly(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $repository = new TestableGitHubRepository($this->config, $httpClient);

        $reflector = new ReflectionClass(AbstractGitHubRepository::class);

        $tokenProp = $reflector->getProperty('token');
        $this->assertEquals('test_token', $tokenProp->getValue($repository));

        $usersProp = $reflector->getProperty('ignoreUsers');
        $this->assertEquals([123, 456], $usersProp->getValue($repository));

        $labelsProp = $reflector->getProperty('ignoreLabels');
        $this->assertEquals(['release', 'wip'], $labelsProp->getValue($repository));
    }

    /**
     * @throws Exception
     */
    public function testGetHeadersReturnsCorrectFormat(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $repository = new TestableGitHubRepository($this->config, $httpClient);

        $headers = $repository->testGetHeaders();

        $this->assertEquals('Bearer test_token', $headers['Authorization']);
        $this->assertEquals('application/vnd.github+json', $headers['Accept']);
    }

    /**
     * @throws Exception
     */
    public function testGetPagesReturnsCorrectPageCountFromLinkHeader(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $repository = new TestableGitHubRepository($this->config, $httpClient);
        $linkHeader = '<https://api.github.com/resource?page=2>; rel="next", <https://api.github.com/resource?page=5>; rel="last"';
        $response = new Response(200, ['Link' => $linkHeader]);

        $this->assertEquals(5, $repository->testGetPages($response));
    }

    /**
     * @throws Exception
     */
    public function testGetPagesReturnsOneWhenNoLinkHeader(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $repository = new TestableGitHubRepository($this->config, $httpClient);
        $response = new Response(200, []);

        $this->assertEquals(1, $repository->testGetPages($response));
    }

    /**
     * @throws HttpClientException
     * @throws Exception
     */
    public function testRetrieveCallsHttpClientSend(): void
    {
        $httpClient = $this->createMock(HttpClientInterface::class);
        $repository = new TestableGitHubRepository($this->config, $httpClient);
        $response = new Response(200, [], '{"data":"ok"}');

        $httpClient->expects($this->once())
            ->method('send')
            ->with('GET', 'https://example.com', [], null)
            ->willReturn($response);

        $result = $repository->testRetrieve('GET', 'https://example.com');
        $this->assertSame($response, $result);
    }
}
