<?php

declare(strict_types=1);

namespace App\Domain\Game\ValueObject;

enum SessionStatus: string
{
    case Active = 'active';
    case Ended  = 'ended';
}
