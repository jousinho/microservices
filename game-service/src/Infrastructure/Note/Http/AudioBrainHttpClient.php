<?php

declare(strict_types=1);

namespace App\Infrastructure\Note\Http;

use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\ValueObject\NoteData;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class AudioBrainHttpClient implements NoteClientInterface
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $audioBrainUrl,
    ) {}

    public function getRandomNote(int $difficulty): NoteData
    {
        $response = $this->httpClient->request('GET', $this->audioBrainUrl . '/api/notes/random', [
            'query' => ['difficulty' => $difficulty],
        ]);

        $data = $response->toArray();

        return new NoteData(
            noteId:   $data['note_id'],
            solfege:  $data['solfege'],
            audioUrl: $this->audioBrainUrl . $data['audio_url'],
        );
    }
}
