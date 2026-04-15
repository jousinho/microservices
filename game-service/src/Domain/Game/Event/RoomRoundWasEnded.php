<?php

declare(strict_types=1);

namespace App\Domain\Game\Event;

final class RoomRoundWasEnded
{
    public readonly \DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $roomId,
        public readonly string $roundId,
        public readonly int $roundNumber,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }
}
