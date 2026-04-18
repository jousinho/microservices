<?php

declare(strict_types=1);

namespace App\Application\Game\Command;

final class JoinRoomCommand
{
    public readonly string $roomCode;
    public readonly string $playerName;

    public function __construct(mixed $roomCode, mixed $playerName)
    {
        if (!is_string($roomCode) || trim($roomCode) === '') {
            throw new \InvalidArgumentException('room_code is required');
        }

        if (!is_string($playerName) || trim($playerName) === '') {
            throw new \InvalidArgumentException('player_name is required');
        }

        $this->roomCode   = strtoupper(trim($roomCode));
        $this->playerName = trim($playerName);
    }
}
