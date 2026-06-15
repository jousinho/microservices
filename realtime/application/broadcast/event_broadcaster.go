package broadcast

import (
	"encoding/json"
	"log"
	"realtime/domain/room"
)

type EventMessage struct {
	Type    string          `json:"type"`
	Payload json.RawMessage `json:"payload"`
}

type EventBroadcaster struct {
	hub *room.RoomHub
}

func NewEventBroadcaster(hub *room.RoomHub) *EventBroadcaster {
	return &EventBroadcaster{hub: hub}
}

func (b *EventBroadcaster) Handle(roomCode string, eventType string, payload []byte) {
	msg := EventMessage{Type: eventType, Payload: json.RawMessage(payload)}
	data, err := json.Marshal(msg)
	if err != nil {
		log.Printf("failed to marshal event: %v", err)
		return
	}
	b.hub.Broadcast(roomCode, data)
}
