<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class StartSessionDTO
{
    private function __construct(
        public readonly string $session_id,
        public readonly string $status,
        public readonly int $difficulty,
        public readonly int $total_rounds,
        public readonly int $current_round,
        public readonly int $score,
        public readonly string $round_id,
        public readonly string $note_id,
        public readonly string $audio_url,
    ) {}

    public static function create(
        string $session_id,
        string $status,
        int $difficulty,
        int $total_rounds,
        int $current_round,
        int $score,
        string $round_id,
        string $note_id,
        string $audio_url,
    ): self {
        return new self($session_id, $status, $difficulty, $total_rounds, $current_round, $score, $round_id, $note_id, $audio_url);
    }
}
