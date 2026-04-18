<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class CreateRoomDTO
{
    private function __construct(
        public readonly string $room_id,
        public readonly string $room_code,
        public readonly string $host_id,
        public readonly string $status,
        public readonly int $difficulty,
        public readonly int $total_rounds,
    ) {}

    public static function create(
        string $room_id,
        string $room_code,
        string $host_id,
        string $status,
        int $difficulty,
        int $total_rounds,
    ): self {
        return new self($room_id, $room_code, $host_id, $status, $difficulty, $total_rounds);
    }
}
