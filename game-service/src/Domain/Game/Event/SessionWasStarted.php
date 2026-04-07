<?php

declare(strict_types=1);

namespace App\Domain\Game\Event;

final class SessionWasStarted
{
    public readonly \DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $sessionId,
        public readonly int $difficulty,
        public readonly int $totalRounds,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }
}
