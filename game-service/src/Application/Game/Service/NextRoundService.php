<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\DTO\NextRoundDTO;
use App\Domain\Game\Entity\Round;
use App\Domain\Game\Entity\Session;
use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\NoteData;
use App\Domain\Game\ValueObject\NoteId;
use Symfony\Component\Uid\Uuid;

final class NextRoundService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly RoundRepositoryInterface $rounds,
        private readonly NoteClientInterface $noteClient,
    ) {}

    public function execute(string $sessionId): NextRoundDTO
    {
        $session  = $this->findSessionOrFail($sessionId);
        $noteData = $this->noteClient->getRandomNote($session->difficulty()->value());
        $round    = $this->startRoundWithNote($session, $noteData);

        $this->sessions->save($session);
        $this->rounds->save($round);
        $session->pullDomainEvents();

        return NextRoundDTO::create(
            round_id:     $round->id(),
            round_number: $round->roundNumber(),
            note_id:      $noteData->noteId,
            audio_url:    $noteData->audioUrl,
        );
    }

    private function findSessionOrFail(string $sessionId): Session
    {
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            throw new \DomainException(sprintf('Session "%s" not found', $sessionId));
        }

        return $session;
    }

    private function startRoundWithNote(Session $session, NoteData $noteData): Round
    {
        $round = $session->startNextRound(Uuid::v4()->toRfc4122());
        $round->assignNote(NoteId::create($noteData->noteId), $noteData->solfege);

        return $round;
    }
}
