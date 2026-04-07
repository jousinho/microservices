from pathlib import Path

from src.application.note.service.get_random_note_service import GetRandomNoteService
from src.domain.note.entity.musical_note import MusicalNote
from src.domain.note.value_object.difficulty import Difficulty


# Fake de NoteGeneratorInterface — no toca disco ni numpy
class FakeNoteGenerator:
    def generate(self, note: MusicalNote, difficulty: Difficulty) -> Path:
        return Path(f"/fake/{note.note_id}_d{difficulty.value}.wav")


def test_get_random_note__with_difficulty_1__should_return_octave_4_note():
    service = GetRandomNoteService(note_generator=FakeNoteGenerator())
    note, _ = service.execute(Difficulty(1))
    assert note.octave == 4


def test_get_random_note__with_difficulty_2__should_return_any_octave():
    service = GetRandomNoteService(note_generator=FakeNoteGenerator())
    note, _ = service.execute(Difficulty(2))
    assert note.octave in (3, 4, 5)


def test_get_random_note__with_difficulty_3__should_return_any_octave():
    service = GetRandomNoteService(note_generator=FakeNoteGenerator())
    note, _ = service.execute(Difficulty(3))
    assert note.octave in (3, 4, 5)


def test_get_random_note__should_call_generator_with_correct_difficulty():
    recorded: list[int] = []

    # Fake de NoteGeneratorInterface — registra las dificultades recibidas
    class TrackingGenerator:
        def generate(self, note: MusicalNote, difficulty: Difficulty) -> Path:
            recorded.append(difficulty.value)
            return Path(f"/fake/{note.note_id}.wav")

    service = GetRandomNoteService(note_generator=TrackingGenerator())
    service.execute(Difficulty(2))
    assert recorded == [2]


def test_get_random_note__should_return_audio_path():
    service = GetRandomNoteService(note_generator=FakeNoteGenerator())
    _, path = service.execute(Difficulty(1))
    assert isinstance(path, Path)
