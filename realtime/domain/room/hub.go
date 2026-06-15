package room

import "sync"

type Client struct {
	Send chan []byte
}

type RoomHub struct {
	mu    sync.RWMutex
	rooms map[string][]*Client
}

func NewRoomHub() *RoomHub {
	return &RoomHub{rooms: make(map[string][]*Client)}
}

func (h *RoomHub) Register(roomCode string, client *Client) {
	h.mu.Lock()
	defer h.mu.Unlock()
	h.rooms[roomCode] = append(h.rooms[roomCode], client)
}

func (h *RoomHub) Unregister(roomCode string, client *Client) {
	h.mu.Lock()
	defer h.mu.Unlock()
	clients := h.rooms[roomCode]
	for i, c := range clients {
		if c == client {
			h.rooms[roomCode] = append(clients[:i], clients[i+1:]...)
			break
		}
	}
	if len(h.rooms[roomCode]) == 0 {
		delete(h.rooms, roomCode)
	}
}

func (h *RoomHub) Broadcast(roomCode string, message []byte) {
	h.mu.RLock()
	defer h.mu.RUnlock()
	for _, client := range h.rooms[roomCode] {
		select {
		case client.Send <- message:
		default:
		}
	}
}
