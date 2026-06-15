FROM golang:1.22-alpine AS builder

WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY . .
RUN CGO_ENABLED=0 GOOS=linux go build -o realtime .

FROM alpine:latest

WORKDIR /app
COPY --from=builder /app/realtime .

EXPOSE 8000
CMD ["./realtime"]
