<?php

declare(strict_types=1);

namespace Tests\GitHub;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Metrics\CommitAuthorDimension;
use TalentLMS\Metrics\GitHub\Metrics\CommitMessageSkipDimension;
use TalentLMS\Metrics\GitHub\Metrics\CommitTeamDimension;
use TalentLMS\Metrics\GitHub\PullRequestContributionsCalculator;
use TalentLMS\Metrics\GitHub\Repository\PullRequestChangesRepository;
use TalentLMS\Metrics\GitHub\Repository\PullRequestCommitsRepository;
use TalentLMS\Metrics\HttpClient\HttpClientException;

class PullRequestContributionsCalculatorTest extends TestCase
{
    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculateAggregatesContributionsCorrectly(): void
    {
        // 1. ARRANGE
        $prDetails = ['pr_id' => 123, 'repo' => 'test/repo'];

        // Mock Repositories
        $commitsRepo = $this->createMock(PullRequestCommitsRepository::class);
        $changesRepo = $this->createMock(PullRequestChangesRepository::class);

        // Mock Dimensions
        $authorDimension = $this->createMock(CommitAuthorDimension::class);
        $teamDimension = $this->createMock(CommitTeamDimension::class);
        $skipDimension = $this->createMock(CommitMessageSkipDimension::class);

        // Define mock data
        $commits = [
            ['sha' => 'sha1', 'commit_date' => '2025-09-01T10:00:00Z', 'committer_name' => 'dev-a'],
            ['sha' => 'sha2', 'commit_date' => '2025-09-01T11:00:00Z', 'committer_name' => 'dev-a'],
            ['sha' => 'sha3', 'commit_date' => '2025-09-02T12:00:00Z', 'committer_name' => 'dev-b'],
            ['sha' => 'sha4', 'commit_date' => '2025-09-02T13:00:00Z', 'committer_name' => 'dev-a'],
            ['sha' => 'sha5', 'commit_date' => '2025-09-02T14:00:00Z', 'committer_name' => 'dev-b', 'commit_message' => 'skip me'],
        ];
        $changes = [
            ['sha' => 'sha1', 'additions' => 10, 'deletions' => 5], // 15
            ['sha' => 'sha2', 'additions' => 8, 'deletions' => 2],  // 10
            ['sha' => 'sha3', 'additions' => 1, 'deletions' => 1],   // 2
            ['sha' => 'sha4', 'additions' => 20, 'deletions' => 10], // 30
            ['sha' => 'sha5', 'additions' => 100, 'deletions' => 100], // Should be ignored
        ];

        // Configure mocks
        $commitsRepo->method('collect')->with($prDetails)->willReturn($commits);
        $changesRepo->method('collect')->with($prDetails)->willReturn($changes);

        $skipDimension->method('calculate')->willReturnCallback(
            fn (array $commit) => ($commit['commit_message'] ?? '') === 'skip me'
        );

        $authorDimension->method('calculate')->willReturnCallback(
            fn (array $commit) => $commit['committer_name']
        );

        $teamDimension->method('calculate')->willReturnCallback(
            fn (array $commit) => $commit['committer_name'] === 'dev-a' ? 'Team Alpha' : 'Team Bravo'
        );

        // Instantiate the calculator
        $calculator = new PullRequestContributionsCalculator(
            $commitsRepo,
            $changesRepo,
            $authorDimension,
            $teamDimension,
            $skipDimension
        );

        // 2. ACT
        $results = $calculator->calculate($prDetails);

        // 3. ASSERT
        $this->assertCount(3, $results);

        $expected = [
            'test/repo123dev-a2025-09-01' => [
                'developer' => 'dev-a', 'team' => 'Team Alpha', 'date' => '2025-09-01',
                'total_commits' => 2, 'total_changes' => 25,
            ],
            'test/repo123dev-b2025-09-02' => [
                'developer' => 'dev-b', 'team' => 'Team Bravo', 'date' => '2025-09-02',
                'total_commits' => 1, 'total_changes' => 2,
            ],
            'test/repo123dev-a2025-09-02' => [
                'developer' => 'dev-a', 'team' => 'Team Alpha', 'date' => '2025-09-02',
                'total_commits' => 1, 'total_changes' => 30,
            ],
        ];

        $this->assertEquals($expected, $results);
    }

    /**
     * @throws Exception
     * @throws HttpClientException
     * @throws JsonException
     */
    public function testCalculateWithNoCommits(): void
    {
        // 1. ARRANGE
        $prDetails = ['pr_id' => 456, 'repo' => 'test/empty-repo'];

        $commitsRepo = $this->createMock(PullRequestCommitsRepository::class);
        $commitsRepo->method('collect')->with($prDetails)->willReturn([]);

        $changesRepo = $this->createMock(PullRequestChangesRepository::class);
        $changesRepo->method('collect')->with($prDetails)->willReturn([]);

        $calculator = new PullRequestContributionsCalculator(
            $commitsRepo,
            $changesRepo,
            $this->createMock(CommitAuthorDimension::class),
            $this->createMock(CommitTeamDimension::class),
            $this->createMock(CommitMessageSkipDimension::class)
        );

        // 2. ACT
        $results = $calculator->calculate($prDetails);

        // 3. ASSERT
        $this->assertEmpty($results);
    }
}
