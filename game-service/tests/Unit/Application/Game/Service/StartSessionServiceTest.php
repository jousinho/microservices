<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Game\Service;

use App\Application\Game\Service\StartSessionService;
use App\Domain\Game\Event\RoundWasStarted;
use App\Domain\Game\Event\SessionWasStarted;
use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\NoteData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class StartSessionServiceTest extends TestCase
{
    private SessionRepositoryInterface&MockObject $sessions;
    private RoundRepositoryInterface&MockObject $rounds;
    private NoteClientInterface&MockObject $noteClient;
    private StartSessionService $service;

    protected function setUp(): void
    {
        $this->sessions   = $this->createMock(SessionRepositoryInterface::class);
        $this->rounds     = $this->createMock(RoundRepositoryInterface::class);
        $this->noteClient = $this->createMock(NoteClientInterface::class);
        $this->service    = new StartSessionService($this->sessions, $this->rounds, $this->noteClient);
    }

    public function test_starting_session__should_return_session_id_and_active_status(): void
    {
        $this->noteClient->method('getRandomNote')->willReturn(
            new NoteData('do_4', 'do', 'http://audio-brain/api/notes/do_4/audio')
        );

        $result = $this->service->execute('session-1', 'round-1', 2, 5);

        $this->assertSame('session-1', $result['session_id']);
        $this->assertSame('active', $result['status']);
        $this->assertSame(2, $result['difficulty']);
        $this->assertSame(5, $result['total_rounds']);
        $this->assertSame(1, $result['current_round']);
        $this->assertSame(0, $result['score']);
    }

    public function test_starting_session__should_emit_SessionWasStarted_event(): void
    {
        $this->noteClient->method('getRandomNote')->willReturn(
            new NoteData('do_4', 'do', 'http://audio-brain/api/notes/do_4/audio')
        );

        $result = $this->service->execute('session-1', 'round-1', 1, 3);

        $this->assertContains(SessionWasStarted::class, $result['events']);
    }

    public function test_starting_session__should_emit_RoundWasStarted_event(): void
    {
        $this->noteClient->method('getRandomNote')->willReturn(
            new NoteData('do_4', 'do', 'http://audio-brain/api/notes/do_4/audio')
        );

        $result = $this->service->execute('session-1', 'round-1', 1, 3);

        $this->assertContains(RoundWasStarted::class, $result['events']);
    }

    public function test_starting_session__should_assign_note_to_first_round(): void
    {
        $this->noteClient->method('getRandomNote')->willReturn(
            new NoteData('re_4', 're', 'http://audio-brain/api/notes/re_4/audio')
        );

        $result = $this->service->execute('session-1', 'round-1', 1, 3);

        $this->assertSame('re_4', $result['note_id']);
        $this->assertSame('http://audio-brain/api/notes/re_4/audio', $result['audio_url']);
    }

    public function test_starting_session__should_save_session_and_round(): void
    {
        $this->noteClient->method('getRandomNote')->willReturn(
            new NoteData('do_4', 'do', 'http://audio-brain/api/notes/do_4/audio')
        );

        $this->sessions->expects($this->once())->method('save');
        $this->rounds->expects($this->once())->method('save');

        $this->service->execute('session-1', 'round-1', 1, 3);
    }
}
