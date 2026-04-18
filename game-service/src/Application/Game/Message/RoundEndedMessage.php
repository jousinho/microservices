<?php

declare(strict_types=1);

namespace App\Application\Game\Message;

final class RoundEndedMessage
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $roundId,
        public readonly int $roundNumber,
        /** @var array<string, int> playerId => score */
        public readonly array $scores,
    ) {}
}
