from fastapi import APIRouter, Depends, HTTPException
from fastapi.responses import FileResponse

from src.application.note.service.get_random_note_service import GetRandomNoteService
from src.domain.note.catalog import NOTE_CATALOG
from src.domain.note.value_object.difficulty import Difficulty
from src.infrastructure.dependencies import get_note_generator, get_note_service
from src.infrastructure.note.audio.numpy_note_generator import NumpyNoteGenerator

router = APIRouter(prefix="/notes", tags=["notes"])


@router.get("")
def list_notes() -> list[dict]:
    return [
        {
            "note_id": note.note_id,
            "name": note.name,
            "solfege": note.solfege,
            "frequency": note.frequency,
            "octave": note.octave,
        }
        for note in NOTE_CATALOG
    ]


@router.get("/random")
def get_random_note(
    difficulty: int = 1,
    service: GetRandomNoteService = Depends(get_note_service),
) -> dict:
    try:
        diff = Difficulty(difficulty)
    except ValueError as e:
        raise HTTPException(status_code=422, detail=str(e))

    note, _ = service.execute(diff)
    return {
        "note_id": note.note_id,
        "name": note.name,
        "solfege": note.solfege,
        "octave": note.octave,
        "audio_url": f"/api/notes/{note.note_id}/audio?difficulty={difficulty}",
    }


@router.get("/{note_id}/audio")
def get_note_audio(
    note_id: str,
    difficulty: int = 1,
    generator: NumpyNoteGenerator = Depends(get_note_generator),
) -> FileResponse:
    note = next((n for n in NOTE_CATALOG if n.note_id == note_id), None)
    if note is None:
        raise HTTPException(status_code=404, detail=f"Note '{note_id}' not found")

    try:
        diff = Difficulty(difficulty)
    except ValueError as e:
        raise HTTPException(status_code=422, detail=str(e))

    audio_path = generator.generate(note, diff)
    return FileResponse(str(audio_path), media_type="audio/wav")
