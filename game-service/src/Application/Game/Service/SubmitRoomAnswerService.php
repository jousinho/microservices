<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\Command\SubmitRoomAnswerCommand;
use App\Application\Game\DTO\NextRoundDTO;
use App\Application\Game\DTO\SubmitRoomAnswerDTO;
use App\Application\Game\Message\GameEndedMessage;
use App\Application\Game\Message\RoundEndedMessage;
use App\Application\Game\Message\RoundStartedMessage;
use App\Domain\Game\Entity\Room;
use App\Domain\Game\Entity\RoomRound;
use App\Domain\Game\Event\RoomGameWasEnded;
use App\Domain\Game\Event\RoomRoundWasEnded;
use App\Domain\Game\Repository\EventBusInterface;
use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\Repository\RoomRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class SubmitRoomAnswerService
{
    public function __construct(
        private readonly RoomRepositoryInterface $rooms,
        private readonly NoteClientInterface $noteClient,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function execute(SubmitRoomAnswerCommand $command): SubmitRoomAnswerDTO
    {
        $room         = $this->findRoomOrFail($command->roomId);
        $currentRound = $this->findCurrentRoundOrFail($room, $command->roundId);

        $isCorrect = $currentRound->isCorrectAnswer($command->guess);
        $room->submitAnswer($command->playerId, $command->roundId, $command->guess, $isCorrect);

        $events         = $room->pullDomainEvents();
        $roundEndedEvent = $this->findEvent($events, RoomRoundWasEnded::class);
        $gameEndedEvent  = $this->findEvent($events, RoomGameWasEnded::class);

        $nextRoundData = $this->advanceToNextRoundIfNeeded($room, $roundEndedEvent, $gameEndedEvent);

        $this->rooms->save($room);
        $this->publishEvents($room, $roundEndedEvent, $gameEndedEvent, $nextRoundData);

        return SubmitRoomAnswerDTO::create(
            is_correct:   $isCorrect,
            correct_note: $currentRound->correctNote(),
            room_status:  $room->status()->value,
            next_round:   $nextRoundData,
            scoreboard:   $gameEndedEvent instanceof RoomGameWasEnded ? $gameEndedEvent->scoreboard : null,
        );
    }

    private function findRoomOrFail(string $roomId): Room
    {
        $room = $this->rooms->findById($roomId);

        if ($room === null) {
            throw new \DomainException(sprintf('Room "%s" not found', $roomId));
        }

        return $room;
    }

    private function findCurrentRoundOrFail(Room $room, string $roundId): RoomRound
    {
        $currentRound = $room->currentRound();

        if ($currentRound === null || $currentRound->id() !== $roundId) {
            throw new \DomainException('Round not found or is not the current round');
        }

        return $currentRound;
    }

    private function advanceToNextRoundIfNeeded(Room $room, ?object $roundEnded, ?object $gameEnded): ?NextRoundDTO
    {
        if ($roundEnded === null || $gameEnded !== null) {
            return null;
        }

        $nextRoundId = Uuid::v4()->toRfc4122();
        $noteData    = $this->noteClient->getRandomNote($room->difficulty()->value());

        $room->startNextRound($nextRoundId);
        $room->assignNoteToCurrentRound($noteData->noteId, $noteData->solfege);
        $room->pullDomainEvents();

        return NextRoundDTO::create(
            round_id:     $nextRoundId,
            round_number: $room->currentRoundNumber(),
            note_id:      $noteData->noteId,
            audio_url:    $noteData->audioUrl,
        );
    }

    private function publishEvents(Room $room, ?RoomRoundWasEnded $roundEnded, ?RoomGameWasEnded $gameEnded, ?NextRoundDTO $nextRoundData): void
    {
        if ($roundEnded !== null) {
            $this->eventBus->publish($this->buildRoundEndedMessage($room, $roundEnded));
        }

        if ($gameEnded !== null) {
            $this->eventBus->publish(new GameEndedMessage(
                roomId:     $gameEnded->roomId,
                scoreboard: $gameEnded->scoreboard,
            ));
        }

        if ($nextRoundData !== null) {
            $this->eventBus->publish(new RoundStartedMessage(
                roomId:      $room->id(),
                roundId:     $nextRoundData->round_id,
                roundNumber: $nextRoundData->round_number,
                noteId:      $nextRoundData->note_id,
                difficulty:  $room->difficulty()->value(),
            ));
        }
    }

    private function buildRoundEndedMessage(Room $room, RoomRoundWasEnded $event): RoundEndedMessage
    {
        $scores = [];
        foreach ($room->players() as $player) {
            $scores[$player->id()] = $player->score();
        }

        return new RoundEndedMessage(
            roomId:      $event->roomId,
            roundId:     $event->roundId,
            roundNumber: $event->roundNumber,
            scores:      $scores,
        );
    }

    private function findEvent(array $events, string $class): ?object
    {
        foreach ($events as $event) {
            if ($event instanceof $class) {
                return $event;
            }
        }

        return null;
    }
}
