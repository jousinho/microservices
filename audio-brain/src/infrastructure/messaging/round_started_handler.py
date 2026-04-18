import json

import aio_pika

from src.application.note.service.get_random_note_service import GetRandomNoteService
from src.domain.note.value_object.difficulty import Difficulty


class RoundStartedHandler:
    def __init__(self, note_service: GetRandomNoteService, exchange: aio_pika.abc.AbstractExchange) -> None:
        self._note_service = note_service
        self._exchange = exchange

    async def handle(self, message: aio_pika.abc.AbstractIncomingMessage) -> None:
        async with message.process():
            payload = self._parse(message)
            note, audio_path = self._note_service.execute(Difficulty(payload["difficulty"]))

            await self._publish_note_ready(
                room_id=payload["room_id"],
                round_id=payload["round_id"],
                note_id=note.note_id,
                audio_url=f"/api/notes/{note.note_id}/audio",
            )

    def _parse(self, message: aio_pika.abc.AbstractIncomingMessage) -> dict:
        return json.loads(message.body.decode())

    async def _publish_note_ready(self, room_id: str, round_id: str, note_id: str, audio_url: str) -> None:
        body = json.dumps({
            "room_id":   room_id,
            "round_id":  round_id,
            "note_id":   note_id,
            "audio_url": audio_url,
        }).encode()

        await self._exchange.publish(
            aio_pika.Message(body=body, content_type="application/json"),
            routing_key="audio.note.ready",
        )
