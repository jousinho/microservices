<?php

declare(strict_types=1);

namespace App\Application\Game\Command;

final class SubmitAnswerCommand
{
    public readonly string $roundId;
    public readonly string $guess;

    public function __construct(string $roundId, mixed $guess)
    {
        if (!is_string($guess) || trim($guess) === '') {
            throw new \InvalidArgumentException('guess is required');
        }

        $this->roundId = $roundId;
        $this->guess   = trim($guess);
    }
}
