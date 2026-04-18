<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\Command\JoinRoomCommand;
use App\Application\Game\DTO\JoinRoomDTO;
use App\Domain\Game\Repository\RoomRepositoryInterface;
use Symfony\Component\Uid\Uuid;

final class JoinRoomService
{
    public function __construct(
        private readonly RoomRepositoryInterface $rooms,
    ) {}

    public function execute(JoinRoomCommand $command): JoinRoomDTO
    {
        $room = $this->rooms->findByCode($command->roomCode);

        if ($room === null) {
            throw new \DomainException(sprintf('Room with code "%s" not found', $command->roomCode));
        }

        $player = $room->join(Uuid::v4()->toRfc4122(), $command->playerName);

        $this->rooms->save($room);

        return JoinRoomDTO::create(
            room_id:   $room->id(),
            room_code: $room->code()->value(),
            player_id: $player->id(),
            name:      $player->name(),
            is_host:   $player->isHost(),
        );
    }
}
