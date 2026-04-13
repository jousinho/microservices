<?php

declare(strict_types=1);

namespace App\Infrastructure\Game\Http\Controller;

use App\Application\Game\Service\NextRoundService;
use App\Application\Game\Service\StartSessionService;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/api')]
final class SessionController
{
    public function __construct(
        private readonly StartSessionService $startSession,
        private readonly NextRoundService $nextRound,
        private readonly SessionRepositoryInterface $sessions,
    ) {}

    #[Route('/sessions', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        $difficulty  = $body['difficulty'] ?? null;
        $totalRounds = $body['total_rounds'] ?? null;

        if (!is_int($difficulty) || !in_array($difficulty, [1, 2, 3], true)) {
            return new JsonResponse(['error' => 'difficulty must be 1, 2 or 3'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        if (!is_int($totalRounds) || $totalRounds < 1) {
            return new JsonResponse(['error' => 'total_rounds must be a positive integer'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->startSession->execute(
                sessionId:   Uuid::v4()->toRfc4122(),
                roundId:     Uuid::v4()->toRfc4122(),
                difficulty:  $difficulty,
                totalRounds: $totalRounds,
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/sessions/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $session = $this->sessions->findById($id);

        if ($session === null) {
            return new JsonResponse(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse([
            'session_id'    => $session->id(),
            'status'        => $session->status()->value,
            'difficulty'    => $session->difficulty()->value(),
            'total_rounds'  => $session->totalRounds(),
            'current_round' => $session->currentRound(),
            'score'         => $session->score()->value(),
        ]);
    }

    #[Route('/sessions/{id}/next-round', methods: ['POST'])]
    public function nextRound(string $id): JsonResponse
    {
        try {
            $result = $this->nextRound->execute(
                sessionId: $id,
                roundId:   Uuid::v4()->toRfc4122(),
            );
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/sessions/{id}/scoreboard', methods: ['GET'])]
    public function scoreboard(string $id): JsonResponse
    {
        $session = $this->sessions->findById($id);

        if ($session === null) {
            return new JsonResponse(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        if ($session->status()->value === 'active') {
            return new JsonResponse(['error' => 'Session is still active'], Response::HTTP_CONFLICT);
        }

        return new JsonResponse([
            'session_id'   => $session->id(),
            'score'        => $session->score()->value(),
            'total_rounds' => $session->totalRounds(),
        ]);
    }
}
