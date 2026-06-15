<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Game\Service;

use App\Application\Game\Command\SubmitAnswerCommand;
use App\Application\Game\Service\SubmitAnswerService;
use App\Domain\Game\Entity\Round;
use App\Domain\Game\Entity\Session;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\NoteId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SubmitAnswerServiceTest extends TestCase
{
    private SessionRepositoryInterface&MockObject $sessions;
    private RoundRepositoryInterface&MockObject $rounds;
    private SubmitAnswerService $service;

    protected function setUp(): void
    {
        $this->sessions = $this->createMock(SessionRepositoryInterface::class);
        $this->rounds   = $this->createMock(RoundRepositoryInterface::class);
        $this->service  = new SubmitAnswerService($this->sessions, $this->rounds);
    }

    private function makeRoundWithNote(string $sessionId, string $roundId, string $note): Round
    {
        $round = Round::create($roundId, $sessionId, 1);
        $round->assignNote(NoteId::create($note . '_4'), $note);

        return $round;
    }

    private function makeSession(string $id, int $totalRounds): Session
    {
        $session = Session::create($id, Difficulty::create(1), $totalRounds);
        $session->startNextRound('round-0');
        $session->pullDomainEvents();

        return $session;
    }

    public function test_submitting_answer__correct__should_return_is_correct_true(): void
    {
        $round   = $this->makeRoundWithNote('session-1', 'round-1', 'do');
        $session = $this->makeSession('session-1', 3);

        $this->rounds->method('findById')->willReturn($round);
        $this->sessions->method('findById')->willReturn($session);

        $result = $this->service->execute(new SubmitAnswerCommand('round-1', 'do'));

        $this->assertTrue($result->is_correct);
    }

    public function test_submitting_answer__wrong__should_return_is_correct_false(): void
    {
        $round   = $this->makeRoundWithNote('session-1', 'round-1', 'do');
        $session = $this->makeSession('session-1', 3);

        $this->rounds->method('findById')->willReturn($round);
        $this->sessions->method('findById')->willReturn($session);

        $result = $this->service->execute(new SubmitAnswerCommand('round-1', 're'));

        $this->assertFalse($result->is_correct);
    }

    public function test_submitting_answer__correct__should_increment_session_score(): void
    {
        $round   = $this->makeRoundWithNote('session-1', 'round-1', 'mi');
        $session = $this->makeSession('session-1', 3);

        $this->rounds->method('findById')->willReturn($round);
        $this->sessions->method('findById')->willReturn($session);

        $result = $this->service->execute(new SubmitAnswerCommand('round-1', 'mi'));

        $this->assertSame(1, $result->score);
    }

    public function test_submitting_answer__wrong__should_not_increment_score(): void
    {
        $round   = $this->makeRoundWithNote('session-1', 'round-1', 'mi');
        $session = $this->makeSession('session-1', 3);

        $this->rounds->method('findById')->willReturn($round);
        $this->sessions->method('findById')->willReturn($session);

        $result = $this->service->execute(new SubmitAnswerCommand('round-1', 'fa'));

        $this->assertSame(0, $result->score);
    }

    public function test_submitting_answer__last_round__should_mark_session_as_ended(): void
    {
        $session = Session::create('session-1', Difficulty::create(1), 1);
        $session->startNextRound('round-0');
        $session->pullDomainEvents();

        $round = $this->makeRoundWithNote('session-1', 'round-0', 'sol');

        $this->rounds->method('findById')->willReturn($round);
        $this->sessions->method('findById')->willReturn($session);

        $result = $this->service->execute(new SubmitAnswerCommand('round-0', 'sol'));

        $this->assertTrue($result->session_ended);
    }

    public function test_submitting_answer__when_round_not_found__should_raise_exception(): void
    {
        $this->rounds->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Round "missing" not found');

        $this->service->execute(new SubmitAnswerCommand('missing', 'do'));
    }
}
