<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Game\Entity;

use App\Domain\Game\Entity\Session;
use App\Domain\Game\Event\RoundWasStarted;
use App\Domain\Game\Event\SessionWasEnded;
use App\Domain\Game\Event\SessionWasStarted;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\Score;
use App\Domain\Game\ValueObject\SessionStatus;
use PHPUnit\Framework\TestCase;

final class SessionTest extends TestCase
{
    private function makeSession(int $totalRounds = 5): Session
    {
        return Session::create('session-1', Difficulty::create(1), $totalRounds);
    }

    public function test_creating_session__with_valid_data__should_have_active_status(): void
    {
        $session = $this->makeSession();

        $this->assertSame(SessionStatus::Active, $session->status());
    }

    public function test_creating_session__should_start_with_zero_score(): void
    {
        $session = $this->makeSession();

        $this->assertSame(0, $session->score()->value());
    }

    public function test_creating_session__should_start_at_round_zero(): void
    {
        $session = $this->makeSession();

        $this->assertSame(0, $session->currentRound());
    }

    public function test_creating_session__should_emit_SessionWasStarted_event(): void
    {
        $session = $this->makeSession();

        $events = $session->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(SessionWasStarted::class, $events[0]);
        $this->assertSame('session-1', $events[0]->sessionId);
    }

    public function test_starting_next_round__should_increment_current_round(): void
    {
        $session = $this->makeSession();
        $session->pullDomainEvents();

        $session->startNextRound('round-1');

        $this->assertSame(1, $session->currentRound());
    }

    public function test_starting_next_round__should_emit_RoundWasStarted_event(): void
    {
        $session = $this->makeSession();
        $session->pullDomainEvents();

        $session->startNextRound('round-1');
        $events = $session->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoundWasStarted::class, $events[0]);
        $this->assertSame('round-1', $events[0]->roundId);
    }

    public function test_starting_next_round__when_session_ended__should_raise_exception(): void
    {
        $session = $this->makeSession();
        $session->end();

        $this->expectException(\DomainException::class);
        $session->startNextRound('round-1');
    }

    public function test_starting_next_round__when_all_rounds_played__should_raise_exception(): void
    {
        $session = $this->makeSession(totalRounds: 1);
        $session->startNextRound('round-1');

        $this->expectException(\DomainException::class);
        $session->startNextRound('round-2');
    }

    public function test_ending_session__should_have_ended_status(): void
    {
        $session = $this->makeSession();

        $session->end();

        $this->assertSame(SessionStatus::Ended, $session->status());
    }

    public function test_ending_session__should_emit_SessionWasEnded_event(): void
    {
        $session = $this->makeSession();
        $session->pullDomainEvents();

        $session->end();
        $events = $session->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(SessionWasEnded::class, $events[0]);
    }

    public function test_ending_session__when_already_ended__should_raise_exception(): void
    {
        $session = $this->makeSession();
        $session->end();

        $this->expectException(\DomainException::class);
        $session->end();
    }

    public function test_adding_score__should_accumulate_points(): void
    {
        $session = $this->makeSession();

        $session->addScore(Score::create(100));
        $session->addScore(Score::create(50));

        $this->assertSame(150, $session->score()->value());
    }

    public function test_pulling_domain_events__should_clear_the_queue(): void
    {
        $session = $this->makeSession();

        $session->pullDomainEvents();

        $this->assertEmpty($session->pullDomainEvents());
    }
}
