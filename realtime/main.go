package main

import (
	"context"
	"log"
	"os"
	"realtime/application/broadcast"
	"realtime/domain/room"
	rabbitinfra "realtime/infrastructure/rabbitmq"
	wsinfra "realtime/infrastructure/websocket"

	"github.com/gin-gonic/gin"
)

func main() {
	rabbitmqURL := os.Getenv("RABBITMQ_URL")
	if rabbitmqURL == "" {
		rabbitmqURL = "amqp://guest:guest@localhost:5672"
	}

	hub := room.NewRoomHub()
	broadcaster := broadcast.NewEventBroadcaster(hub)
	consumer := rabbitinfra.NewConsumer(rabbitmqURL, broadcaster)
	wsHandler := wsinfra.NewHandler(hub)

	go func() {
		if err := consumer.Start(context.Background()); err != nil {
			log.Printf("RabbitMQ consumer stopped: %v", err)
		}
	}()

	r := gin.Default()
	r.GET("/ws/rooms/:code", wsHandler.HandleWS)
	r.GET("/health", func(c *gin.Context) {
		c.JSON(200, gin.H{"status": "ok"})
	})

	if err := r.Run(":8000"); err != nil {
		log.Fatal(err)
	}
}
