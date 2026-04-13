# Guess the Note — Planning

## Contexto

Juego musical. El jugador escucha una nota musical generada dinámicamente y tiene que identificarla (do, re, mi, fa, sol, la, si). La dificultad aumenta por octava y tipo de sonido (nota pura → armónicos → acorde).

**V1.0** → un jugador, sin tiempo real, sin broker  
**V2.0** → multijugador, WebSockets, RabbitMQ

---

## Stack técnico

| Capa | V1.0 | V2.0 |
|------|-------|-------|
| game-service | PHP 8.3 / Symfony 7 / Doctrine ORM | igual |
| audio-brain | Python 3.12 / FastAPI / numpy / scipy | igual |
| realtime | — | Go 1.22 / Gin / gorilla/websocket |
| frontend | Vue.js 3 / Vite / Pinia | igual |
| Broker | — | RabbitMQ 3 (exchange topic `game_events`) |
| Base de datos | MySQL 8 (solo game-service) | igual |
| Orquestación | Docker Compose | igual |
| Tests PHP | PHPUnit | igual |
| Tests Python | pytest | igual |
| Tests Go | — | testing (stdlib) |
| CI/CD | GitHub Actions | igual |

---

## Arquitectura

**DDD + Hexagonal (Ports & Adapters)** en los 2 servicios backend.

```
Domain/      → entidades, interfaces de repositorio, value objects, domain events
Application/ → servicios de aplicación (orquestan dominio + infraestructura)
Infrastructure → repos, controllers, clientes HTTP
```

La regla de dependencia es estricta: `Domain ← Application ← Infrastructure`. El dominio no importa ningún framework ni librería externa.

---

## Bounded Contexts

- **Game** (game-service) → sesiones de juego, rondas, respuestas, puntuaciones
- **Note** (audio-brain) → catálogo de notas musicales, generación de audio, dificultad
- **Realtime** (realtime) → (V2.0) conexiones WebSocket, broadcast de eventos

---

## Flujo del juego — V1.0

```
1. Jugador inicia partida     → POST /api/sessions
2. game-service llama         → GET  audio-brain /api/notes/random?difficulty=1
3. audio-brain responde       → { note_id, audio_url, difficulty }
4. game-service guarda nota   → en la ronda activa
5. Frontend reproduce audio   → GET audio-brain /api/notes/{note_id}/audio
6. Jugador responde           → POST /api/rounds/{id}/answer
7. game-service valida        → devuelve { is_correct, correct_note, score }
8. Al acabar todas las rondas → GET /api/sessions/{id}/scoreboard
```

La comunicación entre game-service y audio-brain es **HTTP directa**. Sin broker.

---

## Modelo de datos — V1.0

### game-service (MySQL `game_db`)

**`sessions`**: id (UUID), status (active/ended), difficulty (1-3), total_rounds, current_round, score, created_at  
**`rounds`**: id (UUID), session_id, round_number, note_id, correct_note, status (active/ended), started_at, ended_at  
**`answers`**: id (UUID), round_id, guess, is_correct, response_time_ms, submitted_at

### audio-brain (sin base de datos)

El catálogo de notas son constantes de dominio. No requiere persistencia.

**Catálogo en memoria:**
- 7 notas: do, re, mi, fa, sol, la, si
- 3 octavas por nota: 3, 4, 5 → 21 combinaciones
- Archivos `.wav` pre-generados al arrancar el servicio en `/app/audio_cache/`

**Niveles de dificultad:**
- Nivel 1 → nota pura, octava 4 (sinusoide limpia)
- Nivel 2 → nota con armónicos, octavas 3-5 (timbre más complejo, registro variable)
- Nivel 3 → acorde de 3 notas (do+mi+sol), hay que identificar la raíz

---

## API REST — V1.0

### game-service (puerto 8001)

```
POST   /api/sessions                     Iniciar partida (difficulty, total_rounds)
GET    /api/sessions/{id}                Estado de la partida
POST   /api/sessions/{id}/next-round     Avanzar a la siguiente ronda
POST   /api/rounds/{id}/answer           Enviar respuesta
GET    /api/sessions/{id}/scoreboard     Resultado final
```

### audio-brain (puerto 8002)

```
GET    /api/notes                        Listar catálogo completo
GET    /api/notes/random?difficulty=N    Seleccionar nota aleatoria por dificultad
GET    /api/notes/{note_id}/audio        Servir archivo .wav
```

---

## Infraestructura Docker — V1.0

```
nginx          → servidor HTTP (8001) — proxy inverso a php-fpm
php-fpm        → PHP-FPM, solo accesible internamente via FastCGI
php-cli        → PHP-CLI para migraciones y comandos Symfony
phpunit        → PHP-CLI con APP_ENV=test, apunta a mysql-test
mysql-game     → MySQL 8 (3307) — base de datos principal
mysql-test     → MySQL 8 (3308) — base de datos de tests, en tmpfs
audio-brain    → uvicorn (8002) — genera WAVs al arrancar, sin BD
frontend       → vite dev server (3000)
```

Sin RabbitMQ. Sin contenedor realtime. Sin mysql-audio.

---

## Fases de implementación — V1.0

### Fase 1 — Infraestructura base
Docker Compose V1 con healthchecks, redes, volúmenes.

### Fase 2 — audio-brain
Catálogo de notas en memoria, generación WAV con numpy/scipy al arranque, API REST.

### Fase 3 — game-service
Entidades Session/Round/Answer, controllers REST, cliente HTTP a audio-brain.

### Fase 4 — Frontend Vue.js
Flujo completo: iniciar partida → escuchar nota → responder → scoreboard.

### Fase 5 — CI/CD
GitHub Actions: lint + tests unitarios + tests de integración por servicio.

---

## V2.0 — Multijugador

### Principio de diseño

V1 no se reescribe. Se añaden adapters de infraestructura (RabbitMQ publisher/consumer) sin tocar Domain ni Application. El modo single-player sigue funcionando igual.

---

### Nuevo bounded context: Room

`Room` es un nuevo Aggregate Root en game-service. Representa una sala multijugador con código de invitación, jugadores y estado de la partida. Coexiste con `Session` (single-player).

**Modelo de datos — nuevas tablas en `game_db`:**

```
rooms
  id           UUID PK
  code         VARCHAR(6) UNIQUE   ← código de invitación (ej: "ABC123")
  host_id      UUID                ← referencia al player que creó la sala
  status       ENUM(waiting, playing, ended)
  difficulty   TINYINT             ← 1 | 2 | 3
  total_rounds TINYINT
  current_round TINYINT DEFAULT 0
  created_at   DATETIME

players
  id           UUID PK
  room_id      UUID FK → rooms.id
  name         VARCHAR(50)
  score        INT DEFAULT 0
  joined_at    DATETIME

room_rounds
  id           UUID PK
  room_id      UUID FK → rooms.id
  round_number TINYINT
  note_id      VARCHAR(10)         ← se rellena cuando audio-brain confirma la nota
  correct_note VARCHAR(5)
  status       ENUM(waiting, active, ended)
  started_at   DATETIME
  ended_at     DATETIME NULL

room_answers
  id           UUID PK
  round_id     UUID FK → room_rounds.id
  player_id    UUID FK → players.id
  guess        VARCHAR(5)
  is_correct   TINYINT(1)
  submitted_at DATETIME
```

Cada ronda es **compartida** — todos los jugadores escuchan la misma nota. Cada jugador tiene su propia `room_answer`. La ronda termina cuando todos han respondido (o cuando expira el tiempo, en V2.1+).

---

### Flujo completo de una partida multijugador

```
1. Host crea sala         POST /api/rooms                  → { room_id, code: "ABC123" }
2. Jugadores se unen      POST /api/rooms/{code}/join       → { player_id, room_id }
3. Todos se conectan      WS  ws://localhost:8003/rooms/{code}
4. Host inicia partida    POST /api/rooms/{id}/start

5. game-service publica   → RabbitMQ: game.round.started   { room_id, round_id, difficulty }
6. audio-brain consume    ← game.round.started
   audio-brain publica    → RabbitMQ: audio.note.ready     { room_id, round_id, note_id, audio_url }
7. realtime consume       ← audio.note.ready
   realtime broadcast     → WebSocket a todos en la sala:  { type: "note.ready", payload: {...} }

8. Cada jugador responde  POST /api/rooms/{id}/rounds/{id}/answer   { guess: "do" }
9. game-service valida, actualiza score, publica:
   → game.answer.submitted { room_id, player_id, answered_count, total_players }
   realtime broadcast → { type: "answer.submitted", payload: { player, answered: true } }

10. Cuando todos respondieron:
    game-service publica → game.round.ended { room_id, correct_note, scores: {...} }
    realtime broadcast  → { type: "round.ended", payload: {...} }

11. Si era la última ronda:
    game-service publica → game.ended { room_id, winner, final_scores: {...} }
    realtime broadcast  → { type: "game.ended", payload: {...} }
```

---

### Topología RabbitMQ

```
Exchange: game_events (type: topic, durable: true)

Queue: audio_brain.game_events
  bindings: game.round.started
  consumer: audio-brain

Queue: realtime.game_events
  bindings: game.*, audio.*
  consumer: realtime

Dead Letter Exchange: game_events.dlx (type: direct)
Queue: game_events.dead_letter
  ← mensajes que fallan tras N reintentos
```

Routing keys publicados por cada servicio:
- game-service → `game.round.started`, `game.answer.submitted`, `game.round.ended`, `game.ended`
- audio-brain  → `audio.note.ready`

---

### Servicio realtime (Go)

Responsabilidad única: **recibir eventos de RabbitMQ y hacer broadcast a los WebSocket clients de la sala correspondiente**. No recibe comandos del frontend. No tiene lógica de negocio.

```
realtime/
├── main.go
├── domain/room/
│   └── hub.go                  # RoomHub: map[roomCode]→[]WebSocket clients
├── application/broadcast/
│   └── event_broadcaster.go    # consume RabbitMQ → busca hub → broadcast
└── infrastructure/
    ├── websocket/
    │   └── handler.go          # HTTP upgrade → WebSocket, registra client en hub
    └── rabbitmq/
        └── consumer.go         # AMQP consumer, parsea mensajes, llama a broadcaster
```

**Mensajes WebSocket (server → client únicamente):**

```json
{ "type": "player.joined",    "payload": { "name": "Ana", "players": ["Ana","Bob"] } }
{ "type": "round.started",    "payload": { "round_number": 1, "total_rounds": 5 } }
{ "type": "note.ready",       "payload": { "note_id": "do_4", "audio_url": "/api/notes/do_4/audio?difficulty=1" } }
{ "type": "answer.submitted", "payload": { "player": "Bob", "answered_count": 1, "total": 2 } }
{ "type": "round.ended",      "payload": { "correct_note": "do", "scores": {"Ana": 3, "Bob": 2} } }
{ "type": "game.ended",       "payload": { "winner": "Ana", "final_scores": {"Ana": 5, "Bob": 3} } }
```

El frontend sigue usando HTTP para los comandos (crear sala, unirse, responder). El WebSocket es canal de broadcast unidireccional (servidor → clientes).

---

### Cambios en servicios existentes

**game-service** — se añade sin tocar lo existente:
- Nueva entidad `Room` con `Player`, `RoomRound`, `RoomAnswer`
- `RabbitMqEventBus` implementa `EventBusInterface` (nuevo puerto en Domain)
- Application Services de Room publican al bus en vez de devolver eventos directamente
- Nuevos controllers: `RoomController`

**audio-brain** — se añade sin tocar lo existente:
- Consumer RabbitMQ que escucha `game.round.started`
- Al consumir: selecciona nota según difficulty, publica `audio.note.ready`
- La generación de audio (dominio) no cambia en absoluto

**Frontend** — se añade sin tocar lo existente:
- Nueva vista `LobbyView` (crear/unirse a sala)
- Nueva vista `MultiplayerGameView` (igual que GameView pero con lista de jugadores y WebSocket)
- Composable `useWebSocket` para gestionar la conexión WS
