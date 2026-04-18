<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class GetSessionDTO
{
    private function __construct(
        public readonly string $session_id,
        public readonly string $status,
        public readonly int $difficulty,
        public readonly int $total_rounds,
        public readonly int $current_round,
        public readonly int $score,
    ) {}

    public static function create(
        string $session_id,
        string $status,
        int $difficulty,
        int $total_rounds,
        int $current_round,
        int $score,
    ): self {
        return new self($session_id, $status, $difficulty, $total_rounds, $current_round, $score);
    }
}
