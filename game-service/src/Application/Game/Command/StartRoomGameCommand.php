<?php

declare(strict_types=1);

namespace App\Application\Game\Command;

final class StartRoomGameCommand
{
    public readonly string $roomId;
    public readonly string $requestingPlayerId;

    public function __construct(string $roomId, mixed $requestingPlayerId)
    {
        if (!is_string($requestingPlayerId) || trim($requestingPlayerId) === '') {
            throw new \InvalidArgumentException('player_id is required');
        }

        $this->roomId             = $roomId;
        $this->requestingPlayerId = trim($requestingPlayerId);
    }
}
