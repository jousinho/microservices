<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\DTO\GetRoomDTO;
use App\Application\Game\DTO\PlayerDTO;
use App\Domain\Game\Repository\RoomRepositoryInterface;

final class GetRoomService
{
    public function __construct(
        private readonly RoomRepositoryInterface $rooms,
    ) {}

    public function execute(string $id): ?GetRoomDTO
    {
        $room = $this->rooms->findById($id);

        if ($room === null) {
            return null;
        }

        $players = array_map(
            fn($p) => PlayerDTO::create(
                player_id: $p->id(),
                name:      $p->name(),
                is_host:   $p->isHost(),
                score:     $p->score(),
            ),
            $room->players(),
        );

        return GetRoomDTO::create(
            room_id:              $room->id(),
            room_code:            $room->code()->value(),
            status:               $room->status()->value,
            difficulty:           $room->difficulty()->value(),
            total_rounds:         $room->totalRounds(),
            current_round_number: $room->currentRoundNumber(),
            players:              $players,
        );
    }
}
