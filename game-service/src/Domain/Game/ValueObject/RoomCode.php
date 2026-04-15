<?php

declare(strict_types=1);

namespace App\Domain\Game\ValueObject;

final class RoomCode
{
    private const LENGTH = 6;
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    private function __construct(private readonly string $value) {}

    public static function generate(): self
    {
        $code = '';
        $max = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, $max)];
        }

        return new self($code);
    }

    public static function fromString(string $value): self
    {
        if (strlen($value) !== self::LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('RoomCode must be exactly %d characters, got %d', self::LENGTH, strlen($value))
            );
        }

        if (!preg_match('/^[A-Z0-9]+$/', $value)) {
            throw new \InvalidArgumentException(
                'RoomCode must contain only uppercase letters and digits'
            );
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
