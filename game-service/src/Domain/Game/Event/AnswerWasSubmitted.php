<?php

declare(strict_types=1);

namespace App\Domain\Game\Event;

final class AnswerWasSubmitted
{
    public readonly \DateTimeImmutable $occurredOn;

    public function __construct(
        public readonly string $sessionId,
        public readonly string $roundId,
        public readonly string $guess,
        public readonly bool $isCorrect,
    ) {
        $this->occurredOn = new \DateTimeImmutable();
    }
}
