<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Game;

use App\Domain\Game\Entity\Session;
use App\Domain\Game\ValueObject\Difficulty;
use App\Domain\Game\ValueObject\SessionStatus;
use App\Infrastructure\Game\Persistence\Doctrine\DoctrineSessionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineSessionRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private DoctrineSessionRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get('doctrine.orm.entity_manager');
        $this->em->beginTransaction();

        $this->repository = new DoctrineSessionRepository($this->em);
    }

    protected function tearDown(): void
    {
        if ($this->em->getConnection()->isTransactionActive()) {
            $this->em->rollback();
        }

        $this->em->close();

        parent::tearDown();
    }

    public function test_saving_session__should_be_retrievable_by_id(): void
    {
        $session = Session::create('test-session-1', Difficulty::create(2), 5);

        $this->repository->save($session);
        $this->em->clear();

        $found = $this->repository->findById('test-session-1');

        $this->assertNotNull($found);
        $this->assertSame('test-session-1', $found->id());
        $this->assertSame(2, $found->difficulty()->value());
        $this->assertSame(5, $found->totalRounds());
        $this->assertSame(SessionStatus::Active, $found->status());
    }

    public function test_finding_session__when_not_exists__should_return_null(): void
    {
        $result = $this->repository->findById('non-existent-id');

        $this->assertNull($result);
    }

    public function test_saving_session__score_and_round_progress__should_persist(): void
    {
        $session = Session::create('test-session-2', Difficulty::create(1), 3);
        $session->startNextRound('round-1');

        $this->repository->save($session);
        $this->em->clear();

        $found = $this->repository->findById('test-session-2');

        $this->assertSame(1, $found->currentRound());
        $this->assertSame(0, $found->score()->value());
    }
}
