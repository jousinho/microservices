import pytest
from fastapi.testclient import TestClient

from src.application.note.service.get_random_note_service import GetRandomNoteService
from src.infrastructure.dependencies import get_note_generator, get_note_service
from src.infrastructure.note.audio.numpy_note_generator import NumpyNoteGenerator
from src.main import app


@pytest.fixture
def client(tmp_path):
    # NumpyNoteGenerator actúa como implementación de NoteGeneratorInterface,
    # apuntando a un directorio temporal para no contaminar audio_cache real
    generator = NumpyNoteGenerator(cache_dir=tmp_path)
    service = GetRandomNoteService(note_generator=generator)
    app.dependency_overrides[get_note_service] = lambda: service
    app.dependency_overrides[get_note_generator] = lambda: generator
    with TestClient(app) as c:
        yield c
    app.dependency_overrides.clear()


def test_listing_notes__should_return_21_notes(client):
    response = client.get("/api/notes")
    assert response.status_code == 200
    assert len(response.json()) == 21


def test_getting_random_note__with_difficulty_1__should_return_octave_4_note(client):
    response = client.get("/api/notes/random?difficulty=1")
    assert response.status_code == 200
    assert response.json()["octave"] == 4


def test_getting_random_note__with_difficulty_above_3__should_return_422(client):
    response = client.get("/api/notes/random?difficulty=4")
    assert response.status_code == 422


def test_getting_note_audio__with_valid_id__should_return_wav_content_type(client):
    response = client.get("/api/notes/la_4/audio?difficulty=1")
    assert response.status_code == 200
    assert response.headers["content-type"] == "audio/wav"


def test_getting_note_audio__with_invalid_id__should_return_404(client):
    response = client.get("/api/notes/nota_falsa/audio")
    assert response.status_code == 404
