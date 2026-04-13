<?php

declare(strict_types=1);

namespace App\Domain\Game\ValueObject;

final class NoteData
{
    public function __construct(
        public readonly string $noteId,
        public readonly string $solfege,
        public readonly string $audioUrl,
    ) {}
}
