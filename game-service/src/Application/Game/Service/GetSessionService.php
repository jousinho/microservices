<?php

declare(strict_types=1);

namespace App\Application\Game\Service;

use App\Application\Game\DTO\GetSessionDTO;
use App\Domain\Game\Repository\SessionRepositoryInterface;

final class GetSessionService
{
    public function __construct(
        private readonly SessionRepositoryInterface $sessions,
    ) {}

    public function execute(string $id): ?GetSessionDTO
    {
        $session = $this->sessions->findById($id);

        if ($session === null) {
            return null;
        }

        return GetSessionDTO::create(
            session_id:    $session->id(),
            status:        $session->status()->value,
            difficulty:    $session->difficulty()->value(),
            total_rounds:  $session->totalRounds(),
            current_round: $session->currentRound(),
            score:         $session->score()->value(),
        );
    }
}
