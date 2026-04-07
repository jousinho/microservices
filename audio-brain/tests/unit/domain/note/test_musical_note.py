import pytest

from src.domain.note.entity.musical_note import MusicalNote


def test_creating_musical_note__with_valid_data__should_store_solfege_and_octave():
    note = MusicalNote(note_id="la_4", name="La", solfege="la", frequency=440.0, octave=4)
    assert note.solfege == "la"
    assert note.octave == 4
    assert note.frequency == 440.0


def test_creating_musical_note__with_negative_frequency__should_raise_error():
    with pytest.raises(ValueError, match="Frequency must be positive"):
        MusicalNote(note_id="la_4", name="La", solfege="la", frequency=-1.0, octave=4)


def test_creating_musical_note__with_zero_frequency__should_raise_error():
    with pytest.raises(ValueError, match="Frequency must be positive"):
        MusicalNote(note_id="la_4", name="La", solfege="la", frequency=0.0, octave=4)


def test_creating_musical_note__with_invalid_octave__should_raise_error():
    with pytest.raises(ValueError, match="Octave must be 3, 4 or 5"):
        MusicalNote(note_id="la_6", name="La", solfege="la", frequency=1760.0, octave=6)


def test_musical_note__is_immutable():
    note = MusicalNote(note_id="la_4", name="La", solfege="la", frequency=440.0, octave=4)
    with pytest.raises(Exception):
        note.frequency = 500.0  # type: ignore
