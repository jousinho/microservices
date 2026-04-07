<?php

declare(strict_types=1);

namespace App\Domain\Game\Repository;

use App\Domain\Game\Entity\Session;

interface SessionRepositoryInterface
{
    public function save(Session $session): void;

    public function findById(string $id): ?Session;
}
