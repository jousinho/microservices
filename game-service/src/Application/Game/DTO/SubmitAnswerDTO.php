<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class SubmitAnswerDTO
{
    private function __construct(
        public readonly bool $is_correct,
        public readonly string $correct_note,
        public readonly int $score,
        public readonly bool $session_ended,
    ) {}

    public static function create(
        bool $is_correct,
        string $correct_note,
        int $score,
        bool $session_ended,
    ): self {
        return new self($is_correct, $correct_note, $score, $session_ended);
    }
}
