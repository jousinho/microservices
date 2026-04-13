<?php

declare(strict_types=1);

namespace App\Domain\Game\Repository;

use App\Domain\Game\ValueObject\NoteData;

interface NoteClientInterface
{
    public function getRandomNote(int $difficulty): NoteData;
}
