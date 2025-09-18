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

namespace TalentLMS\Metrics\GitHub\Metrics;

use Carbon\Carbon;

class EndedAtDimension extends AbstractGitHubDimension
{
    public function calculate(array $params = []): ?string
    {
        $endDate = null;

        assert(is_string($params['state']));
        assert(!isset($params['merged_at']) || is_string($params['merged_at']));
        assert(!isset($params['closed_at']) || is_string($params['closed_at']));

        if ($params['state'] === 'closed') {
            /** @var string $closedOrMergedAt */
            $closedOrMergedAt = $params['merged_at'] ?? $params['closed_at'];
            $endDate = Carbon::parse($closedOrMergedAt)->format('Y-m-d');
        }

        return $endDate;
    }
}