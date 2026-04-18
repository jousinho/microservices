<?php

declare(strict_types=1);

namespace App\Domain\Game\Entity;

use App\Domain\Game\Event\RoomAnswerWasSubmitted;
use App\Domain\Game\Event\RoomGameWasEnded;
use App\Domain\Game\Event\RoomRoundWasEnded;
use App\Domain\Game\Event\RoomRoundWasStarted;
use App\Domain\Game\Event\RoomWasCreated;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\RoomCode;
use App\Domain\Game\ValueObject\RoomStatus;

class Room
{
    private const POINTS_PER_CORRECT_ANSWER = 100;

    /** @var object[] */
    private array $domainEvents = [];

    /** @var Player[] */
    private array $players = [];

    /** @var RoomRound[] */
    private array $rounds = [];

    private array $playerIdsWhoAnsweredCurrentRound = [];

    private int $currentRoundNumber = 0;

    private function __construct(
        private readonly string $id,
        private readonly string $code,
        private RoomStatus $status,
        private readonly int $difficulty,
        private readonly int $totalRounds,
        private readonly \DateTimeImmutable $createdAt,
    ) {}

    public static function create(
        string $id,
        Difficulty $difficulty,
        int $totalRounds,
        string $hostId,
        string $hostName,
    ): self {
        $code = RoomCode::generate()->value();

        $room = new self(
            id: $id,
            code: $code,
            status: RoomStatus::Waiting,
            difficulty: $difficulty->value(),
            totalRounds: $totalRounds,
            createdAt: new \DateTimeImmutable(),
        );

        $room->players[] = Player::create($hostId, $id, $hostName, isHost: true);

        $room->record(new RoomWasCreated(
            $id,
            $code,
            $difficulty->value(),
            $totalRounds,
            $hostId,
            $hostName,
        ));

        return $room;
    }

    public static function reconstitute(
        string $id,
        string $code,
        RoomStatus $status,
        int $difficulty,
        int $totalRounds,
        int $currentRoundNumber,
        \DateTimeImmutable $createdAt,
        array $players,
        array $rounds,
        array $playerIdsWhoAnsweredCurrentRound,
    ): self {
        $room = new self(
            id: $id,
            code: $code,
            status: $status,
            difficulty: $difficulty,
            totalRounds: $totalRounds,
            createdAt: $createdAt,
        );

        $room->currentRoundNumber = $currentRoundNumber;
        $room->players = $players;
        $room->rounds = $rounds;
        $room->playerIdsWhoAnsweredCurrentRound = $playerIdsWhoAnsweredCurrentRound;

        return $room;
    }

    public function join(string $playerId, string $playerName): Player
    {
        if ($this->status !== RoomStatus::Waiting) {
            throw new \DomainException('Cannot join a room that is not waiting for players');
        }

        $player = Player::create($playerId, $this->id, $playerName, isHost: false);
        $this->players[] = $player;

        return $player;
    }

    public function startGame(string $firstRoundId): RoomRound
    {
        if ($this->status !== RoomStatus::Waiting) {
            throw new \DomainException('Game can only be started from waiting status');
        }

        $this->status = RoomStatus::Playing;
        $this->currentRoundNumber = 1;

        $round = RoomRound::create($firstRoundId, $this->id, $this->currentRoundNumber);
        $this->rounds[] = $round;

        $this->record(new RoomRoundWasStarted(
            $this->id,
            $firstRoundId,
            $this->currentRoundNumber,
            $this->difficulty,
        ));

        return $round;
    }

    public function startNextRound(string $roundId): RoomRound
    {
        if ($this->status !== RoomStatus::Playing) {
            throw new \DomainException('Game is not in playing status');
        }

        if ($this->currentRoundNumber >= $this->totalRounds) {
            throw new \DomainException('All rounds have already been played');
        }

        $this->currentRoundNumber++;
        $this->playerIdsWhoAnsweredCurrentRound = [];

        $round = RoomRound::create($roundId, $this->id, $this->currentRoundNumber);
        $this->rounds[] = $round;

        $this->record(new RoomRoundWasStarted(
            $this->id,
            $roundId,
            $this->currentRoundNumber,
            $this->difficulty,
        ));

        return $round;
    }

    public function assignNoteToCurrentRound(string $noteId, string $correctNote): void
    {
        $round = $this->currentRound();

        if ($round === null) {
            throw new \DomainException('No current round to assign note to');
        }

        $round->assignNote($noteId, $correctNote);
    }

    public function submitAnswer(string $playerId, string $roundId, string $guess, bool $isCorrect): void
    {
        if ($this->status !== RoomStatus::Playing) {
            throw new \DomainException('Cannot submit an answer when the game is not playing');
        }

        if ($isCorrect) {
            $this->addScoreToPlayer($playerId, self::POINTS_PER_CORRECT_ANSWER);
        }

        $this->playerIdsWhoAnsweredCurrentRound[] = $playerId;

        $this->record(new RoomAnswerWasSubmitted($this->id, $roundId, $playerId, $guess, $isCorrect));

        if ($this->allPlayersAnswered()) {
            $this->record(new RoomRoundWasEnded($this->id, $roundId, $this->currentRoundNumber));

            if ($this->currentRoundNumber >= $this->totalRounds) {
                $this->status = RoomStatus::Ended;
                $this->record(new RoomGameWasEnded($this->id, $this->buildScoreboard()));
            }
        }
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

    public function code(): RoomCode
    {
        return RoomCode::fromString($this->code);
    }

    public function status(): RoomStatus
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

    public function currentRoundNumber(): int
    {
        return $this->currentRoundNumber;
    }

    public function currentRound(): ?RoomRound
    {
        foreach ($this->rounds as $round) {
            if ($round->roundNumber() === $this->currentRoundNumber) {
                return $round;
            }
        }

        return null;
    }

    /** @return Player[] */
    public function players(): array
    {
        return $this->players;
    }

    /** @return RoomRound[] */
    public function rounds(): array
    {
        return $this->rounds;
    }

    /** @return string[] */
    public function answeredPlayerIdsCurrentRound(): array
    {
        return $this->playerIdsWhoAnsweredCurrentRound;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function allPlayersAnswered(): bool
    {
        return count($this->playerIdsWhoAnsweredCurrentRound) === count($this->players);
    }

    private function addScoreToPlayer(string $playerId, int $points): void
    {
        foreach ($this->players as $player) {
            if ($player->id() === $playerId) {
                $player->addScore($points);

                return;
            }
        }
    }

    /** @return array<string, int> */
    private function buildScoreboard(): array
    {
        $scoreboard = [];
        foreach ($this->players as $player) {
            $scoreboard[$player->id()] = $player->score();
        }

        return $scoreboard;
    }

    private function record(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
