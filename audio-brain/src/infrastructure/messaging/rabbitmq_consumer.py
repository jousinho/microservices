import asyncio
import logging
import os

import aio_pika

from src.infrastructure.dependencies import get_note_service
from src.infrastructure.messaging.round_started_handler import RoundStartedHandler

logger = logging.getLogger(__name__)

RABBITMQ_URL   = os.getenv("RABBITMQ_URL", "amqp://guest:guest@rabbitmq:5672")
EXCHANGE_NAME  = "game_events"
QUEUE_NAME     = "audio_brain.game_events"
ROUTING_KEY    = "game.round.started"


async def start_rabbitmq_consumer() -> None:
    while True:
        try:
            await _run_consumer()
        except Exception as e:
            logger.error("RabbitMQ consumer error: %s — retrying in 5s", e)
            await asyncio.sleep(5)


async def _run_consumer() -> None:
    connection = await aio_pika.connect_robust(RABBITMQ_URL)

    async with connection:
        channel  = await connection.channel()
        exchange = await channel.declare_exchange(EXCHANGE_NAME, aio_pika.ExchangeType.TOPIC, durable=True)
        queue    = await channel.declare_queue(QUEUE_NAME, durable=True)

        await queue.bind(exchange, routing_key=ROUTING_KEY)

        handler = RoundStartedHandler(note_service=get_note_service(), exchange=exchange)

        await queue.consume(handler.handle)

        logger.info("RabbitMQ consumer listening on %s → %s", QUEUE_NAME, ROUTING_KEY)

        await asyncio.Future()
