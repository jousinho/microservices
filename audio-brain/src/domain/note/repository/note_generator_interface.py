from pathlib import Path
from typing import Protocol

from src.domain.note.entity.musical_note import MusicalNote
from src.domain.note.value_object.difficulty import Difficulty


class NoteGeneratorInterface(Protocol):
    def generate(self, note: MusicalNote, difficulty: Difficulty) -> Path:
        ...
