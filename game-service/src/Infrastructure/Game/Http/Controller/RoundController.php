<?php

declare(strict_types=1);

namespace App\Infrastructure\Game\Http\Controller;

use App\Application\Game\Command\SubmitAnswerCommand;
use App\Application\Game\Service\SubmitAnswerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class RoundController
{
    public function __construct(
        private readonly SubmitAnswerService $submitAnswer,
    ) {}

    #[Route('/rounds/{id}/answer', methods: ['POST'])]
    public function answer(string $id, Request $request): JsonResponse
    {
        $body = json_decode($request->getContent(), true) ?? [];

        try {
            $command = new SubmitAnswerCommand($id, $body['guess'] ?? null);
            $result  = $this->submitAnswer->execute($command);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($result);
    }
}
