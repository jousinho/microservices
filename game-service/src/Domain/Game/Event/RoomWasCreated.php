<?php

declare(strict_types=1);

namespace App\Domain\Game\Event;

final class RoomWasCreated
{
    public readonly \DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $roomId,
        public readonly string $roomCode,
        public readonly int $difficulty,
        public readonly int $totalRounds,
        public readonly string $hostId,
        public readonly string $hostName,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }
}
