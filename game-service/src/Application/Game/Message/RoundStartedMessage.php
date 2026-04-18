<?php

declare(strict_types=1);

namespace App\Application\Game\Message;

final class RoundStartedMessage
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $roundId,
        public readonly int $roundNumber,
        public readonly string $noteId,
        public readonly int $difficulty,
    ) {}
}
