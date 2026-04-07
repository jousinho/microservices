<?php

declare(strict_types=1);

namespace App\Domain\Game\ValueObject;

final class NoteId
{
    private function __construct(private readonly string $value) {}

    public static function create(string $value): self
    {
        if (empty(trim($value))) {
            throw new \InvalidArgumentException('NoteId cannot be empty');
        }

        return new self($value);
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
