<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class JoinRoomDTO
{
    private function __construct(
        public readonly string $room_id,
        public readonly string $room_code,
        public readonly string $player_id,
        public readonly string $name,
        public readonly bool $is_host,
    ) {}

    public static function create(
        string $room_id,
        string $room_code,
        string $player_id,
        string $name,
        bool $is_host,
    ): self {
        return new self($room_id, $room_code, $player_id, $name, $is_host);
    }
}
