<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class GetRoomDTO
{
    /**
     * @param PlayerDTO[] $players
     */
    private function __construct(
        public readonly string $room_id,
        public readonly string $room_code,
        public readonly string $status,
        public readonly int $difficulty,
        public readonly int $total_rounds,
        public readonly int $current_round_number,
        public readonly array $players,
    ) {}

    /**
     * @param PlayerDTO[] $players
     */
    public static function create(
        string $room_id,
        string $room_code,
        string $status,
        int $difficulty,
        int $total_rounds,
        int $current_round_number,
        array $players,
    ): self {
        return new self($room_id, $room_code, $status, $difficulty, $total_rounds, $current_round_number, $players);
    }
}
