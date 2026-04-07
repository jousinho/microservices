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
game-service   → php-fpm (8001) + php-cli (migraciones)
audio-brain    → uvicorn (8002)  ← genera WAVs al arrancar, sin BD
frontend       → vite dev server (3000)
mysql-game     → MySQL 8 (3307)
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

## V2.0 — Multijugador (pendiente de diseño detallado)

Añadir sobre V1.0:
- Servicio `realtime` en Go (WebSocket hub por sala)
- RabbitMQ como broker de integración entre los 3 servicios
- Concepto de `Room` con código de invitación, host, jugadores
- Eventos de integración: `game.round.started`, `audio.note.ready`, `game.round.ended`, `game.ended`
- Frontend: lobby, lista de jugadores en tiempo real, scores en vivo

El dominio de game-service y audio-brain **no se reescribe**: se añaden adapters de infraestructura (RabbitMQ publisher/consumer) sin tocar Domain ni Application.
