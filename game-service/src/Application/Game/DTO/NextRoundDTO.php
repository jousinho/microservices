<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class NextRoundDTO
{
    private function __construct(
        public readonly string $round_id,
        public readonly int $round_number,
        public readonly string $note_id,
        public readonly string $audio_url,
    ) {}

    public static function create(
        string $round_id,
        int $round_number,
        string $note_id,
        string $audio_url,
    ): self {
        return new self($round_id, $round_number, $note_id, $audio_url);
    }
}
