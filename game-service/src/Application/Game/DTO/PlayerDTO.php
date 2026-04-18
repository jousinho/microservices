<?php

declare(strict_types=1);

namespace App\Application\Game\DTO;

final class PlayerDTO
{
    private function __construct(
        public readonly string $player_id,
        public readonly string $name,
        public readonly bool $is_host,
        public readonly int $score,
    ) {}

    public static function create(
        string $player_id,
        string $name,
        bool $is_host,
        int $score,
    ): self {
        return new self($player_id, $name, $is_host, $score);
    }
}
