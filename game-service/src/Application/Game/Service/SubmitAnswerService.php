<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Domain\Game\Repository\RoundRepositoryInterface;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\Score;

final class SubmitAnswerService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
        private readonly RoundRepositoryInterface $rounds,
    ) {}

    public function execute(string $roundId, string $guess): array
    {
        $round = $this->rounds->findById($roundId);

        if ($round === null) {
            throw new \DomainException(sprintf('Round "%s" not found', $roundId));
        }

        $session = $this->sessions->findById($round->sessionId());

        if ($session === null) {
            throw new \DomainException(sprintf('Session "%s" not found', $round->sessionId()));
        }

        $isCorrect = $round->isCorrectAnswer($guess);

        $session->submitAnswer($round->id(), $guess, $isCorrect);

        if ($isCorrect) {
            $session->addScore(Score::create(1));
        }

        $round->end();

        if ($session->currentRound() >= $session->totalRounds()) {
            $session->end();
        }

        $this->rounds->save($round);
        $this->sessions->save($session);

        $events = $session->pullDomainEvents();

        return [
            'is_correct'     => $isCorrect,
            'correct_note'   => $round->correctNote(),
            'score'          => $session->score()->value(),
            'session_ended'  => $session->status()->value === 'ended',
            'events'         => array_map(fn($e) => $e::class, $events),
        ];
    }
}
