<?php

declare(strict_types=1);

namespace App\Infrastructure\Game\Http\Controller;

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
        $body  = json_decode($request->getContent(), true) ?? [];
        $guess = $body['guess'] ?? null;

        if (!is_string($guess) || trim($guess) === '') {
            return new JsonResponse(['error' => 'guess is required'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $this->submitAnswer->execute(roundId: $id, guess: $guess);
        } catch (\DomainException $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($result);
    }
}
