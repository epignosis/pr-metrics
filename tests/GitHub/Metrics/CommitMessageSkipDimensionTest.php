<?php

declare(strict_types=1);

namespace Tests\GitHub\Metrics;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use TalentLMS\Metrics\GitHub\Mappings;
use TalentLMS\Metrics\GitHub\Metrics\CommitMessageSkipDimension;
use TalentLMS\Metrics\Helpers\Config;

class CommitMessageSkipDimensionTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCalculateReturnsTrueWhenMessageShouldBeSkipped(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('github.ignore_commit_messages', [])
            ->willReturn(['Merge branch', 'Merge pull request']);

        $dimension = new CommitMessageSkipDimension($mappings, $config);

        $params = [
            'commit_message' => 'Merge branch "feature/test"',
        ];

        $this->assertTrue($dimension->calculate($params));
    }

    /**
     * @throws Exception
     */
    public function testCalculateReturnsFalseWhenMessageShouldNotBeSkipped(): void
    {
        $mappings = $this->createMock(Mappings::class);
        $config = $this->createMock(Config::class);
        $config->method('get')
            ->with('github.ignore_commit_messages', [])
            ->willReturn(['Merge branch', 'Merge pull request']);

        $dimension = new CommitMessageSkipDimension($mappings, $config);

        $params = [
            'commit_message' => 'Fix bug',
        ];

        $this->assertFalse($dimension->calculate($params));
    }
}
