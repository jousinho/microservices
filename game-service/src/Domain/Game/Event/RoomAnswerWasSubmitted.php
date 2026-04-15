<?php

declare(strict_types=1);

namespace App\Domain\Game\Event;

final class RoomAnswerWasSubmitted
{
    public readonly \DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $roomId,
        public readonly string $roundId,
        public readonly string $playerId,
        public readonly string $guess,
        public readonly bool $isCorrect,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }
}
