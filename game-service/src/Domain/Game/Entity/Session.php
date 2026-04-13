<?php

declare(strict_types=1);

namespace App\Domain\Game\Entity;

use App\Domain\Game\Event\AnswerWasSubmitted;
use App\Domain\Game\Event\RoundWasStarted;
use App\Domain\Game\Event\SessionWasEnded;
use App\Domain\Game\Event\SessionWasStarted;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\Score;
use App\Domain\Game\ValueObject\SessionStatus;

class Session
{
    /** @var object[] */
    private array $domainEvents = [];

    private function __construct(
        private readonly string $id,
        private SessionStatus $status,
        private readonly int $difficulty,
        private readonly int $totalRounds,
        private int $currentRound,
        private int $score,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(string $id, Difficulty $difficulty, int $totalRounds): self
    {
        $session = new self(
            id: $id,
            status: SessionStatus::Active,
            difficulty: $difficulty->value(),
            totalRounds: $totalRounds,
            currentRound: 0,
            score: 0,
            createdAt: new \DateTimeImmutable(),
        );

        $session->record(new SessionWasStarted($id, $difficulty->value(), $totalRounds));

        return $session;
    }

    public function startNextRound(string $roundId): Round
    {
        if ($this->status !== SessionStatus::Active) {
            throw new \DomainException('Cannot start a round on an ended session');
        }

        if ($this->currentRound >= $this->totalRounds) {
            throw new \DomainException('All rounds have already been played');
        }

        $this->currentRound++;

        $round = Round::create($roundId, $this->id, $this->currentRound);

        $this->record(new RoundWasStarted($this->id, $roundId, $this->currentRound));

        return $round;
    }

    public function submitAnswer(string $roundId, string $guess, bool $isCorrect): void
    {
        $this->record(new AnswerWasSubmitted($this->id, $roundId, $guess, $isCorrect));
    }

    public function end(): void
    {
        if ($this->status === SessionStatus::Ended) {
            throw new \DomainException('Session is already ended');
        }

        $this->status = SessionStatus::Ended;

        $this->record(new SessionWasEnded($this->id, $this->score));
    }

    public function addScore(Score $points): void
    {
        $this->score += $points->value();
    }

    /** @return object[] */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function status(): SessionStatus
    {
        return $this->status;
    }

    public function difficulty(): Difficulty
    {
        return Difficulty::create($this->difficulty);
    }

    public function totalRounds(): int
    {
        return $this->totalRounds;
    }

    public function currentRound(): int
    {
        return $this->currentRound;
    }

    public function score(): Score
    {
        return Score::create($this->score);
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
