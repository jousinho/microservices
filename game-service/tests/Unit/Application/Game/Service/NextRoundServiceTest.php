<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Game\Service;

use App\Application\Game\Service\NextRoundService;
use App\Domain\Game\Entity\Session;
use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\NoteData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class NextRoundServiceTest extends TestCase
{
    private SessionRepositoryInterface&MockObject $sessions;
    private RoundRepositoryInterface&MockObject $rounds;
    private NoteClientInterface&MockObject $noteClient;
    private NextRoundService $service;

    protected function setUp(): void
    {
        $this->sessions   = $this->createMock(SessionRepositoryInterface::class);
        $this->rounds     = $this->createMock(RoundRepositoryInterface::class);
        $this->noteClient = $this->createMock(NoteClientInterface::class);
        $this->service    = new NextRoundService($this->sessions, $this->rounds, $this->noteClient);
    }

    public function test_next_round__should_return_note_id_and_audio_url(): void
    {
        $session = Session::create('session-1', Difficulty::create(2), 3);
        $session->pullDomainEvents();

        $this->sessions->method('findById')->willReturn($session);
        $this->noteClient->method('getRandomNote')->willReturn(
            new NoteData('fa_4', 'fa', 'http://audio-brain/api/notes/fa_4/audio')
        );

        $result = $this->service->execute('session-1');

        $this->assertSame('fa_4', $result['note_id']);
        $this->assertSame('http://audio-brain/api/notes/fa_4/audio', $result['audio_url']);
        $this->assertSame(1, $result['round_number']);
    }

    public function test_next_round__should_return_round_id(): void
    {
        $session = Session::create('session-1', Difficulty::create(1), 3);
        $session->pullDomainEvents();

        $this->sessions->method('findById')->willReturn($session);
        $this->noteClient->method('getRandomNote')->willReturn(
            new NoteData('mi_4', 'mi', 'http://audio-brain/api/notes/mi_4/audio')
        );

        $result = $this->service->execute('session-1');

        $this->assertArrayHasKey('round_id', $result);
        $this->assertNotEmpty($result['round_id']);
    }

    public function test_next_round__should_save_session_and_round(): void
    {
        $session = Session::create('session-1', Difficulty::create(1), 3);
        $session->pullDomainEvents();

        $this->sessions->method('findById')->willReturn($session);
        $this->noteClient->method('getRandomNote')->willReturn(
            new NoteData('mi_4', 'mi', 'http://audio-brain/api/notes/mi_4/audio')
        );

        $this->sessions->expects($this->once())->method('save');
        $this->rounds->expects($this->once())->method('save');

        $this->service->execute('session-1');
    }

    public function test_next_round__when_session_not_found__should_raise_exception(): void
    {
        $this->sessions->method('findById')->willReturn(null);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Session "missing" not found');

        $this->service->execute('missing');
    }

    public function test_next_round__when_session_ended__should_raise_exception(): void
    {
        $session = Session::create('session-1', Difficulty::create(1), 1);
        $session->startNextRound('round-0');
        $session->end();
        $session->pullDomainEvents();

        $this->sessions->method('findById')->willReturn($session);

        $this->expectException(\DomainException::class);

        $this->service->execute('session-1');
    }

    public function test_next_round__when_all_rounds_played__should_raise_exception(): void
    {
        $session = Session::create('session-1', Difficulty::create(1), 1);
        $session->startNextRound('round-0');
        $session->pullDomainEvents();

        $this->sessions->method('findById')->willReturn($session);

        $this->expectException(\DomainException::class);

        $this->service->execute('session-1');
    }
}
