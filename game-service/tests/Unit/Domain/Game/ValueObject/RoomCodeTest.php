<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Game\ValueObject;

use App\Domain\Game\ValueObject\RoomCode;
use PHPUnit\Framework\TestCase;

final class RoomCodeTest extends TestCase
{
    public function test_generating_room_code__should_produce_six_char_string(): void
    {
        $code = RoomCode::generate();

        $this->assertSame(6, strlen($code->value()));
    }

    public function test_generating_room_code__should_contain_only_uppercase_alphanumeric_chars(): void
    {
        $code = RoomCode::generate();

        $this->assertMatchesRegularExpression('/^[A-Z0-9]{6}$/', $code->value());
    }

    public function test_creating_room_code_from_string__with_valid_value__should_preserve_value(): void
    {
        $code = RoomCode::fromString('ABC123');

        $this->assertSame('ABC123', $code->value());
    }

    public function test_creating_room_code_from_string__with_less_than_six_chars__should_raise_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RoomCode::fromString('AB1');
    }

    public function test_creating_room_code_from_string__with_more_than_six_chars__should_raise_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RoomCode::fromString('ABCDEFG');
    }

    public function test_creating_room_code_from_string__with_lowercase__should_raise_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        RoomCode::fromString('abc123');
    }

    public function test_two_room_codes_with_same_value__should_be_equal(): void
    {
        $a = RoomCode::fromString('ABC123');
        $b = RoomCode::fromString('ABC123');

        $this->assertTrue($a->equals($b));
    }

    public function test_two_room_codes_with_different_values__should_not_be_equal(): void
    {
        $a = RoomCode::fromString('ABC123');
        $b = RoomCode::fromString('XYZ789');

        $this->assertFalse($a->equals($b));
    }
}
