# CLAUDE.md — Guía de colaboración para este proyecto

## Workflow obligatorio

- No implementar nada sin que el usuario lo pida explícitamente. Planear, proponer, esperar luz verde.
- Antes de hacer un commit, avisar al usuario y esperar su confirmación.
- Documentar cada nueva funcionalidad en `planning.md` (qué) y `steps.md` (cómo) antes de implementar.

## Estilo de trabajo

- **Modo enseñanza activo**: al implementar cualquier cosa, explicar el razonamiento: por qué ese patrón, qué alternativas existían y por qué se descartaron.
- **Hablar como senior**: aplicar y explicar buenas prácticas sin que el usuario tenga que pedirlo.

## Qué explicar siempre

- Patrones de diseño usados (Repository, Factory, Strategy, Event-Driven, etc.)
- Decisiones de diseño de sistemas (por qué esta estructura de eventos, por qué esta separación de responsabilidades)
- Configuración de infraestructura: qué hace cada parte del Docker Compose, por qué esa red, por qué ese volumen
- Flujo de mensajes RabbitMQ: qué se publica, quién consume, por qué ese routing key
- Cualquier cosa no obvia o que tenga alternativas relevantes

---

## Arquitectura obligatoria

- **DDD + Arquitectura Hexagonal (Ports & Adapters)** en los 3 servicios
- Regla de dependencia estricta: Domain ← Application ← Infrastructure
- El dominio no importa ningún framework, ORM ni broker
- Estructura de carpetas basada en el proyecto betting:
  - `Domain/{Contexto}/Entity/` — Aggregate Roots y Entities
  - `Domain/{Contexto}/Repository/` — interfaces (repositorios Y otros puertos como EventBusInterface)
  - `Domain/{Contexto}/ValueObject/` — Value Objects
  - `Domain/{Contexto}/Event/` — Domain Events
  - `Domain/{Contexto}/Service/` — Domain Services
  - `Application/{Contexto}/Service/` — Application Services (use cases)
  - `Infrastructure/{Contexto}/Http/Controller/` — Controllers
  - `Infrastructure/{Contexto}/Persistence/Doctrine/` — Implementaciones de repositorios
  - `Infrastructure/Shared/Persistence/Doctrine/Migrations/` — Migraciones (game-service)
- NO usar "Port" como nombre de carpeta, usar "Repository" para todas las interfaces de dominio
- Aggregates emiten Domain Events; los Application Services los transforman en Integration Events (RabbitMQ)
- Value Objects para todo dato con reglas de negocio (RoomCode, Score, Difficulty...)

---

## Convenciones de código

### PHP (game-service)

- Constructor **privado** + factory method estático `create(...): self`
- Getters **sin prefijo `get`**: `status()`, `code()`, `score()` — nunca `getStatus()`
- Los setters sí mantienen el prefijo `set` si hacen falta
- `declare(strict_types=1)` en todos los ficheros PHP sin excepción
- No añadir comentarios salvo que la lógica no sea evidente por sí sola
- No añadir docblocks ni type annotations en código que no se ha modificado
- `config/services.yaml`: bindings explícitos de interfaces → implementaciones Doctrine
- `config/routes.yaml`: rutas declaradas por bounded context

### Python (audio-brain)

- Usar `Protocol` (typing) para las interfaces de dominio, no ABC salvo que haya lógica compartida
- Clases de dominio inmutables con `@dataclass(frozen=True)` donde aplique
- Value Objects como dataclasses inmutables
- Sin lógica en los routers — solo delegan al Application Service

### Go (realtime)

- Interfaces en el paquete que las consume, no en el que las implementa (convención Go)
- Structs con constructor explícito (`NewRoomHub(...)`) en lugar de inicialización directa
- Errores explícitos, sin panics en lógica de negocio

---

## Testing

### Nomenclatura obligatoria (los 3 servicios)

```
test_{acción}_{contexto}__should_{resultado_esperado}
```

Ejemplos:
- `test_creating_room__with_valid_data__should_generate_six_char_code`
- `test_submitting_answer__when_round_already_ended__should_raise_exception`
- `test_get_fragment_for_round__when_no_songs_available__should_raise_error`

### Niveles de test

| Nivel | Qué prueba | Qué se mockea |
|---|---|---|
| **Unit** | Una clase de dominio aislada | Todo lo externo |
| **Integration** | Repositorios Doctrine + Application Services | Solo APIs externas |
| **Functional** | Controllers HTTP end-to-end | Nada (o solo mensajería) |

- Tests unitarios: probar una sola clase, mockear libremente
- Tests de integración: **BD de test real**, solo se mockea RabbitMQ y APIs externas
- No se considera implementado un cambio hasta que los tests pasan

---

## Infraestructura Docker

- Contenedor `php-cli` separado de `php-fpm` para comandos Symfony (migraciones, seeds)
- Healthchecks en todos los servicios con dependencias (MySQL, RabbitMQ)
- Dead Letter Queue configurada en RabbitMQ para mensajes fallidos
- `.env.example` junto a cada `.env` en todos los servicios

---

## CI/CD

- GitHub Actions con un workflow por servicio (game-service, audio-brain, realtime)
- Los workflows corren en paralelo al hacer push
- Cada workflow: lint + tests unitarios + tests de integración

---

## Buenas prácticas generales

- **Principios SOLID** en todo el código orientado a objetos
- **Idempotencia** en los consumers de eventos (mismo mensaje procesado dos veces = mismo resultado)
- **Variables de entorno** para toda configuración sensible
- **DTOs** para transferencia de datos entre capas

---

## Proyecto actual

Juego musical "Guess the Song" — microservicios:
- `game-service` → PHP / Symfony 7 (salas, rondas, puntuaciones)
- `audio-brain` → Python / FastAPI (catálogo de canciones, fragmentos)
- `realtime` → Go / Gin (WebSockets, broadcast en tiempo real)
- `frontend` → Vue.js 3
- Broker: RabbitMQ (exchange `game_events`, tipo topic)
- DBs: MySQL independiente por servicio

Ver plan completo en: `.claude/plans/abstract-jingling-prism.md`
Ver planificación en: `planification/planning.md`
Ver steps de implementación en: `planification/steps.md`
