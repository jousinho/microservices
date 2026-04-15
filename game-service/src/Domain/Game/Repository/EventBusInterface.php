<?php

declare(strict_types=1);

namespace App\Domain\Game\Repository;

interface EventBusInterface
{
    public function publish(object ...$events): void;
}
