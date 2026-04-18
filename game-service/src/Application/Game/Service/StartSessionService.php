<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\Command\StartSessionCommand;
use App\Application\Game\DTO\StartSessionDTO;
use App\Domain\Game\Entity\Round;
use App\Domain\Game\Entity\Session;
use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\NoteData;
use App\Domain\Game\ValueObject\NoteId;
use Symfony\Component\Uid\Uuid;

final class StartSessionService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly RoundRepositoryInterface $rounds,
        private readonly NoteClientInterface $noteClient,
    ) {}

    public function execute(StartSessionCommand $command): StartSessionDTO
    {
        $session  = $this->createSession($command);
        $noteData = $this->noteClient->getRandomNote($command->difficulty);
        $round    = $this->startFirstRound($session, $noteData);

        $this->sessions->save($session);
        $this->rounds->save($round);
        $session->pullDomainEvents();

        return StartSessionDTO::create(
            session_id:    $session->id(),
            status:        $session->status()->value,
            difficulty:    $session->difficulty()->value(),
            total_rounds:  $session->totalRounds(),
            current_round: $session->currentRound(),
            score:         $session->score()->value(),
            round_id:      $round->id(),
            note_id:       $noteData->noteId,
            audio_url:     $noteData->audioUrl,
        );
    }

    private function createSession(StartSessionCommand $command): Session
    {
        return Session::create(
            Uuid::v4()->toRfc4122(),
            Difficulty::create($command->difficulty),
            $command->totalRounds,
        );
    }

    private function startFirstRound(Session $session, NoteData $noteData): Round
    {
        $round = $session->startNextRound(Uuid::v4()->toRfc4122());
        $round->assignNote(NoteId::create($noteData->noteId), $noteData->solfege);

        return $round;
    }
}
