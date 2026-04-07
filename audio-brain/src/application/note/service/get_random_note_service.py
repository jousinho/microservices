import random
from pathlib import Path

from src.domain.note.catalog import NOTE_CATALOG
from src.domain.note.entity.musical_note import MusicalNote
from src.domain.note.repository.note_generator_interface import NoteGeneratorInterface
from src.domain.note.value_object.difficulty import Difficulty


class GetRandomNoteService:
    def __init__(self, note_generator: NoteGeneratorInterface) -> None:
        self._note_generator = note_generator

    def execute(self, difficulty: Difficulty) -> tuple[MusicalNote, Path]:
        note = self._select_note(difficulty)
        audio_path = self._note_generator.generate(note, difficulty)
        return note, audio_path

    def _select_note(self, difficulty: Difficulty) -> MusicalNote:
        if difficulty.value == 1:
            candidates = [n for n in NOTE_CATALOG if n.octave == 4]
        else:
            candidates = list(NOTE_CATALOG)
        return random.choice(candidates)
