<?php

declare(strict_types=1);

namespace App\Domain\Game\Repository;

use App\Domain\Game\Entity\Room;

interface RoomRepositoryInterface
{
    public function save(Room $room): void;

    public function findById(string $id): ?Room;

    public function findByCode(string $code): ?Room;
}
