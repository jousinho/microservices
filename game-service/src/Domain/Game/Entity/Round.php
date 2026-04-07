<?php

declare(strict_types=1);

namespace App\Domain\Game\Entity;

use App\Domain\Game\ValueObject\NoteId;

class Round
{
    private function __construct(
        private readonly string $id,
        private readonly string $sessionId,
        private readonly int $roundNumber,
        private ?NoteId $noteId,
        private ?string $correctNote,
        private readonly \DateTimeImmutable $startedAt,
        private ?\DateTimeImmutable $endedAt,
    ) {}

    public static function create(string $id, string $sessionId, int $roundNumber): self
    {
        return new self(
            id: $id,
            sessionId: $sessionId,
            roundNumber: $roundNumber,
            noteId: null,
            correctNote: null,
            startedAt: new \DateTimeImmutable(),
            endedAt: null,
        );
    }

    public function assignNote(NoteId $noteId, string $correctNote): void
    {
        $this->noteId = $noteId;
        $this->correctNote = $correctNote;
    }

    public function end(): void
    {
        $this->endedAt = new \DateTimeImmutable();
    }

    public function isCorrectAnswer(string $guess): bool
    {
        return strtolower(trim($guess)) === strtolower($this->correctNote ?? '');
    }

    public function id(): string
    {
        return $this->id;
    }

    public function sessionId(): string
    {
        return $this->sessionId;
    }

    public function roundNumber(): int
    {
        return $this->roundNumber;
    }

    public function noteId(): ?NoteId
    {
        return $this->noteId;
    }

    public function correctNote(): ?string
    {
        return $this->correctNote;
    }

    public function startedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function endedAt(): ?\DateTimeImmutable
    {
        return $this->endedAt;
    }
}
