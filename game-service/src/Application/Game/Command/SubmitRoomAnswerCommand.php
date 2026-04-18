<?php

declare(strict_types=1);

namespace App\Application\Game\Command;

final class SubmitRoomAnswerCommand
{
    public readonly string $roomId;
    public readonly string $roundId;
    public readonly string $playerId;
    public readonly string $guess;

    public function __construct(string $roomId, string $roundId, mixed $playerId, mixed $guess)
    {
        if (!is_string($playerId) || trim($playerId) === '') {
            throw new \InvalidArgumentException('player_id is required');
        }

        if (!is_string($guess) || trim($guess) === '') {
            throw new \InvalidArgumentException('guess is required');
        }

        $this->roomId   = $roomId;
        $this->roundId  = $roundId;
        $this->playerId = trim($playerId);
        $this->guess    = trim($guess);
    }
}
