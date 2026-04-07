<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Game\ValueObject;

use App\Domain\Game\ValueObject\Score;
use PHPUnit\Framework\TestCase;

final class ScoreTest extends TestCase
{
    public function test_creating_score__zero__should_have_value_0(): void
    {
        $this->assertSame(0, Score::zero()->value());
    }

    public function test_creating_score__with_positive_value__should_store_it(): void
    {
        $this->assertSame(100, Score::create(100)->value());
    }

    public function test_creating_score__with_negative_value__should_raise_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Score cannot be negative');

        Score::create(-1);
    }

    public function test_adding_scores__should_sum_values(): void
    {
        $score = Score::create(100)->add(Score::create(50));

        $this->assertSame(150, $score->value());
    }

    public function test_adding_scores__should_return_new_instance(): void
    {
        $original = Score::create(100);

        $result = $original->add(Score::create(50));

        $this->assertSame(100, $original->value());
        $this->assertSame(150, $result->value());
    }
}
