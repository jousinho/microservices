<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RoundControllerTest extends WebTestCase
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

    private function postJson(string $uri, array $body): array
    {
        $this->client->request('POST', $uri, content: json_encode($body), server: [
            'CONTENT_TYPE' => 'application/json',
        ]);

        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }

    private function startSessionAndGetFirstRound(int $difficulty = 1, int $totalRounds = 3): array
    {
        return $this->postJson('/api/sessions', [
            'difficulty'  => $difficulty,
            'total_rounds' => $totalRounds,
        ]);
    }

    // ── POST /api/rounds/{id}/answer ───────────────────────────────────────────

    public function test_submitting_answer__correct__should_return_is_correct_true(): void
    {
        $session = $this->startSessionAndGetFirstRound();

        $result = $this->postJson('/api/rounds/' . $session['round_id'] . '/answer', ['guess' => 'do']);

        $this->assertResponseIsSuccessful();
        $this->assertTrue($result['is_correct']);
        $this->assertSame('do', $result['correct_note']);
    }

    public function test_submitting_answer__wrong__should_return_is_correct_false(): void
    {
        $session = $this->startSessionAndGetFirstRound();

        $result = $this->postJson('/api/rounds/' . $session['round_id'] . '/answer', ['guess' => 're']);

        $this->assertResponseIsSuccessful();
        $this->assertFalse($result['is_correct']);
    }

    public function test_submitting_answer__correct__should_increment_score_in_response(): void
    {
        $session = $this->startSessionAndGetFirstRound();

        $result = $this->postJson('/api/rounds/' . $session['round_id'] . '/answer', ['guess' => 'do']);

        $this->assertSame(1, $result['score']);
    }

    public function test_submitting_answer__wrong__should_not_increment_score(): void
    {
        $session = $this->startSessionAndGetFirstRound();

        $result = $this->postJson('/api/rounds/' . $session['round_id'] . '/answer', ['guess' => 'si']);

        $this->assertSame(0, $result['score']);
    }

    public function test_submitting_answer__when_round_not_found__should_return_404(): void
    {
        $this->postJson('/api/rounds/non-existent-id/answer', ['guess' => 'do']);

        $this->assertResponseStatusCodeSame(404);
    }

    public function test_submitting_answer__with_missing_guess__should_return_422(): void
    {
        $session = $this->startSessionAndGetFirstRound();

        $this->postJson('/api/rounds/' . $session['round_id'] . '/answer', []);

        $this->assertResponseStatusCodeSame(422);
    }

    // ── Scoreboard completo ────────────────────────────────────────────────────

    public function test_scoreboard__after_all_rounds__should_return_final_score(): void
    {
        $session   = $this->startSessionAndGetFirstRound(totalRounds: 1);
        $sessionId = $session['session_id'];
        $roundId   = $session['round_id'];

        $this->postJson('/api/rounds/' . $roundId . '/answer', ['guess' => 'do']);

        $this->client->request('GET', '/api/sessions/' . $sessionId . '/scoreboard');

        $this->assertResponseIsSuccessful();
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame($sessionId, $data['session_id']);
        $this->assertArrayHasKey('score', $data);
        $this->assertSame(1, $data['total_rounds']);
    }
}
