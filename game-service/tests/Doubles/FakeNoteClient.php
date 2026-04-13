<?php

declare(strict_types=1);

namespace App\Tests\Doubles;

use App\Domain\Game\Repository\NoteClientInterface;
use App\Domain\Game\ValueObject\NoteData;

final class FakeNoteClient implements NoteClientInterface
{
    public function getRandomNote(int $difficulty): NoteData
    {
        return new NoteData(
            noteId:   'do_4',
            solfege:  'do',
            audioUrl: 'http://audio-brain/api/notes/do_4/audio?difficulty=' . $difficulty,
        );
    }
}
