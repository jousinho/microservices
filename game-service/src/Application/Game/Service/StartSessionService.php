<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Domain\Game\Entity\Session;
use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\NoteId;

final class StartSessionService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly RoundRepositoryInterface $rounds,
        private readonly NoteClientInterface $noteClient,
    ) {}

    public function execute(string $sessionId, string $roundId, int $difficulty, int $totalRounds): array
    {
        $session = Session::create($sessionId, Difficulty::create($difficulty), $totalRounds);
        $round   = $session->startNextRound($roundId);

        $noteData = $this->noteClient->getRandomNote($difficulty);
        $round->assignNote(NoteId::create($noteData->noteId), $noteData->solfege);

        $this->sessions->save($session);
        $this->rounds->save($round);

        $events = $session->pullDomainEvents();

        return [
            'session_id'    => $session->id(),
            'status'        => $session->status()->value,
            'difficulty'    => $session->difficulty()->value(),
            'total_rounds'  => $session->totalRounds(),
            'current_round' => $session->currentRound(),
            'score'         => $session->score()->value(),
            'round_id'      => $round->id(),
            'note_id'       => $noteData->noteId,
            'audio_url'     => $noteData->audioUrl,
            'events'        => array_map(fn($e) => $e::class, $events),
        ];
    }
}
