<?php

declare(strict_types=1);

namespace App\Application\Game\Message;

final class GameEndedMessage
{
    public function __construct(
        public readonly string $roomId,
        /** @var array<string, int> playerId => score */
        public readonly array $scoreboard,
    ) {}
}
