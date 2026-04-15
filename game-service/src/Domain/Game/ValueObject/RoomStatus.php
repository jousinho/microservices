<?php

declare(strict_types=1);

namespace App\Domain\Game\ValueObject;

enum RoomStatus: string
{
    case Waiting = 'waiting';
    case Playing = 'playing';
    case Ended   = 'ended';
}
