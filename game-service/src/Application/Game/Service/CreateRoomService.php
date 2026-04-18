<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\Command\CreateRoomCommand;
use App\Application\Game\DTO\CreateRoomDTO;
use App\Domain\Game\Entity\Room;
use App\Domain\Game\Repository\RoomRepositoryInterface;
use App\Domain\Game\ValueObject\Difficulty;
use Symfony\Component\Uid\Uuid;

final class CreateRoomService
{
    public function __construct(
        private readonly RoomRepositoryInterface $rooms,
    ) {}

    public function execute(CreateRoomCommand $command): CreateRoomDTO
    {
        $room = Room::create(
            Uuid::v4()->toRfc4122(),
            Difficulty::create($command->difficulty),
            $command->totalRounds,
            Uuid::v4()->toRfc4122(),
            $command->hostName,
        );

        $this->rooms->save($room);

        $host = $room->players()[0];

        return CreateRoomDTO::create(
            room_id:      $room->id(),
            room_code:    $room->code()->value(),
            host_id:      $host->id(),
            status:       $room->status()->value,
            difficulty:   $room->difficulty()->value(),
            total_rounds: $room->totalRounds(),
        );
    }
}
