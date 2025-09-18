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

namespace TalentLMS\Metrics\Tests;

use JsonException;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\Export\AbstractExport;
use TalentLMS\Metrics\GatherGitHubMetrics;
use TalentLMS\Metrics\GitHub\Metrics\EndedAtDimension;
use TalentLMS\Metrics\GitHub\Metrics\FirstReviewedAtDimension;
use TalentLMS\Metrics\GitHub\Metrics\TeamDimension;
use TalentLMS\Metrics\GitHub\Metrics\TotalChangesDimension;
use TalentLMS\Metrics\GitHub\Metrics\TotalCommentsDimension;
use TalentLMS\Metrics\GitHub\Metrics\TotalCommitsDimension;
use TalentLMS\Metrics\GitHub\Metrics\TotalReviewCyclesDimension;
use TalentLMS\Metrics\GitHub\Metrics\TotalReviewsDimension;
use TalentLMS\Metrics\GitHub\Metrics\UserDimension;
use TalentLMS\Metrics\GitHub\PullRequestContributionsCalculator;
use TalentLMS\Metrics\GitHub\Repository\PullRequestsRepository;
use TalentLMS\Metrics\Helpers\Config;
use TalentLMS\Metrics\Helpers\Sprints;
use TalentLMS\Metrics\HttpClient\HttpClientException;

final class GatherGitHubMetricsTest extends TestCase
{
    private Sprints $sprints;
    private UserDimension $userDimension;
    private TeamDimension $teamDimension;
    private TotalCommentsDimension $totalCommentsDimension;
    private TotalReviewsDimension $totalReviewsDimension;
    private TotalCommitsDimension $totalCommitsDimension;
    private TotalChangesDimension $totalChangesDimension;
    private TotalReviewCyclesDimension $totalReviewCyclesDimension;
    private EndedAtDimension $endedAtDimension;
    private FirstReviewedAtDimension $firstReviewedAtDimension;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->sprints = $this->createMock(Sprints::class);
        $this->userDimension = $this->createMock(UserDimension::class);
        $this->teamDimension = $this->createMock(TeamDimension::class);
        $this->totalCommentsDimension = $this->createMock(TotalCommentsDimension::class);
        $this->totalReviewsDimension = $this->createMock(TotalReviewsDimension::class);
        $this->totalCommitsDimension = $this->createMock(TotalCommitsDimension::class);
        $this->totalChangesDimension = $this->createMock(TotalChangesDimension::class);
        $this->totalReviewCyclesDimension = $this->createMock(TotalReviewCyclesDimension::class);
        $this->endedAtDimension = $this->createMock(EndedAtDimension::class);
        $this->firstReviewedAtDimension = $this->createMock(FirstReviewedAtDimension::class);

        $this->sprints->method('getCurrentSprintId')->willReturn('sprint-1');
        $this->userDimension->method('calculate')->willReturn('test-user');
        $this->teamDimension->method('calculate')->willReturn('test-team');
        $this->totalCommentsDimension->method('calculate')->willReturn(5);
        $this->totalReviewsDimension->method('calculate')->willReturn(2);
        $this->totalCommitsDimension->method('calculate')->willReturn(3);
        $this->totalChangesDimension->method('calculate')->willReturn(100);
        $this->totalReviewCyclesDimension->method('calculate')->willReturn(1);
        $this->endedAtDimension->method('calculate')->willReturn('2023-01-02');
        $this->firstReviewedAtDimension->method('calculate')->willReturn('2023-01-01');
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     * @throws Exception
     */
    public function testCompileWithoutContributions(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->willReturnMap([
                ['github.repository', 'epignosis/pr-metrics'],
                ['metrics.contributions', false],
            ]);

        $pullRequest = [
            'id' => 123,
            'state' => 'closed',
            'created_at' => '2023-01-01T10:00:00Z',
            'merged_at' => '2023-01-02T12:00:00Z',
        ];

        $prs = $this->createMock(PullRequestsRepository::class);
        $prs->method('collect')
            ->with(['repo' => 'epignosis/pr-metrics'])
            ->willReturn([$pullRequest]);

        $expectedHeaders = [
            'Repository' => '[repository]',
            'Sprint' => '[sprint]',
            'Pull Request' => '[number]',
            'Creator' => '[creator]',
            'Team' => '[team]',
            'State' => '[state]',
            'Created date' => '[created_at]',
            'Closed date' => '[closed_at]',
            'First review date' => '[first_review_at]',
            'Merged?' => '[merged]',
            '# of comments' => '[total_comments]',
            '# of reviews' => '[total_reviews]',
            '# of review cycles' => '[review_cycles]',
            '# of commits' => '[total_commits]',
            '# of changes' => '[total_changes]',
        ];
        $expectedLine = [
            'number' => 123,
            'state' => 'closed',
            'created_at' => '2023-01-01',
            'merged' => 'Yes',
            'repository' => 'epignosis/pr-metrics',
            'creator' => 'test-user',
            'team' => 'test-team',
            'total_comments' => 5,
            'total_reviews' => 2,
            'total_commits' => 3,
            'total_changes' => 100,
            'closed_at' => '2023-01-02',
            'review_cycles' => 1,
            'first_review_at' => '2023-01-01',
            'sprint' => 'sprint-1',
        ];

        $report = $this->createMock(AbstractExport::class);
        $report->expects($this->once())
            ->method('setHeaders')
            ->with($expectedHeaders);
        $report->expects($this->once())
            ->method('addLine')
            ->with($expectedLine);
        $report->expects($this->once())->method('save');

        $contributionCalculator = $this->createMock(PullRequestContributionsCalculator::class);
        $contributionCalculator->expects($this->never())->method('calculate');

        $gatherGitHubMetrics = new GatherGitHubMetrics(
            $report,
            $config,
            $this->sprints,
            $prs,
            $this->userDimension,
            $this->teamDimension,
            $this->totalCommentsDimension,
            $this->totalReviewsDimension,
            $this->totalCommitsDimension,
            $this->totalChangesDimension,
            $this->totalReviewCyclesDimension,
            $this->endedAtDimension,
            $this->firstReviewedAtDimension,
            $contributionCalculator
        );

        $gatherGitHubMetrics->compile();
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     * @throws Exception
     */
    public function testCompileWithContributions(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->willReturnMap([
                ['github.repository', 'epignosis/pr-metrics'],
                ['metrics.contributions', true],
            ]);

        $pullRequest = [
            'id' => 123,
            'state' => 'closed',
            'created_at' => '2023-01-01T10:00:00Z',
            'merged_at' => '2023-01-02T12:00:00Z',
        ];

        $prs = $this->createMock(PullRequestsRepository::class);
        $prs->method('collect')
            ->with(['repo' => 'epignosis/pr-metrics'])
            ->willReturn([$pullRequest]);

        $expectedHeaders = [
            'Repository' => '[repository]',
            'Sprint' => '[sprint]',
            'Pull Request' => '[number]',
            'Creator' => '[creator]',
            'Team' => '[team]',
            'State' => '[state]',
            'Created date' => '[created_at]',
            'Closed date' => '[closed_at]',
            'First review date' => '[first_review_at]',
            'Merged?' => '[merged]',
            '# of comments' => '[total_comments]',
            '# of reviews' => '[total_reviews]',
            '# of review cycles' => '[review_cycles]',
            '# of commits' => '[total_commits]',
            '# of changes' => '[total_changes]',
            'Developer' => '[developer]',
            'Developer team' => '[developer_team]',
            'Commit date' => '[commited_at]',
            '# of developer commits' => '[developer_commits]',
            '# of developer changes' => '[developer_changes]',
        ];

        $baseLine = [
            'number' => 123,
            'state' => 'closed',
            'created_at' => '2023-01-01',
            'merged' => 'Yes',
            'repository' => 'epignosis/pr-metrics',
            'creator' => 'test-user',
            'team' => 'test-team',
            'total_comments' => 5,
            'total_reviews' => 2,
            'total_commits' => 3,
            'total_changes' => 100,
            'closed_at' => '2023-01-02',
            'review_cycles' => 1,
            'first_review_at' => '2023-01-01',
            'sprint' => 'sprint-1',
            'developer' => null,
            'developer_team' => null,
            'commited_at' => null,
            'developer_commits' => null,
            'developer_changes' => null,
        ];

        $contributionLine1 = $baseLine;
        $contributionLine1['developer'] = 'dev1';
        $contributionLine1['developer_team'] = 'dev-team-1';
        $contributionLine1['commited_at'] = '2023-01-01';
        $contributionLine1['developer_commits'] = 1;
        $contributionLine1['developer_changes'] = 50;

        $contributionLine2 = $baseLine;
        $contributionLine2['developer'] = 'dev2';
        $contributionLine2['developer_team'] = 'dev-team-2';
        $contributionLine2['commited_at'] = '2023-01-02';
        $contributionLine2['developer_commits'] = 2;
        $contributionLine2['developer_changes'] = 50;

        $addedLines = [];

        $report = $this->createMock(AbstractExport::class);
        $report->expects($this->once())
            ->method('setHeaders')
            ->with($expectedHeaders);
        $report->expects($this->exactly(3))
            ->method('addLine')
            ->willReturnCallback(function ($line) use (&$addedLines): void {
                $addedLines[] = $line;
            });
        $report->expects($this->once())->method('save');

        $contributions = [
            [
                'developer' => 'dev1',
                'team' => 'dev-team-1',
                'date' => '2023-01-01',
                'total_commits' => 1,
                'total_changes' => 50,
            ],
            [
                'developer' => 'dev2',
                'team' => 'dev-team-2',
                'date' => '2023-01-02',
                'total_commits' => 2,
                'total_changes' => 50,
            ],
        ];

        $contributionCalculator = $this->createMock(PullRequestContributionsCalculator::class);
        $contributionCalculator->method('calculate')->willReturn($contributions);

        $gatherGitHubMetrics = new GatherGitHubMetrics(
            $report,
            $config,
            $this->sprints,
            $prs,
            $this->userDimension,
            $this->teamDimension,
            $this->totalCommentsDimension,
            $this->totalReviewsDimension,
            $this->totalCommitsDimension,
            $this->totalChangesDimension,
            $this->totalReviewCyclesDimension,
            $this->endedAtDimension,
            $this->firstReviewedAtDimension,
            $contributionCalculator
        );

        $gatherGitHubMetrics->compile();

        $this->assertEquals($baseLine, $addedLines[0]);
        $this->assertEquals($contributionLine1, $addedLines[1]);
        $this->assertEquals($contributionLine2, $addedLines[2]);
    }
}