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

# V2.0 — Multijugador (pendiente de diseño detallado)

Los steps de V2 se definirán cuando V1.0 esté completa y en producción.

Resumen de lo que se añade (sin reescribir lo existente):

- **STEP V2.1** — Añadir RabbitMQ al Docker Compose
- **STEP V2.2** — game-service: entidad `Room` (Aggregate Root con jugadores), reemplaza `Session`
- **STEP V2.3** — game-service: publisher RabbitMQ (sustituye la llamada HTTP directa a audio-brain)
- **STEP V2.4** — audio-brain: consumer `game.round.started` + publisher `audio.note.ready`
- **STEP V2.5** — servicio `realtime` en Go: WebSocket hub por sala, consumer RabbitMQ
- **STEP V2.6** — Frontend: lobby, lista de jugadores en tiempo real, scores en vivo
- **STEP V2.7** — CI/CD: añadir workflow `realtime.yml`
