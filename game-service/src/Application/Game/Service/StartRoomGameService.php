<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\Command\StartRoomGameCommand;
use App\Application\Game\DTO\StartRoomGameDTO;
use App\Application\Game\Message\RoundStartedMessage;
use App\Domain\Game\Entity\Room;
use App\Domain\Game\Repository\EventBusInterface;
use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\Repository\RoomRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class StartRoomGameService
{
    public function __construct(
        private readonly RoomRepositoryInterface $rooms,
        private readonly NoteClientInterface $noteClient,
        private readonly EventBusInterface $eventBus,
    ) {}

    public function execute(StartRoomGameCommand $command): StartRoomGameDTO
    {
        $room = $this->findRoomOrFail($command->roomId);

        $this->ensureIsHost($room->players(), $command->requestingPlayerId);

        $firstRoundId = Uuid::v4()->toRfc4122();
        $room->startGame($firstRoundId);

        $noteData = $this->noteClient->getRandomNote($room->difficulty()->value());
        $room->assignNoteToCurrentRound($noteData->noteId, $noteData->solfege);

        $this->rooms->save($room);

        $this->eventBus->publish(new RoundStartedMessage(
            roomId:      $room->id(),
            roundId:     $firstRoundId,
            roundNumber: 1,
            noteId:      $noteData->noteId,
            difficulty:  $room->difficulty()->value(),
        ));

        return StartRoomGameDTO::create(
            room_id:      $room->id(),
            status:       $room->status()->value,
            round_id:     $firstRoundId,
            round_number: 1,
            note_id:      $noteData->noteId,
            audio_url:    $noteData->audioUrl,
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

    private function ensureIsHost(array $players, string $playerId): void
    {
        foreach ($players as $player) {
            if ($player->id() === $playerId) {
                if (!$player->isHost()) {
                    throw new \DomainException('Only the host can start the game');
                }

                return;
            }
        }

        throw new \DomainException('Player not found in this room');
    }
}
