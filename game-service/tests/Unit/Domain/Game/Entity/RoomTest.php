<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Game\Entity;

use App\Domain\Game\Entity\Room;
use App\Domain\Game\Event\RoomAnswerWasSubmitted;
use App\Domain\Game\Event\RoomGameWasEnded;
use App\Domain\Game\Event\RoomRoundWasEnded;
use App\Domain\Game\Event\RoomRoundWasStarted;
use App\Domain\Game\Event\RoomWasCreated;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\RoomStatus;
use PHPUnit\Framework\TestCase;

final class RoomTest extends TestCase
{
    private function makeRoom(int $totalRounds = 3): Room
    {
        return Room::create('room-1', Difficulty::create(1), $totalRounds, 'host-1', 'Alice');
    }

    // --- Creation ---

    public function test_creating_room__should_have_waiting_status(): void
    {
        $room = $this->makeRoom();

        $this->assertSame(RoomStatus::Waiting, $room->status());
    }

    public function test_creating_room__should_generate_six_char_code(): void
    {
        $room = $this->makeRoom();

        $this->assertSame(6, strlen($room->code()->value()));
    }

    public function test_creating_room__should_add_host_as_first_player(): void
    {
        $room = $this->makeRoom();

        $this->assertCount(1, $room->players());
        $this->assertTrue($room->players()[0]->isHost());
        $this->assertSame('Alice', $room->players()[0]->name());
    }

    public function test_creating_room__should_emit_RoomWasCreated_event(): void
    {
        $room = $this->makeRoom();

        $events = $room->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoomWasCreated::class, $events[0]);
        $this->assertSame('room-1', $events[0]->roomId);
        $this->assertSame('host-1', $events[0]->hostId);
    }

    // --- Join ---

    public function test_joining_room__should_add_player(): void
    {
        $room = $this->makeRoom();
        $room->pullDomainEvents();

        $room->join('player-2', 'Bob');

        $this->assertCount(2, $room->players());
    }

    public function test_joining_room__when_playing__should_raise_exception(): void
    {
        $room = $this->makeRoom();
        $room->startGame('round-1');

        $this->expectException(\DomainException::class);
        $room->join('player-2', 'Bob');
    }

    public function test_joining_room__when_ended__should_raise_exception(): void
    {
        $room = $this->makeRoom(totalRounds: 1);
        $room->join('player-2', 'Bob');
        $room->startGame('round-1');
        $room->submitAnswer('host-1', 'round-1', 'Do', true);
        $room->submitAnswer('player-2', 'round-1', 'Re', false);

        $this->expectException(\DomainException::class);
        $room->join('player-3', 'Charlie');
    }

    // --- Start Game ---

    public function test_starting_game__should_transition_to_playing_status(): void
    {
        $room = $this->makeRoom();
        $room->pullDomainEvents();

        $room->startGame('round-1');

        $this->assertSame(RoomStatus::Playing, $room->status());
    }

    public function test_starting_game__should_emit_RoomRoundWasStarted_event(): void
    {
        $room = $this->makeRoom();
        $room->pullDomainEvents();

        $room->startGame('round-1');
        $events = $room->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(RoomRoundWasStarted::class, $events[0]);
        $this->assertSame('room-1', $events[0]->roomId);
        $this->assertSame('round-1', $events[0]->roundId);
        $this->assertSame(1, $events[0]->roundNumber);
    }

    public function test_starting_game__when_already_playing__should_raise_exception(): void
    {
        $room = $this->makeRoom();
        $room->startGame('round-1');

        $this->expectException(\DomainException::class);
        $room->startGame('round-2');
    }

    // --- Start Next Round ---

    public function test_starting_next_round__should_increment_round_number(): void
    {
        $room = $this->makeRoom();
        $room->startGame('round-1');

        $room->startNextRound('round-2');

        $this->assertSame(2, $room->currentRoundNumber());
    }

    public function test_starting_next_round__when_all_rounds_played__should_raise_exception(): void
    {
        $room = $this->makeRoom(totalRounds: 1);
        $room->startGame('round-1');

        $this->expectException(\DomainException::class);
        $room->startNextRound('round-2');
    }

    // --- Submit Answer ---

    public function test_submitting_answer__correct__should_add_score_to_player(): void
    {
        $room = $this->makeRoom();
        $room->startGame('round-1');
        $room->pullDomainEvents();

        $room->submitAnswer('host-1', 'round-1', 'Do', true);

        $host = $room->players()[0];
        $this->assertSame(100, $host->score());
    }

    public function test_submitting_answer__incorrect__should_not_add_score(): void
    {
        $room = $this->makeRoom();
        $room->startGame('round-1');
        $room->pullDomainEvents();

        $room->submitAnswer('host-1', 'round-1', 'Re', false);

        $host = $room->players()[0];
        $this->assertSame(0, $host->score());
    }

    public function test_submitting_answer__should_emit_RoomAnswerWasSubmitted_event(): void
    {
        $room = $this->makeRoom();
        $room->startGame('round-1');
        $room->pullDomainEvents();

        $room->submitAnswer('host-1', 'round-1', 'Do', true);
        $events = $room->pullDomainEvents();

        $this->assertInstanceOf(RoomAnswerWasSubmitted::class, $events[0]);
        $this->assertSame('host-1', $events[0]->playerId);
        $this->assertTrue($events[0]->isCorrect);
    }

    public function test_submitting_answer__when_game_not_playing__should_raise_exception(): void
    {
        $room = $this->makeRoom();

        $this->expectException(\DomainException::class);
        $room->submitAnswer('host-1', 'round-1', 'Do', true);
    }

    // --- Round Ended ---

    public function test_submitting_answer__when_all_players_answered__should_emit_RoomRoundWasEnded(): void
    {
        $room = $this->makeRoom();
        $room->join('player-2', 'Bob');
        $room->startGame('round-1');
        $room->pullDomainEvents();

        $room->submitAnswer('host-1', 'round-1', 'Do', true);
        $room->submitAnswer('player-2', 'round-1', 'Re', false);
        $events = $room->pullDomainEvents();

        $roundEndedEvents = array_filter($events, fn($e) => $e instanceof RoomRoundWasEnded);
        $this->assertCount(1, $roundEndedEvents);
    }

    public function test_submitting_answer__when_not_all_players_answered__should_not_emit_RoomRoundWasEnded(): void
    {
        $room = $this->makeRoom();
        $room->join('player-2', 'Bob');
        $room->startGame('round-1');
        $room->pullDomainEvents();

        $room->submitAnswer('host-1', 'round-1', 'Do', true);
        $events = $room->pullDomainEvents();

        $roundEndedEvents = array_filter($events, fn($e) => $e instanceof RoomRoundWasEnded);
        $this->assertCount(0, $roundEndedEvents);
    }

    // --- Game Ended ---

    public function test_submitting_last_answer__when_last_round__should_emit_RoomGameWasEnded(): void
    {
        $room = $this->makeRoom(totalRounds: 1);
        $room->join('player-2', 'Bob');
        $room->startGame('round-1');
        $room->pullDomainEvents();

        $room->submitAnswer('host-1', 'round-1', 'Do', true);
        $room->submitAnswer('player-2', 'round-1', 'Re', false);
        $events = $room->pullDomainEvents();

        $gameEndedEvents = array_filter($events, fn($e) => $e instanceof RoomGameWasEnded);
        $this->assertCount(1, $gameEndedEvents);
    }

    public function test_submitting_last_answer__when_last_round__should_transition_to_ended_status(): void
    {
        $room = $this->makeRoom(totalRounds: 1);
        $room->join('player-2', 'Bob');
        $room->startGame('round-1');

        $room->submitAnswer('host-1', 'round-1', 'Do', true);
        $room->submitAnswer('player-2', 'round-1', 'Re', false);

        $this->assertSame(RoomStatus::Ended, $room->status());
    }

    public function test_submitting_last_answer__should_include_scoreboard_in_event(): void
    {
        $room = $this->makeRoom(totalRounds: 1);
        $room->join('player-2', 'Bob');
        $room->startGame('round-1');
        $room->pullDomainEvents();

        $room->submitAnswer('host-1', 'round-1', 'Do', true);
        $room->submitAnswer('player-2', 'round-1', 'Re', false);
        $events = $room->pullDomainEvents();

        /** @var RoomGameWasEnded $gameEndedEvent */
        $gameEndedEvent = array_values(array_filter($events, fn($e) => $e instanceof RoomGameWasEnded))[0];

        $this->assertArrayHasKey('host-1', $gameEndedEvent->scoreboard);
        $this->assertSame(100, $gameEndedEvent->scoreboard['host-1']);
        $this->assertSame(0, $gameEndedEvent->scoreboard['player-2']);
    }

    // --- Pull Domain Events ---

    public function test_pulling_domain_events__should_clear_the_queue(): void
    {
        $room = $this->makeRoom();

        $room->pullDomainEvents();

        $this->assertEmpty($room->pullDomainEvents());
    }
}
