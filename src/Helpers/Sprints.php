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

namespace TalentLMS\Metrics\Helpers;

use Carbon\Carbon;

class Sprints
{
    /** @var array<string, string> $sprints */
    private array $sprints = [];

    public function __construct(Config $config)
    {
        /** @var string $startDate */
        $startDate = $config->get('sprint.start_date', '2025-01-06');
        $start = Carbon::createFromFormat('Y-m-d', $startDate); // First sprint start date
        $now = Carbon::now();

        assert(isset($start)); // Ensure $start is defined

        $inSprint = false;
        $startYear = $start->year;
        $sprintCounter = 1;

        while ($start->lessThanOrEqualTo($now)) {
            $currentYear = $start->year;

            if ($currentYear > $startYear && !$inSprint) {
                // If we moved to a new year, reset the sprint count
                $startYear = $currentYear;
                $sprintCounter = 1;
            }

            $weekOfYear = $this->getSprintIndex($start);
            $this->sprints[$weekOfYear] = $startYear.' Sprint '.$sprintCounter;

            if ($inSprint) { // This block is for the second week of the sprint
                $inSprint = false;
                $sprintCounter++;
            } else { // This block is for the first week of the sprint
                $inSprint = true;
            }

            $start->addWeek();
        }
    }

    public function getCurrentSprintId(): string
    {
        $weekOfYear = $this->getSprintIndex(Carbon::now());

        return $this->sprints[$weekOfYear] ?? 'Sprint not found';
    }

    private function getSprintIndex(Carbon $date): string
    {
        $weekOfYear = $date->year.'_'.$date->isoWeek;

        if ($date->month === 12 && $date->isoWeek < 3) {
            $weekOfYear = ($date->year + 1).'_'.$date->isoWeek;
        }

        return $weekOfYear;
    }
}
