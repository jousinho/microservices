<?php

declare(strict_types=1);

namespace App\Domain\Game\ValueObject;

final class Score
{
    private function __construct(private readonly int $value) {}

    public static function zero(): self
    {
        return new self(0);
    }

    public static function create(int $value): self
    {
        if ($value < 0) {
            throw new \InvalidArgumentException(
                sprintf('Score cannot be negative, got %d', $value)
            );
        }

        return new self($value);
    }

    public function add(self $other): self
    {
        return new self($this->value + $other->value);
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
