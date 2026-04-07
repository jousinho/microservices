from pathlib import Path

import numpy as np
from scipy.io import wavfile

from src.domain.note.entity.musical_note import MusicalNote
from src.domain.note.value_object.difficulty import Difficulty

SAMPLE_RATE = 44100
DURATION = 2.0


class NumpyNoteGenerator:
    def __init__(self, cache_dir: Path) -> None:
        self._cache_dir = cache_dir
        cache_dir.mkdir(parents=True, exist_ok=True)

    def generate(self, note: MusicalNote, difficulty: Difficulty) -> Path:
        path = self._cache_dir / f"{note.note_id}_d{difficulty.value}.wav"
        if not path.exists():
            wave = self._build_wave(note.frequency, difficulty)
            wave = wave * self._make_envelope(len(wave))
            wave_int16 = (wave / np.max(np.abs(wave)) * 32767).astype(np.int16)
            wavfile.write(str(path), SAMPLE_RATE, wave_int16)
        return path

    def _build_wave(self, frequency: float, difficulty: Difficulty) -> np.ndarray:
        t = np.linspace(0, DURATION, int(SAMPLE_RATE * DURATION))

        if difficulty.value == 1:
            return np.sin(2 * np.pi * frequency * t)

        if difficulty.value == 2:
            return (
                1.00 * np.sin(2 * np.pi * frequency * t)
                + 0.50 * np.sin(2 * np.pi * frequency * 2 * t)
                + 0.25 * np.sin(2 * np.pi * frequency * 3 * t)
                + 0.10 * np.sin(2 * np.pi * frequency * 4 * t)
            )

        # difficulty 3: acorde mayor (raíz + tercera mayor + quinta justa)
        third = frequency * 2 ** (4 / 12)
        fifth = frequency * 2 ** (7 / 12)
        return (
            np.sin(2 * np.pi * frequency * t)
            + np.sin(2 * np.pi * third * t)
            + np.sin(2 * np.pi * fifth * t)
        ) / 3

    def _make_envelope(self, num_samples: int) -> np.ndarray:
        envelope = np.ones(num_samples)
        attack = int(0.05 * SAMPLE_RATE)
        release = int(0.30 * SAMPLE_RATE)
        envelope[:attack] = np.linspace(0, 1, attack)
        envelope[-release:] = np.linspace(1, 0, release)
        return envelope
