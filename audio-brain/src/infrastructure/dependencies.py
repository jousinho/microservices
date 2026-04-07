import os
from pathlib import Path

from src.application.note.service.get_random_note_service import GetRandomNoteService
from src.infrastructure.note.audio.numpy_note_generator import NumpyNoteGenerator

AUDIO_CACHE_DIR = Path(os.getenv("AUDIO_CACHE_DIR", "/app/audio_cache"))

_generator = NumpyNoteGenerator(cache_dir=AUDIO_CACHE_DIR)
_service = GetRandomNoteService(note_generator=_generator)


def get_note_generator() -> NumpyNoteGenerator:
    return _generator


def get_note_service() -> GetRandomNoteService:
    return _service
