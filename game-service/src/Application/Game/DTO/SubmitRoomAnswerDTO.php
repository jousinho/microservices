<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class SubmitRoomAnswerDTO
{
    private function __construct(
        public readonly bool $is_correct,
        public readonly string $correct_note,
        public readonly string $room_status,
        public readonly ?NextRoundDTO $next_round,
        public readonly ?array $scoreboard,
    ) {}

    public static function create(
        bool $is_correct,
        string $correct_note,
        string $room_status,
        ?NextRoundDTO $next_round,
        ?array $scoreboard,
    ): self {
        return new self($is_correct, $correct_note, $room_status, $next_round, $scoreboard);
    }
}
