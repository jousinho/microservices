<?php

declare(strict_types=1);

namespace App\Domain\Game\Entity;

final class RoomRound
{
    private function __construct(
        private readonly string $id,
        private readonly string $roomId,
        private readonly int $roundNumber,
        private readonly \DateTimeImmutable $startedAt,
    ) {}

    public static function create(string $id, string $roomId, int $roundNumber): self
    {
        return new self($id, $roomId, $roundNumber, new \DateTimeImmutable());
    }

    public function id(): string
    {
        return $this->id;
    }

    public function roomId(): string
    {
        return $this->roomId;
    }

    public function roundNumber(): int
    {
        return $this->roundNumber;
    }

    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }
}
