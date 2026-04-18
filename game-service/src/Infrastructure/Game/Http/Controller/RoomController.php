<?php

declare(strict_types=1);

namespace App\Infrastructure\Game\Http\Controller;

use App\Application\Game\Command\CreateRoomCommand;
use App\Application\Game\Command\JoinRoomCommand;
use App\Application\Game\Command\StartRoomGameCommand;
use App\Application\Game\Command\SubmitRoomAnswerCommand;
use App\Application\Game\Service\CreateRoomService;
use App\Application\Game\Service\GetRoomService;
use App\Application\Game\Service\JoinRoomService;
use App\Application\Game\Service\StartRoomGameService;
use App\Application\Game\Service\SubmitRoomAnswerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/rooms')]
final class RoomController
{
    public function __construct(
        private readonly CreateRoomService $createRoom,
        private readonly JoinRoomService $joinRoom,
        private readonly StartRoomGameService $startGame,
        private readonly SubmitRoomAnswerService $submitAnswer,
        private readonly GetRoomService $getRoom,
    ) {}

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $command = new CreateRoomCommand($body['host_name'] ?? null, $body['difficulty'] ?? null, $body['total_rounds'] ?? null);
            $result  = $this->createRoom->execute($command);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($result, Response::HTTP_CREATED);
    }

    #[Route('/{code}/join', methods: ['POST'])]
    public function join(string $code, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $command = new JoinRoomCommand($code, $body['player_name'] ?? null);
            $result  = $this->joinRoom->execute($command);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($result);
    }

    #[Route('/{id}/start', methods: ['POST'])]
    public function start(string $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $command = new StartRoomGameCommand($id, $body['player_id'] ?? null);
            $result  = $this->startGame->execute($command);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($result);
    }

    #[Route('/{roomId}/rounds/{roundId}/answer', methods: ['POST'])]
    public function answer(string $roomId, string $roundId, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $command = new SubmitRoomAnswerCommand($roomId, $roundId, $body['player_id'] ?? null, $body['guess'] ?? null);
            $result  = $this->submitAnswer->execute($command);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return new JsonResponse($result);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $data = $this->getRoom->execute($id);

        if ($data === null) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($data);
    }
}
