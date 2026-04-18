<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class StartRoomGameDTO
{
    private function __construct(
        public readonly string $room_id,
        public readonly string $status,
        public readonly string $round_id,
        public readonly int $round_number,
        public readonly string $note_id,
        public readonly string $audio_url,
    ) {}

    public static function create(
        string $room_id,
        string $status,
        string $round_id,
        int $round_number,
        string $note_id,
        string $audio_url,
    ): self {
        return new self($room_id, $status, $round_id, $round_number, $note_id, $audio_url);
    }
}
