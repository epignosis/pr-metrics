<?php

declare(strict_types=1);

namespace TalentLMS\Metrics;

use Carbon\Carbon;
use JsonException;
use TalentLMS\Metrics\Export\AbstractExport;
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

readonly class GatherGitHubMetrics
{
    public function __construct(
        private AbstractExport                     $report,
        private Config                             $config,
        private Sprints                            $sprints,
        private PullRequestsRepository             $prs,
        private UserDimension                      $userDimension,
        private TeamDimension                      $teamDimension,
        private TotalCommentsDimension             $totalCommentsDimension,
        private TotalReviewsDimension              $totalReviewsDimension,
        private TotalCommitsDimension              $totalCommitsDimension,
        private TotalChangesDimension              $totalChangesDimension,
        private TotalReviewCyclesDimension         $totalReviewCyclesDimension,
        private EndedAtDimension                   $endedAtDimension,
        private FirstReviewedAtDimension           $firstReviewedAtDimension,
        private PullRequestContributionsCalculator $contributionCalculator,
    ) {
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     */
    public function compile(): void
    {
        /** @var string $repo */
        $repo = $this->config->get('github.repository');
        /** @var bool $captureContributions */
        $captureContributions = $this->config->get('metrics.contributions');

        $headers = [
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

        if ($captureContributions) {
            $headers += [
                'Developer' => '[developer]',
                'Developer team' => '[developer_team]',
                'Commit date' => '[commited_at]',
                '# of developer commits' => '[developer_commits]',
                '# of developer changes' => '[developer_changes]',
            ];
        }

        $this->report->setHeaders($headers);

        $pullRequests = $this->prs->collect([
            'repo' => $repo,
        ]);

        foreach ($pullRequests as $pullRequest) {
            $prDetails = [
                'pr_id' => $pullRequest['id'],
                'repo' => $repo,
            ];

            $line = [
                // Basic info
                'number' => $pullRequest['id'],
                'state' => $pullRequest['state'],
                'created_at' => Carbon::parse($pullRequest['created_at'])->format('Y-m-d'),
                'merged' => $pullRequest['merged_at'] ? 'Yes' : 'No',
                'repository' => $repo,
                // Dimensions
                'creator' => $this->userDimension->calculate($pullRequest),
                'team' => $this->teamDimension->calculate($pullRequest),
                'total_comments' => $this->totalCommentsDimension->calculate($prDetails),
                'total_reviews' => $this->totalReviewsDimension->calculate($prDetails),
                'total_commits' => $this->totalCommitsDimension->calculate($prDetails),
                'total_changes' => $this->totalChangesDimension->calculate($prDetails),
                'closed_at' => $this->endedAtDimension->calculate($pullRequest),
                'review_cycles' => $this->totalReviewCyclesDimension->calculate($prDetails),
                'first_review_at' => $this->firstReviewedAtDimension->calculate($prDetails),
                'sprint' => $this->sprints->getCurrentSprintId(),
            ];

            if ($captureContributions) {
                $line += [
                    // Contribution details (not available on the first line)
                    'developer' => null,
                    'developer_team' => null,
                    'commited_at' => null,
                    'developer_commits' => null,
                    'developer_changes' => null,
                ];
            }

            $this->report->addLine($line);

            if ($captureContributions) {
                $contributions = $this->contributionCalculator->calculate($prDetails);

                foreach ($contributions as $contribution) {
                    $line['developer'] = $contribution['developer'];
                    $line['developer_team'] = $contribution['team'];
                    $line['commited_at'] = $contribution['date'];
                    $line['developer_commits'] = $contribution['total_commits'];
                    $line['developer_changes'] = $contribution['total_changes'];

                    $this->report->addLine($line);
                }
            }
        }

        $this->report->save();
    }
}
