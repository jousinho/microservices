<?php

declare(strict_types=1);

namespace App\Domain\Game\Repository;

use App\Domain\Game\Entity\Round;

interface RoundRepositoryInterface
{
    public function save(Round $round): void;

    public function findById(string $id): ?Round;
}
