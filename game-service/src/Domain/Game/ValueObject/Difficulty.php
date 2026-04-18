<?php

declare(strict_types=1);

namespace App\Domain\Game\ValueObject;

final class Difficulty
{
    private const VALID_DIFFICULTY_LEVELS = [1, 2, 3];

    private function __construct(private readonly int $value) {}

    public static function validDifficultyLevels(): array
    {
        return self::VALID_DIFFICULTY_LEVELS;
    }

    public static function create(int $value): self
    {
        if (!in_array($value, self::VALID_DIFFICULTY_LEVELS, true)) {
            throw new \InvalidArgumentException(
                sprintf('Difficulty must be 1, 2 or 3, got %d', $value)
            );
        }

        return new self($value);
    }

    public function value(): int
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
