package room

import "testing"

func TestRoomHub_Broadcast_SendsToAllClientsInRoom(t *testing.T) {
	hub := NewRoomHub()

	client1 := &Client{Send: make(chan []byte, 1)}
	client2 := &Client{Send: make(chan []byte, 1)}
	hub.Register("ABC123", client1)
	hub.Register("ABC123", client2)

	hub.Broadcast("ABC123", []byte(`{"type":"test"}`))

	for _, c := range []*Client{client1, client2} {
		select {
		case msg := <-c.Send:
			if string(msg) != `{"type":"test"}` {
				t.Errorf("got %s, want {\"type\":\"test\"}", msg)
			}
		default:
			t.Error("client did not receive message")
		}
	}
}

func TestRoomHub_Unregister_RemovesClient(t *testing.T) {
	hub := NewRoomHub()

	client := &Client{Send: make(chan []byte, 1)}
	hub.Register("ABC123", client)
	hub.Unregister("ABC123", client)

	hub.Broadcast("ABC123", []byte(`{"type":"test"}`))

	select {
	case <-client.Send:
		t.Error("unregistered client should not receive messages")
	default:
	}
}
