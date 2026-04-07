<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Game\Entity;

use App\Domain\Game\Entity\Round;
use App\Domain\Game\ValueObject\NoteId;
use PHPUnit\Framework\TestCase;

final class RoundTest extends TestCase
{
    public function test_creating_round__with_valid_data__should_store_round_number(): void
    {
        $round = Round::create('round-1', 'session-1', 3);

        $this->assertSame(3, $round->roundNumber());
        $this->assertSame('session-1', $round->sessionId());
    }

    public function test_creating_round__should_have_no_note_assigned(): void
    {
        $round = Round::create('round-1', 'session-1', 1);

        $this->assertNull($round->noteId());
        $this->assertNull($round->correctNote());
    }

    public function test_assigning_note__should_store_note_id_and_correct_note(): void
    {
        $round = Round::create('round-1', 'session-1', 1);
        $round->assignNote(NoteId::create('la_4'), 'la');

        $this->assertSame('la_4', $round->noteId()->value());
        $this->assertSame('la', $round->correctNote());
    }

    public function test_checking_correct_answer__when_guess_matches__should_return_true(): void
    {
        $round = Round::create('round-1', 'session-1', 1);
        $round->assignNote(NoteId::create('la_4'), 'la');

        $this->assertTrue($round->isCorrectAnswer('la'));
    }

    public function test_checking_correct_answer__when_guess_is_wrong__should_return_false(): void
    {
        $round = Round::create('round-1', 'session-1', 1);
        $round->assignNote(NoteId::create('la_4'), 'la');

        $this->assertFalse($round->isCorrectAnswer('do'));
    }

    public function test_checking_correct_answer__should_be_case_insensitive(): void
    {
        $round = Round::create('round-1', 'session-1', 1);
        $round->assignNote(NoteId::create('la_4'), 'la');

        $this->assertTrue($round->isCorrectAnswer('LA'));
        $this->assertTrue($round->isCorrectAnswer('La'));
    }

    public function test_ending_round__should_set_ended_at(): void
    {
        $round = Round::create('round-1', 'session-1', 1);

        $this->assertNull($round->endedAt());
        $round->end();
        $this->assertNotNull($round->endedAt());
    }
}
