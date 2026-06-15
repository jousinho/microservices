package websocket

import (
	"net/http"
	"realtime/domain/room"

	"github.com/gin-gonic/gin"
	gorillaws "github.com/gorilla/websocket"
)

var upgrader = gorillaws.Upgrader{
	CheckOrigin: func(r *http.Request) bool { return true },
}

type Handler struct {
	hub *room.RoomHub
}

func NewHandler(hub *room.RoomHub) *Handler {
	return &Handler{hub: hub}
}

func (h *Handler) HandleWS(c *gin.Context) {
	roomCode := c.Param("code")

	conn, err := upgrader.Upgrade(c.Writer, c.Request, nil)
	if err != nil {
		return
	}

	client := &room.Client{Send: make(chan []byte, 256)}
	h.hub.Register(roomCode, client)

	go h.writePump(conn, client)
	h.readPump(conn, roomCode, client)
}

func (h *Handler) readPump(conn *gorillaws.Conn, roomCode string, client *room.Client) {
	defer func() {
		h.hub.Unregister(roomCode, client)
		close(client.Send)
		conn.Close()
	}()
	for {
		if _, _, err := conn.ReadMessage(); err != nil {
			return
		}
	}
}

func (h *Handler) writePump(conn *gorillaws.Conn, client *room.Client) {
	defer conn.Close()
	for msg := range client.Send {
		if err := conn.WriteMessage(gorillaws.TextMessage, msg); err != nil {
			return
		}
	}
}
