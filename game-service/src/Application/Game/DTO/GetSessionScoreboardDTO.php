<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class GetSessionScoreboardDTO
{
    private function __construct(
        public readonly string $session_id,
        public readonly int $score,
        public readonly int $total_rounds,
    ) {}

    public static function create(
        string $session_id,
        int $score,
        int $total_rounds,
    ): self {
        return new self($session_id, $score, $total_rounds);
    }
}
