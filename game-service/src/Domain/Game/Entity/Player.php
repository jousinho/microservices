<?php

declare(strict_types=1);

namespace App\Domain\Game\Entity;

final class Player
{
    private int $score = 0;

    private function __construct(
        private readonly string $id,
        private readonly string $roomId,
        private readonly string $name,
        private readonly bool $isHost,
    ) {}

    public static function create(string $id, string $roomId, string $name, bool $isHost): self
    {
        return new self($id, $roomId, $name, $isHost);
    }

    public function addScore(int $points): void
    {
        $this->score += $points;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function roomId(): string
    {
        return $this->roomId;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function isHost(): bool
    {
        return $this->isHost;
    }

    public function score(): int
    {
        return $this->score;
    }
}
