<?php

declare(strict_types=1);

namespace TalentLMS\Metrics\GitHub;

use JsonException;
use TalentLMS\Metrics\Helpers\Config;

class Mappings
{
    /** @var array<string, string> $userIndex */
    private array $userIndex;
    /** @var array<string, string> $developerIndex */
    private array $developerIndex;
    /** @var array<string, string> $teamIndex */
    private array $teamIndex;

    /**
     * @throws JsonException
     */
    public function __construct(private readonly Config $config)
    {
        /** @var string $mappingFile */
        $mappingFile = $this->config->get('github.mapping_file');
        $mappingPath = __DIR__.'/../../'.$mappingFile;
        /** @var array<string, array<string, string>> $mappings */
        $mappings = is_file($mappingPath) ? json_decode(file_get_contents($mappingPath) ?: '{}', true, 512, JSON_THROW_ON_ERROR) : [];

        $this->userIndex = $mappings['user_index'] ?? [];
        $this->developerIndex = $mappings['developer_index'] ?? [];
        $this->teamIndex = $mappings['team_index'] ?? [];
    }

    public function findUser(string $username, string|null $default = null): string|null
    {
        if (array_key_exists($username, $this->userIndex)) {
            return $this->userIndex[$username];
        }

        return $default;
    }

    public function findDeveloper(string $username, string|null $default = null): string|null
    {
        if (array_key_exists($username, $this->developerIndex)) {
            return $this->developerIndex[$username];
        }

        return $default;
    }

    public function findTeam(string $username, string|null $default = null): string|null
    {
        if (array_key_exists($username, $this->teamIndex)) {
            return $this->teamIndex[$username];
        }

        return $default;
    }
}
