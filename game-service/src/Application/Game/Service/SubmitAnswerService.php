<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\Command\SubmitAnswerCommand;
use App\Application\Game\DTO\SubmitAnswerDTO;
use App\Domain\Game\Entity\Round;
use App\Domain\Game\Entity\Session;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\Score;

final class SubmitAnswerService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly RoundRepositoryInterface $rounds,
    ) {}

    public function execute(SubmitAnswerCommand $command): SubmitAnswerDTO
    {
        $round   = $this->findRoundOrFail($command->roundId);
        $session = $this->findSessionOrFail($round->sessionId());

        $isCorrect = $round->isCorrectAnswer($command->guess);

        $this->applyAnswer($session, $round, $command->guess, $isCorrect);
        $this->closeSessionIfFinished($session);

        $this->rounds->save($round);
        $this->sessions->save($session);
        $session->pullDomainEvents();

        return SubmitAnswerDTO::create(
            is_correct:    $isCorrect,
            correct_note:  $round->correctNote(),
            score:         $session->score()->value(),
            session_ended: $session->status()->value === 'ended',
        );
    }

    private function findRoundOrFail(string $roundId): Round
    {
        $round = $this->rounds->findById($roundId);

        if ($round === null) {
            throw new \DomainException(sprintf('Round "%s" not found', $roundId));
        }

        return $round;
    }

    private function findSessionOrFail(string $sessionId): Session
    {
        $session = $this->sessions->findById($sessionId);

        if ($session === null) {
            throw new \DomainException(sprintf('Session "%s" not found', $sessionId));
        }

        return $session;
    }

    private function applyAnswer(Session $session, Round $round, string $guess, bool $isCorrect): void
    {
        $session->submitAnswer($round->id(), $guess, $isCorrect);

        if ($isCorrect) {
            $session->addScore(Score::create(1));
        }

        $round->end();
    }

    private function closeSessionIfFinished(Session $session): void
    {
        if ($session->currentRound() >= $session->totalRounds()) {
            $session->end();
        }
    }
}
