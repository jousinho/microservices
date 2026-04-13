<?php

declare(strict_types=1);

namespace App\Infrastructure\Game\Persistence\Doctrine;

use App\Domain\Game\Entity\Round;
use App\Domain\Game\Repository\RoundRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineRoundRepository implements RoundRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function save(Round $round): void
    {
        $this->em->persist($round);
        $this->em->flush();
    }

    public function findById(string $id): ?Round
    {
        return $this->em->find(Round::class, $id);
    }
}
