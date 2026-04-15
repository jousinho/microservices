<?php

declare(strict_types=1);

namespace App\Domain\Game\Event;

final class RoomGameWasEnded
{
    public readonly \DateTimeImmutable $occurredOn;

    /** @param array<string, int> $scoreboard playerId => score */
    public function __construct(
        public readonly string $roomId,
        public readonly array $scoreboard,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }
}
