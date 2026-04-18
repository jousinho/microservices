<?php

declare(strict_types=1);

namespace App\Infrastructure\Game\Http\Controller;

use App\Application\Game\Command\StartSessionCommand;
use App\Application\Game\Service\GetSessionScoreboardService;
use App\Application\Game\Service\GetSessionService;
use App\Application\Game\Service\NextRoundService;
use App\Application\Game\Service\StartSessionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class SessionController
{
    public function __construct(
        private readonly StartSessionService $startSession,
        private readonly NextRoundService $nextRound,
        private readonly GetSessionService $getSession,
        private readonly GetSessionScoreboardService $getScoreboard,
    ) {}

    #[Route('/sessions', methods: ['POST'])]
    public function start(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $command = new StartSessionCommand($body['difficulty'] ?? null, $body['total_rounds'] ?? null);
            $result  = $this->startSession->execute($command);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/sessions/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $data = $this->getSession->execute($id);

        if ($data === null) {
            return new JsonResponse(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($data);
    }

    #[Route('/sessions/{id}/next-round', methods: ['POST'])]
    public function nextRound(string $id): JsonResponse
    {
        try {
            $result = $this->nextRound->execute($id);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/sessions/{id}/scoreboard', methods: ['GET'])]
    public function scoreboard(string $id): JsonResponse
    {
        try {
            $data = $this->getScoreboard->execute($id);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        if ($data === null) {
            return new JsonResponse(['error' => 'Session not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($data);
    }
}
