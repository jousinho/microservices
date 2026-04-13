<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\NoteId;

final class NextRoundService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly RoundRepositoryInterface $rounds,
        private readonly NoteClientInterface $noteClient,
    ) {}

    public function execute(string $sessionId, string $roundId): array
    {
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            throw new \DomainException(sprintf('Session "%s" not found', $sessionId));
        }

        $round = $session->startNextRound($roundId);

        $noteData = $this->noteClient->getRandomNote($session->difficulty()->value());
        $round->assignNote(NoteId::create($noteData->noteId), $noteData->solfege);

        $this->sessions->save($session);
        $this->rounds->save($round);

        $events = $session->pullDomainEvents();

        return [
            'round_id'     => $round->id(),
            'round_number' => $round->roundNumber(),
            'note_id'      => $noteData->noteId,
            'audio_url'    => $noteData->audioUrl,
            'events'       => array_map(fn($e) => $e::class, $events),
        ];
    }
}
