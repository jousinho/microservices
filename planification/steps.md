# Guess the Note — Steps de implementación

---

# V1.0 — Un jugador

---

## STEP 1 — Docker Compose V1

Ficheros a crear:

```
microservices/
├── docker-compose.yml
├── .env
├── .env.example
├── game-service/
│   ├── Dockerfile
│   └── nginx.conf
├── audio-brain/
│   └── Dockerfile
└── frontend/
    └── Dockerfile
```

Servicios en docker-compose.yml:
- `mysql-game` — MySQL 8, healthcheck, puerto 3307, volumen persistente
- `mysql-test` — MySQL 8, healthcheck, puerto 3308, tmpfs (sin persistencia, para tests)
- `nginx` — nginx:alpine, puerto 8001, proxy inverso a php-fpm via FastCGI
- `php-fpm` — PHP-FPM, sin puerto expuesto al host, solo accesible internamente
- `php-cli` — mismo Dockerfile target cli, para migraciones y comandos Symfony
- `phpunit` — mismo Dockerfile target cli, APP_ENV=test, apunta a mysql-test
- `audio-brain` — uvicorn, sin dependencias de BD, puerto 8002
- `frontend` — vite dev server, puerto 3000

Red interna compartida: `game_network`.

Sin RabbitMQ. Sin contenedor realtime. Sin mysql-audio.

Verificación: `docker compose up -d` levanta los servicios sin errores.
Comprobación: `curl -I http://localhost:8001` debe devolver headers `Server: nginx` y `X-Powered-By: PHP`.

---

## STEP 2 — audio-brain: dominio + generación de audio

> Bounded context: `Note`

Sin base de datos. El catálogo de notas son constantes de dominio. Los archivos `.wav` se generan con `numpy` + `scipy` al arrancar el servicio.

Ficheros:

```
audio-brain/src/
├── domain/note/
│   ├── entity/
│   │   └── musical_note.py           # dataclass(frozen=True): name, solfege, frequency, octave
│   ├── repository/
│   │   └── note_generator_interface.py  # Protocol: genera y persiste WAV dada una nota
│   ├── value_object/
│   │   └── difficulty.py             # 1=pura octava4, 2=armónicos octavas3-5, 3=acorde
│   └── catalog.py                    # constantes: 7 notas × 3 octavas
├── application/note/service/
│   └── get_random_note_service.py    # selecciona nota aleatoria según dificultad
└── infrastructure/
    ├── note/
    │   ├── http/routers/
    │   │   └── note_router.py
    │   └── audio/
    │       └── numpy_note_generator.py  # genera onda senoidal + armónicos + envelope → WAV
    └── shared/
        └── audio_cache/              # directorio donde se guardan los .wav pre-generados
```

Al arrancar FastAPI (`startup`): `NumpyNoteGenerator` pre-genera los 21 archivos WAV en `/app/audio_cache/`. Peticiones posteriores leen el archivo ya existente.

Tests:
```
tests/unit/domain/note/test_musical_note.py
tests/unit/domain/note/test_difficulty.py
tests/unit/application/note/test_get_random_note_service.py
```

Casos:
- `test_creating_musical_note__with_negative_frequency__should_raise_error`
- `test_creating_musical_note__with_valid_data__should_store_solfege_and_octave`
- `test_difficulty__with_value_above_3__should_raise_error`
- `test_difficulty__with_value_below_1__should_raise_error`
- `test_get_random_note__with_difficulty_1__should_return_octave_4_note`
- `test_get_random_note__with_difficulty_3__should_return_chord_note`

---

## STEP 3 — audio-brain: API REST

Endpoints:
```
GET  /api/notes                        → listar catálogo completo (21 notas)
GET  /api/notes/random?difficulty=N    → nota aleatoria por dificultad
GET  /api/notes/{note_id}/audio        → servir archivo .wav (StreamingResponse)
```

Tests:
```
tests/functional/test_note_router.py
```

Casos:
- `test_listing_notes__should_return_21_notes`
- `test_getting_random_note__with_difficulty_1__should_return_octave_4_note`
- `test_getting_random_note__with_difficulty_above_3__should_return_422`
- `test_getting_note_audio__with_valid_id__should_return_wav_content_type`
- `test_getting_note_audio__with_invalid_id__should_return_404`

---

## STEP 4 — game-service: entidades de dominio

> Bounded context: `Game`

Ficheros:

```
game-service/src/
├── Domain/Game/
│   ├── Entity/
│   │   ├── Session.php          # Aggregate Root
│   │   ├── Round.php
│   │   └── Answer.php
│   ├── Repository/
│   │   └── SessionRepositoryInterface.php
│   ├── ValueObject/
│   │   ├── SessionStatus.php    # active | ended
│   │   ├── Difficulty.php       # 1 | 2 | 3
│   │   ├── NoteId.php           # UUID de la nota (viene de audio-brain)
│   │   └── Score.php
│   └── Event/
│       ├── SessionWasStarted.php
│       ├── RoundWasStarted.php
│       ├── AnswerWasSubmitted.php
│       └── SessionWasEnded.php
```

Convenciones:
- Constructor privado + `Session::create(...): self`
- Getters sin prefijo `get`: `$session->status()`, `$session->score()`
- `declare(strict_types=1)` en todos los ficheros

Tests:
```
tests/Unit/Domain/Game/Entity/SessionTest.php
tests/Unit/Domain/Game/Entity/RoundTest.php
tests/Unit/Domain/Game/ValueObject/DifficultyTest.php
tests/Unit/Domain/Game/ValueObject/ScoreTest.php
```

Casos:
- `test_creating_session__with_valid_data__should_have_active_status`
- `test_creating_session__should_emit_SessionWasStarted_domain_event`
- `test_ending_session__when_already_ended__should_raise_exception`
- `test_difficulty__with_value_above_3__should_raise_exception`
- `test_submitting_answer__correct__should_increment_score`

---

## STEP 5 — game-service: repositorios Doctrine + migraciones

Ficheros:

```
game-service/src/Infrastructure/
├── Game/Persistence/Doctrine/
│   └── DoctrineSessionRepository.php
└── Shared/Persistence/Doctrine/Migrations/
```

Binding en `config/services.yaml`:
```yaml
App\Domain\Game\Repository\SessionRepositoryInterface:
    class: App\Infrastructure\Game\Persistence\Doctrine\DoctrineSessionRepository
```

Migraciones:
```
docker compose exec game-cli php bin/console doctrine:migrations:diff
docker compose exec game-cli php bin/console doctrine:migrations:migrate
```

Tests:
```
tests/Integration/Infrastructure/Game/DoctrineSessionRepositoryTest.php
```

Casos:
- `test_saving_session__should_be_retrievable_by_id`
- `test_finding_session__when_not_exists__should_return_null`
- `test_saving_session__with_rounds__should_persist_all_rounds`

---

## STEP 6 — game-service: Application Services + controllers REST

Ficheros:

```
game-service/src/
├── Application/Game/Service/
│   ├── StartSessionService.php      # crea sesión, llama a audio-brain por HTTP
│   ├── NextRoundService.php         # avanza ronda, pide nueva nota a audio-brain
│   └── SubmitAnswerService.php      # valida respuesta, actualiza score
└── Infrastructure/
    ├── Game/Http/Controller/
    │   ├── SessionController.php
    │   └── RoundController.php
    └── Note/Http/
        └── AudioBrainHttpClient.php  # cliente HTTP a audio-brain (implementa NoteClientInterface)
```

`AudioBrainHttpClient` llama a `GET audio-brain/api/notes/random?difficulty=N` cuando se necesita una nota nueva. Es el único punto de acoplamiento entre servicios en V1.

Rutas en `config/routes.yaml`:
```yaml
game_controllers:
    resource:
        path: ../src/Infrastructure/Game/Http/Controller/
        namespace: App\Infrastructure\Game\Http\Controller
    type: attribute
```

Tests:
```
tests/Functional/SessionControllerTest.php
tests/Functional/RoundControllerTest.php
```

Casos:
- `test_starting_session__should_return_201_with_session_id`
- `test_starting_session__with_invalid_difficulty__should_return_422`
- `test_next_round__when_session_ended__should_return_409`
- `test_submitting_answer__with_correct_note__should_return_is_correct_true`
- `test_submitting_answer__with_wrong_note__should_return_is_correct_false`
- `test_scoreboard__after_all_rounds__should_return_final_score`

---

## STEP 7 — Frontend Vue.js 3

Vistas:
- `HomeView` — elegir dificultad y número de rondas, botón "Empezar"
- `GameView` — botón reproducir nota, opciones do/re/mi/fa/sol/la/si, timer, score parcial
- `ScoreboardView` — puntuación final, opción de reiniciar

Composables:
- `useGameAPI` — wrapper de fetch para la REST API de game-service
- `useAudioPlayer` — reproduce el `.wav` recibido de audio-brain via Web Audio API

State management con Pinia: `useGameStore` (sesión activa, ronda actual, score).

---

## STEP 8 — CI/CD GitHub Actions V1

Ficheros:
```
.github/workflows/
├── game-service.yml    → lint (phpstan) + phpunit
└── audio-brain.yml     → lint (ruff) + pytest
```

Ambos corren en paralelo al hacer push a cualquier rama.

---

# V2.0 — Multijugador

---

## STEP V2.1 — RabbitMQ en Docker Compose

Añadir al `docker-compose.yml`:

```yaml
rabbitmq:
  image: rabbitmq:3.13-management-alpine
  ports:
    - "5672:5672"    # AMQP
    - "15672:15672"  # Management UI
  environment:
    RABBITMQ_DEFAULT_USER: guest
    RABBITMQ_DEFAULT_PASS: guest
  healthcheck:
    test: ["CMD", "rabbitmq-diagnostics", "ping"]
    interval: 10s
    timeout: 5s
    retries: 5
  networks:
    - game_network
```

Añadir al `.env` y `.env.example`:
```
RABBITMQ_URL=amqp://guest:guest@rabbitmq:5672
RABBITMQ_PORT=5672
RABBITMQ_MANAGEMENT_PORT=15672
```

Los servicios que dependen de RabbitMQ (`php-fpm`, `audio-brain`, `realtime`) añaden `depends_on: rabbitmq: condition: service_healthy`.

Verificación: `http://localhost:15672` abre la Management UI de RabbitMQ.

---

## STEP V2.2 — game-service: dominio Room

> Bounded context: `Game` — nuevo sub-contexto `Room`

Ficheros nuevos (sin tocar nada de Session/Round/Answer):

```
game-service/src/Domain/Game/
├── Entity/
│   ├── Room.php           # Aggregate Root
│   ├── Player.php         # Entity dentro de Room
│   ├── RoomRound.php      # Entity: ronda compartida por todos los jugadores
│   └── RoomAnswer.php     # Entity: respuesta de un jugador a una ronda
├── Repository/
│   ├── RoomRepositoryInterface.php
│   └── EventBusInterface.php    # nuevo puerto: publicar eventos de integración
├── ValueObject/
│   ├── RoomCode.php       # 6 caracteres alfanuméricos generados aleatoriamente
│   └── RoomStatus.php     # waiting | playing | ended
└── Event/
    ├── RoomWasCreated.php
    ├── RoundWasStarted.php       # (nuevo, diferente al de Session)
    ├── RoomAnswerWasSubmitted.php
    ├── RoomRoundWasEnded.php
    └── RoomGameWasEnded.php
```

`Room::create(difficulty, totalRounds): self` — genera `RoomCode` aleatorio, crea el host como primer `Player`.

`Room::join(playerName): Player` — añade jugador, emite `PlayerJoined`.

`Room::startGame(): void` — transiciona a `playing`, emite `RoundWasStarted`.

`Room::submitAnswer(playerId, roundId, guess): void` — crea `RoomAnswer`, actualiza score del `Player`, comprueba si todos respondieron → si sí, emite `RoomRoundWasEnded` (y `RoomGameWasEnded` si era la última).

`EventBusInterface` es un puerto de dominio:
```php
interface EventBusInterface {
    public function publish(DomainEvent ...$events): void;
}
```

Tests:
```
tests/Unit/Domain/Game/Entity/RoomTest.php
tests/Unit/Domain/Game/ValueObject/RoomCodeTest.php
```

Casos:
- `test_creating_room__should_generate_six_char_code`
- `test_joining_room__when_playing__should_raise_exception`
- `test_submitting_answer__when_all_players_answered__should_emit_round_ended_event`
- `test_room_code__with_less_than_six_chars__should_raise_exception`

---

## STEP V2.3 — game-service: infraestructura Room + RabbitMQ publisher

Ficheros nuevos:

```
game-service/src/
├── Application/Game/Service/
│   ├── CreateRoomService.php
│   ├── JoinRoomService.php
│   ├── StartRoomGameService.php
│   └── SubmitRoomAnswerService.php
├── Infrastructure/
│   ├── Game/
│   │   ├── Persistence/Doctrine/
│   │   │   └── DoctrineRoomRepository.php
│   │   └── Http/Controller/
│   │       └── RoomController.php
│   └── Shared/
│       ├── Messaging/
│       │   └── RabbitMqEventBus.php    # implementa EventBusInterface
│       └── Persistence/Doctrine/Migrations/
│           └── (nueva migración: rooms, players, room_rounds, room_answers)
```

`RabbitMqEventBus` usa el componente `symfony/amqp-messenger` o la librería `php-amqplib/php-amqplib` para publicar al exchange `game_events` con el routing key correspondiente al tipo de evento.

API REST nuevos endpoints:
```
POST  /api/rooms                          Crear sala (difficulty, total_rounds)
POST  /api/rooms/{code}/join              Unirse a sala (player_name)
POST  /api/rooms/{id}/start               Iniciar partida (solo host)
POST  /api/rooms/{id}/rounds/{id}/answer  Enviar respuesta (player_id, guess)
GET   /api/rooms/{id}                     Estado de la sala
```

Binding en `services.yaml`:
```yaml
App\Domain\Game\Repository\RoomRepositoryInterface:
    class: App\Infrastructure\Game\Persistence\Doctrine\DoctrineRoomRepository
App\Domain\Game\Repository\EventBusInterface:
    class: App\Infrastructure\Shared\Messaging\RabbitMqEventBus
    arguments:
        $rabbitmqUrl: '%env(RABBITMQ_URL)%'
```

Tests:
```
tests/Integration/Infrastructure/Game/DoctrineRoomRepositoryTest.php
tests/Functional/RoomControllerTest.php
```

Casos integration:
- `test_saving_room__should_be_retrievable_by_code`
- `test_saving_room__with_players__should_persist_all_players`

Casos functional:
- `test_creating_room__should_return_201_with_room_code`
- `test_joining_room__should_return_player_id`
- `test_starting_room_game__when_not_host__should_return_403`
- `test_submitting_room_answer__should_return_is_correct`

---

## STEP V2.4 — audio-brain: consumer RabbitMQ

Sin tocar el dominio ni la API REST existente. Solo infraestructura nueva:

```
audio-brain/src/infrastructure/
└── messaging/
    ├── rabbitmq_consumer.py      # AMQP consumer con aio-pika
    └── round_started_handler.py  # consume game.round.started → publica audio.note.ready
```

Flujo del handler:
1. Recibe `{ room_id, round_id, difficulty }`
2. Llama a `GetRandomNoteService.execute(difficulty)` — reutiliza el dominio existente
3. Publica `audio.note.ready` con `{ room_id, round_id, note_id, audio_url }`

El consumer arranca como tarea async junto con uvicorn en el `lifespan` de FastAPI:

```python
@asynccontextmanager
async def lifespan(app: FastAPI):
    _pregenerate_audio_cache()
    asyncio.create_task(start_rabbitmq_consumer())   # nuevo
    yield
```

Añadir a `requirements.txt`: `aio-pika==9.4.1`

Tests:
```
tests/unit/infrastructure/messaging/test_round_started_handler.py
```

Caso:
- `test_handling_round_started__should_publish_note_ready_with_correct_note_id`

---

## STEP V2.5 — servicio realtime (Go)

Servicio nuevo desde cero.

```
realtime/
├── Dockerfile
├── go.mod
├── go.sum
├── main.go
├── domain/room/
│   └── hub.go                      # RoomHub: gestiona clients WebSocket por sala
├── application/broadcast/
│   └── event_broadcaster.go        # recibe evento → busca hub → broadcast JSON
└── infrastructure/
    ├── websocket/
    │   └── handler.go              # HTTP handler: upgrade a WebSocket, registra client
    └── rabbitmq/
        └── consumer.go             # AMQP consumer, parsea mensajes, llama a broadcaster
```

**`RoomHub`** (domain):
```go
type Client struct {
    conn *websocket.Conn
    send chan []byte
}

type RoomHub struct {
    mu      sync.RWMutex
    rooms   map[string][]*Client   // roomCode → clients
}

func (h *RoomHub) Register(roomCode string, client *Client)
func (h *RoomHub) Unregister(roomCode string, client *Client)
func (h *RoomHub) Broadcast(roomCode string, message []byte)
```

**`EventBroadcaster`** (application):
```go
type EventBroadcaster struct {
    hub *room.RoomHub
}

func (b *EventBroadcaster) Handle(roomCode string, eventType string, payload []byte)
```

**WebSocket handler** (infrastructure):
- `GET /ws/rooms/{code}` → upgrade HTTP → WebSocket
- Registra el client en el hub con `roomCode`
- Goroutine de lectura (para detectar desconexiones)
- Goroutine de escritura (para enviar mensajes del canal `send`)

**RabbitMQ consumer** (infrastructure):
- Conecta a `amqp://guest:guest@rabbitmq:5672`
- Consume queue `realtime.game_events`
- Parsea el `routing_key` para determinar `eventType`
- Extrae `room_id` del payload
- Llama a `EventBroadcaster.Handle`

Puerto expuesto: `8003`

Dependencias Go:
- `github.com/gin-gonic/gin` — HTTP server
- `github.com/gorilla/websocket` — WebSocket
- `github.com/rabbitmq/amqp091-go` — AMQP client

Tests:
```
realtime/domain/room/hub_test.go
```

Casos:
- `TestRoomHub_Broadcast_SendsToAllClientsInRoom`
- `TestRoomHub_Unregister_RemovesClient`

---

## STEP V2.6 — Frontend: lobby y partida multijugador

Vistas nuevas (sin tocar HomeView/GameView/ScoreboardView):

```
frontend/src/views/
├── LobbyView.vue           # crear sala o unirse con código
├── WaitingRoomView.vue     # sala de espera: lista de jugadores, botón "Iniciar" para el host
└── MultiplayerGameView.vue # igual que GameView + lista de jugadores + scores en vivo
```

Composables nuevos:
```
frontend/src/composables/
├── useRoomAPI.js     # POST /api/rooms, POST /api/rooms/{code}/join, etc.
└── useWebSocket.js   # gestiona conexión WS, emite eventos Vue para cada message type
```

Store nuevo:
```
frontend/src/stores/roomStore.js
# roomId, roomCode, playerId, playerName, players[], roundScores, isHost
```

Router nuevas rutas:
```
/lobby              → LobbyView
/rooms/:code/wait   → WaitingRoomView
/rooms/:code/play   → MultiplayerGameView
```

`useWebSocket` conecta a `ws://localhost:8003/ws/rooms/{code}` y expone un `EventEmitter` o callbacks por tipo de mensaje:
```js
const ws = useWebSocket(roomCode)
ws.on('note.ready',    (payload) => { ... })
ws.on('round.ended',   (payload) => { ... })
ws.on('game.ended',    (payload) => { ... })
```

---

## STEP V2.7 — CI/CD: workflow realtime

```
.github/workflows/realtime.yml
```

```yaml
- Setup Go 1.22
- go mod download
- go build ./...
- go test ./...
```

Sin servicios externos (RabbitMQ no hace falta para los tests unitarios del hub).
