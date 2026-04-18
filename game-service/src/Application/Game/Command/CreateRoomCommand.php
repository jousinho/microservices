<?php

declare(strict_types=1);

namespace App\Application\Game\Command;

use App\Domain\Game\ValueObject\Difficulty;

final class CreateRoomCommand
{
    public readonly string $hostName;
    public readonly int $difficulty;
    public readonly int $totalRounds;

    public function __construct(mixed $hostName, mixed $difficulty, mixed $totalRounds)
    {
        if (!is_string($hostName) || trim($hostName) === '') {
            throw new \InvalidArgumentException('host_name is required');
        }

        if (!is_int($difficulty) || !in_array($difficulty, Difficulty::validDifficultyLevels(), true)) {
            throw new \InvalidArgumentException('difficulty must be 1, 2 or 3');
        }

        if (!is_int($totalRounds) || $totalRounds < 1) {
            throw new \InvalidArgumentException('total_rounds must be a positive integer');
        }

        $this->hostName    = trim($hostName);
        $this->difficulty  = $difficulty;
        $this->totalRounds = $totalRounds;
    }
}
