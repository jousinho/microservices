package rabbitmq

import (
	"context"
	"encoding/json"
	"log"
	"realtime/application/broadcast"

	amqp "github.com/rabbitmq/amqp091-go"
)

type Consumer struct {
	url         string
	broadcaster *broadcast.EventBroadcaster
}

func NewConsumer(url string, broadcaster *broadcast.EventBroadcaster) *Consumer {
	return &Consumer{url: url, broadcaster: broadcaster}
}

type eventPayload struct {
	RoomID string `json:"room_id"`
}

func (c *Consumer) Start(ctx context.Context) error {
	conn, err := amqp.Dial(c.url)
	if err != nil {
		return err
	}
	defer conn.Close()

	ch, err := conn.Channel()
	if err != nil {
		return err
	}
	defer ch.Close()

	if err := ch.ExchangeDeclare("game_events", "topic", true, false, false, false, nil); err != nil {
		return err
	}

	q, err := ch.QueueDeclare("realtime.game_events", true, false, false, false, nil)
	if err != nil {
		return err
	}

	for _, key := range []string{"game.*", "audio.*"} {
		if err := ch.QueueBind(q.Name, key, "game_events", false, nil); err != nil {
			return err
		}
	}

	msgs, err := ch.Consume(q.Name, "", true, false, false, false, nil)
	if err != nil {
		return err
	}

	log.Println("RabbitMQ consumer started, waiting for events...")

	for {
		select {
		case <-ctx.Done():
			return nil
		case msg, ok := <-msgs:
			if !ok {
				return nil
			}
			var p eventPayload
			if err := json.Unmarshal(msg.Body, &p); err != nil || p.RoomID == "" {
				log.Printf("skipping malformed message: %s", msg.Body)
				continue
			}
			c.broadcaster.Handle(p.RoomID, msg.RoutingKey, msg.Body)
		}
	}
}
