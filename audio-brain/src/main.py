from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from src.domain.note.catalog import NOTE_CATALOG
from src.domain.note.value_object.difficulty import Difficulty
from src.infrastructure.dependencies import get_note_generator
from src.infrastructure.note.http.routers.note_router import router as note_router


@asynccontextmanager
async def lifespan(app: FastAPI):
    _pregenerate_audio_cache()
    yield


def _pregenerate_audio_cache() -> None:
    generator = get_note_generator()
    for note in NOTE_CATALOG:
        for diff_value in (1, 2, 3):
            generator.generate(note, Difficulty(diff_value))


app = FastAPI(title="audio-brain", lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:3000"],
    allow_methods=["GET"],
    allow_headers=["*"],
)

app.include_router(note_router, prefix="/api")
