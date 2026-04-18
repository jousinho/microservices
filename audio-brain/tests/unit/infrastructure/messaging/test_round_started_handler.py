import json
from pathlib import Path
from unittest.mock import AsyncMock, MagicMock, patch

import pytest

from src.domain.note.entity.musical_note import MusicalNote
from src.domain.note.value_object.difficulty import Difficulty
from src.infrastructure.messaging.round_started_handler import RoundStartedHandler


def _make_message(payload: dict) -> MagicMock:
    message = MagicMock()
    message.body = json.dumps(payload).encode()
    message.process = MagicMock(return_value=AsyncMock(__aenter__=AsyncMock(), __aexit__=AsyncMock()))

    return message


@pytest.fixture()
def note() -> MusicalNote:
    return MusicalNote(note_id="do4", name="C4", solfege="Do", frequency=261.63, octave=4)


@pytest.fixture()
def note_service(note):
    service = MagicMock()
    service.execute.return_value = (note, Path("/app/audio_cache/do4.wav"))

    return service


@pytest.fixture()
def exchange():
    ex = MagicMock()
    ex.publish = AsyncMock()

    return ex


@pytest.mark.asyncio
async def test_handling_round_started__should_publish_note_ready_with_correct_note_id(note_service, exchange, note):
    handler = RoundStartedHandler(note_service=note_service, exchange=exchange)
    message = _make_message({"room_id": "room-1", "round_id": "round-1", "difficulty": 1})

    await handler.handle(message)

    note_service.execute.assert_called_once_with(Difficulty(1))

    published_body = json.loads(exchange.publish.call_args[0][0].body.decode())
    assert published_body["note_id"]  == note.note_id
    assert published_body["room_id"]  == "room-1"
    assert published_body["round_id"] == "round-1"
    assert "audio_url" in published_body
