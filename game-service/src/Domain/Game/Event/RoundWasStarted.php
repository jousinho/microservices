<?php

declare(strict_types=1);

namespace App\Domain\Game\Event;

final class RoundWasStarted
{
    public readonly \DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $sessionId,
        public readonly string $roundId,
        public readonly int $roundNumber,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }
}
