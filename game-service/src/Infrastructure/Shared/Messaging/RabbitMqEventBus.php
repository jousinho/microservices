<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Messaging;

use App\Application\Game\Message\GameEndedMessage;
use App\Application\Game\Message\RoundEndedMessage;
use App\Application\Game\Message\RoundStartedMessage;
use App\Domain\Game\Repository\EventBusInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMqEventBus implements EventBusInterface
{
    private const EXCHANGE = 'game_events';

    public function __construct(private readonly string $rabbitmqUrl) {}

    public function publish(object ...$events): void
    {
        if (empty($events)) {
            return;
        }

        $connection = $this->connect();
        $channel    = $connection->channel();

        $channel->exchange_declare(self::EXCHANGE, 'topic', false, true, false);

        foreach ($events as $event) {
            $routingKey = $this->routingKeyFor($event);
            $payload    = json_encode($this->serialize($event));

            $channel->basic_publish(
                new AMQPMessage($payload, ['delivery_mode' => 2]),
                self::EXCHANGE,
                $routingKey,
            );
        }

        $channel->close();
        $connection->close();
    }

    private function routingKeyFor(object $event): string
    {
        return match($event::class) {
            RoundStartedMessage::class => 'game.round.started',
            RoundEndedMessage::class   => 'game.round.ended',
            GameEndedMessage::class    => 'game.game.ended',
            default => throw new \InvalidArgumentException(
                sprintf('No routing key defined for event %s', $event::class)
            ),
        };
    }

    private function serialize(object $event): array
    {
        $data = [];
        $reflection = new \ReflectionClass($event);

        foreach ($reflection->getProperties() as $property) {
            $data[$property->getName()] = $property->getValue($event);
        }

        return $data;
    }

    private function connect(): AMQPStreamConnection
    {
        $parsed = parse_url($this->rabbitmqUrl);

        return new AMQPStreamConnection(
            $parsed['host'],
            $parsed['port'] ?? 5672,
            $parsed['user'],
            $parsed['pass'],
            ltrim($parsed['path'] ?? '/', '/') ?: '/',
        );
    }
}
