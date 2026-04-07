<?php

declare(strict_types=1);

namespace App\Domain\Game\Entity;

final class Answer
{
    private function __construct(
        private readonly string $id,
        private readonly string $roundId,
        private readonly string $guess,
        private readonly bool $isCorrect,
        private readonly int $responseTimeMs,
        private readonly \DateTimeImmutable $submittedAt,
    ) {}

    public static function create(
        string $id,
        string $roundId,
        string $guess,
        bool $isCorrect,
        int $responseTimeMs,
    ): self {
        return new self(
            id: $id,
            roundId: $roundId,
            guess: $guess,
            isCorrect: $isCorrect,
            responseTimeMs: $responseTimeMs,
            submittedAt: new \DateTimeImmutable(),
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function roundId(): string
    {
        return $this->roundId;
    }

    public function guess(): string
    {
        return $this->guess;
    }

    public function isCorrect(): bool
    {
        return $this->isCorrect;
    }

    public function responseTimeMs(): int
    {
        return $this->responseTimeMs;
    }

    public function submittedAt(): \DateTimeImmutable
    {
        return $this->submittedAt;
    }
}
