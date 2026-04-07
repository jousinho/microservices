from src.domain.note.entity.musical_note import MusicalNote

NOTE_CATALOG: list[MusicalNote] = [
    # Octava 3
    MusicalNote(note_id="do_3", name="Do", solfege="do", frequency=130.81, octave=3),
    MusicalNote(note_id="re_3", name="Re", solfege="re", frequency=146.83, octave=3),
    MusicalNote(note_id="mi_3", name="Mi", solfege="mi", frequency=164.81, octave=3),
    MusicalNote(note_id="fa_3", name="Fa", solfege="fa", frequency=174.61, octave=3),
    MusicalNote(note_id="sol_3", name="Sol", solfege="sol", frequency=196.00, octave=3),
    MusicalNote(note_id="la_3", name="La", solfege="la", frequency=220.00, octave=3),
    MusicalNote(note_id="si_3", name="Si", solfege="si", frequency=246.94, octave=3),
    # Octava 4
    MusicalNote(note_id="do_4", name="Do", solfege="do", frequency=261.63, octave=4),
    MusicalNote(note_id="re_4", name="Re", solfege="re", frequency=293.66, octave=4),
    MusicalNote(note_id="mi_4", name="Mi", solfege="mi", frequency=329.63, octave=4),
    MusicalNote(note_id="fa_4", name="Fa", solfege="fa", frequency=349.23, octave=4),
    MusicalNote(note_id="sol_4", name="Sol", solfege="sol", frequency=392.00, octave=4),
    MusicalNote(note_id="la_4", name="La", solfege="la", frequency=440.00, octave=4),
    MusicalNote(note_id="si_4", name="Si", solfege="si", frequency=493.88, octave=4),
    # Octava 5
    MusicalNote(note_id="do_5", name="Do", solfege="do", frequency=523.25, octave=5),
    MusicalNote(note_id="re_5", name="Re", solfege="re", frequency=587.33, octave=5),
    MusicalNote(note_id="mi_5", name="Mi", solfege="mi", frequency=659.25, octave=5),
    MusicalNote(note_id="fa_5", name="Fa", solfege="fa", frequency=698.46, octave=5),
    MusicalNote(note_id="sol_5", name="Sol", solfege="sol", frequency=783.99, octave=5),
    MusicalNote(note_id="la_5", name="La", solfege="la", frequency=880.00, octave=5),
    MusicalNote(note_id="si_5", name="Si", solfege="si", frequency=987.77, octave=5),
]
