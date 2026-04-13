<?php

declare(strict_types=1);

namespace App\Infrastructure\Game\Persistence\Doctrine;

use App\Domain\Game\Entity\Session;
use App\Domain\Game\Repository\SessionRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineSessionRepository implements SessionRepositoryInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function save(Session $session): void
    {
        $this->em->persist($session);
        $this->em->flush();
    }

    public function findById(string $id): ?Session
    {
        return $this->em->find(Session::class, $id);
    }
}
