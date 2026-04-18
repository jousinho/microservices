<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\DTO\GetSessionScoreboardDTO;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use App\Domain\Game\ValueObject\SessionStatus;

final class GetSessionScoreboardService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
    ) {}

    public function execute(string $id): ?GetSessionScoreboardDTO
    {
        $session = $this->sessions->findById($id);

        if ($session === null) {
            return null;
        }

        if ($session->status() === SessionStatus::Active) {
            throw new \DomainException('Session is still active');
        }

        return GetSessionScoreboardDTO::create(
            session_id:   $session->id(),
            score:        $session->score()->value(),
            total_rounds: $session->totalRounds(),
        );
    }
}
