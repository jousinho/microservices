<?php

declare(strict_types=1);

namespace App\Domain\Game\Entity;

final class RoomRound
{
    private ?string $noteId = null;
    private ?string $correctNote = null;

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

    public static function reconstitute(
        string $id,
        string $roomId,
        int $roundNumber,
        \DateTimeImmutable $startedAt,
        ?string $noteId,
        ?string $correctNote,
    ): self {
        $round = new self($id, $roomId, $roundNumber, $startedAt);
        $round->noteId = $noteId;
        $round->correctNote = $correctNote;

        return $round;
    }

    public function assignNote(string $noteId, string $correctNote): void
    {
        $this->noteId = $noteId;
        $this->correctNote = $correctNote;
    }

    public function isCorrectAnswer(string $guess): bool
    {
        if ($this->correctNote === null) {
            throw new \DomainException('Note has not been assigned to this round yet');
        }

        return strtolower(trim($guess)) === strtolower(trim($this->correctNote));
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

    public function noteId(): ?string
    {
        return $this->noteId;
    }

    public function correctNote(): ?string
    {
        return $this->correctNote;
    }
}
