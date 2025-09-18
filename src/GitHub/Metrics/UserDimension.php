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

use RuntimeException;

class UserDimension extends AbstractGitHubDimension
{
    public function calculate(array $params = []): string
    {
        assert(is_int($params['id']));
        assert(is_int($params['creator']));

        $developer = $this->mappings->findUser((string)$params['creator']);

        if (!$developer) {
            throw new RuntimeException('In PR #'.$params['id'].' found an unknown developer: '.$params['creator']);
        }

        return $developer;
    }
}