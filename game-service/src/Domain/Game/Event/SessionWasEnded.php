<?php

declare(strict_types=1);

namespace App\Domain\Game\Event;

final class SessionWasEnded
{
    public readonly \DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $sessionId,
        public readonly int $finalScore,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }
}
