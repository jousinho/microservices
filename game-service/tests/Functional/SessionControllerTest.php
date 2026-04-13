<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SessionControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em     = static::getContainer()->get('doctrine.orm.entity_manager');
    }

    protected function tearDown(): void
    {
        $this->em->getConnection()->executeStatement('DELETE FROM answers');
        $this->em->getConnection()->executeStatement('DELETE FROM rounds');
        $this->em->getConnection()->executeStatement('DELETE FROM sessions');
        $this->em->close();

        parent::tearDown();
    }

    private function postJson(string $uri, array $body): void
    {
        $this->client->request('POST', $uri, content: json_encode($body), server: [
            'CONTENT_TYPE' => 'application/json',
        ]);
    }

    // ── POST /api/sessions ─────────────────────────────────────────────────────

    public function test_starting_session__should_return_201_with_session_id(): void
    {
        $this->postJson('/api/sessions', ['difficulty' => 1, 'total_rounds' => 5]);

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('session_id', $data);
        $this->assertArrayHasKey('round_id', $data);
    }

    public function test_starting_session__should_return_active_status(): void
    {
        $this->postJson('/api/sessions', ['difficulty' => 2, 'total_rounds' => 3]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('active', $data['status']);
        $this->assertSame(2, $data['difficulty']);
        $this->assertSame(3, $data['total_rounds']);
        $this->assertSame(1, $data['current_round']);
        $this->assertSame(0, $data['score']);
    }

    public function test_starting_session__with_invalid_difficulty__should_return_422(): void
    {
        $this->postJson('/api/sessions', ['difficulty' => 5, 'total_rounds' => 3]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function test_starting_session__with_missing_fields__should_return_422(): void
    {
        $this->postJson('/api/sessions', ['difficulty' => 1]);

        $this->assertResponseStatusCodeSame(422);
    }

    // ── GET /api/sessions/{id} ─────────────────────────────────────────────────

    public function test_getting_session__should_return_current_round_and_score(): void
    {
        $this->postJson('/api/sessions', ['difficulty' => 1, 'total_rounds' => 3]);
        $sessionId = json_decode($this->client->getResponse()->getContent(), true)['session_id'];

        $this->client->request('GET', '/api/sessions/' . $sessionId);

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($sessionId, $data['session_id']);
        $this->assertSame(1, $data['current_round']);
        $this->assertSame(0, $data['score']);
    }

    public function test_getting_session__when_not_found__should_return_404(): void
    {
        $this->client->request('GET', '/api/sessions/non-existent-id');

        $this->assertResponseStatusCodeSame(404);
    }

    // ── POST /api/sessions/{id}/next-round ─────────────────────────────────────

    public function test_next_round__should_return_note_id_and_audio_url(): void
    {
        $this->postJson('/api/sessions', ['difficulty' => 1, 'total_rounds' => 3]);
        $sessionId = json_decode($this->client->getResponse()->getContent(), true)['session_id'];

        $this->postJson('/api/sessions/' . $sessionId . '/next-round', []);

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('round_id', $data);
        $this->assertArrayHasKey('note_id', $data);
        $this->assertArrayHasKey('audio_url', $data);
    }

    public function test_next_round__when_session_not_found__should_return_409(): void
    {
        $this->postJson('/api/sessions/non-existent-id/next-round', []);

        $this->assertResponseStatusCodeSame(409);
    }

    public function test_next_round__when_all_rounds_played__should_return_409(): void
    {
        $this->postJson('/api/sessions', ['difficulty' => 1, 'total_rounds' => 1]);
        $sessionId = json_decode($this->client->getResponse()->getContent(), true)['session_id'];

        $this->postJson('/api/sessions/' . $sessionId . '/next-round', []);

        $this->assertResponseStatusCodeSame(409);
    }

    // ── GET /api/sessions/{id}/scoreboard ──────────────────────────────────────

    public function test_scoreboard__when_session_not_found__should_return_404(): void
    {
        $this->client->request('GET', '/api/sessions/non-existent-id/scoreboard');

        $this->assertResponseStatusCodeSame(404);
    }

    public function test_scoreboard__when_session_still_active__should_return_409(): void
    {
        $this->postJson('/api/sessions', ['difficulty' => 1, 'total_rounds' => 3]);
        $sessionId = json_decode($this->client->getResponse()->getContent(), true)['session_id'];

        $this->client->request('GET', '/api/sessions/' . $sessionId . '/scoreboard');

        $this->assertResponseStatusCodeSame(409);
    }
}
