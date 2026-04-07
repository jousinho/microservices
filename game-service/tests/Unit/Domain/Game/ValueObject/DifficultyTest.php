<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Game\ValueObject;

use App\Domain\Game\ValueObject\Difficulty;
use PHPUnit\Framework\TestCase;

final class DifficultyTest extends TestCase
{
    public function test_creating_difficulty__with_value_1__should_be_valid(): void
    {
        $this->assertSame(1, Difficulty::create(1)->value());
    }

    public function test_creating_difficulty__with_value_2__should_be_valid(): void
    {
        $this->assertSame(2, Difficulty::create(2)->value());
    }

    public function test_creating_difficulty__with_value_3__should_be_valid(): void
    {
        $this->assertSame(3, Difficulty::create(3)->value());
    }

    public function test_creating_difficulty__with_value_above_3__should_raise_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Difficulty must be 1, 2 or 3');

        Difficulty::create(4);
    }

    public function test_creating_difficulty__with_value_below_1__should_raise_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Difficulty must be 1, 2 or 3');

        Difficulty::create(0);
    }

    public function test_difficulty__equals__when_same_value__should_return_true(): void
    {
        $this->assertTrue(Difficulty::create(2)->equals(Difficulty::create(2)));
    }

    public function test_difficulty__equals__when_different_value__should_return_false(): void
    {
        $this->assertFalse(Difficulty::create(1)->equals(Difficulty::create(3)));
    }
}
