<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useGameAPI } from '../composables/useGameAPI.js'
import { useGameStore } from '../stores/gameStore.js'
import { useAudioPlayer } from '../composables/useAudioPlayer.js'
import { useNoteCatalog } from '../composables/useNoteCatalog.js'

const router  = useRouter()
const api     = useGameAPI()
const store   = useGameStore()
const player  = useAudioPlayer()
const catalog = useNoteCatalog()

// Si el usuario llega aquí sin sesión activa, lo mandamos al inicio.
if (!store.sessionId) {
  router.replace('/')
}

const NOTES = ['do', 're', 'mi', 'fa', 'sol', 'la', 'si']

const DIFFICULTY_INFO = {
  1: {
    label: 'Fácil',
    description: 'Onda sinusoidal pura — solo el sonido fundamental, sin armónicos.',
  },
  2: {
    label: 'Medio',
    description: 'Con armónicos — el sonido incluye las 2ª, 3ª y 4ª armónicas, como un instrumento real.',
  },
  3: {
    label: 'Difícil',
    description: 'Acorde mayor — suenan a la vez la raíz, su 3ª mayor y su 5ª justa. Hay que identificar la raíz.',
  },
}

// Estado local de la ronda actual
const playing       = ref(false)   // mientras se carga/reproduce el audio
const answered      = ref(false)   // si ya ha respondido en esta ronda
const lastResult    = ref(null)    // { is_correct, correct_note }
const noteDetails   = ref(null)    // { name, octave, frequency } — se carga al responder
const loadingNext   = ref(false)   // mientras se pide la siguiente ronda
const error         = ref(null)

const progress = computed(() =>
  `Ronda ${store.currentRound} / ${store.totalRounds}`
)

const difficultyInfo = computed(() => DIFFICULTY_INFO[store.difficulty] ?? null)

async function playNote() {
  playing.value = true
  error.value   = null

  try {
    await player.play(store.noteId, store.difficulty)
  } catch (e) {
    error.value = 'No se pudo reproducir el audio.'
  } finally {
    playing.value = false
  }
}

async function guess(note) {
  if (answered.value) return

  answered.value = true
  error.value    = null

  try {
    const result = await api.submitAnswer(store.roundId, note)
    lastResult.value = result
    store.applyAnswerResult(result)

    // Cargamos los detalles de la nota correcta para mostrárselos al usuario.
    noteDetails.value = await catalog.findNote(store.noteId)
  } catch (e) {
    error.value    = e.message
    answered.value = false
  }
}

async function nextRound() {
  loadingNext.value = true
  error.value       = null

  try {
    const data = await api.nextRound(store.sessionId)
    store.setRound(data)
    answered.value   = false
    lastResult.value = null
    noteDetails.value = null
  } catch (e) {
    error.value = e.message
  } finally {
    loadingNext.value = false
  }
}

function goToScoreboard() {
  router.push('/scoreboard')
}
</script>

<template>
  <div>
    <h1>🎵 Guess the Note</h1>

    <!-- Barra de progreso -->
    <div class="progress-bar">
      <span>{{ progress }}</span>
      <span class="score">Puntos: {{ store.score }}</span>
    </div>

    <!-- Badge de dificultad -->
    <div v-if="difficultyInfo" class="difficulty-badge" :class="`diff-${store.difficulty}`">
      <span class="diff-label">{{ difficultyInfo.label }}</span>
      <span class="diff-desc">{{ difficultyInfo.description }}</span>
    </div>

    <!-- Botón reproducir -->
    <div class="play-area">
      <button class="btn-play" :disabled="playing || answered" @click="playNote">
        {{ playing ? '▶ Cargando...' : '▶ Escuchar nota' }}
      </button>
      <p class="hint-play">Puedes escucharla las veces que quieras antes de responder.</p>
    </div>

    <!-- Grid de notas -->
    <div class="notes-grid">
      <button
        v-for="note in NOTES"
        :key="note"
        class="btn-note"
        :class="{
          correct: answered && lastResult?.correct_note === note,
          wrong:   answered && lastResult?.correct_note !== note && note === lastResult?.guess,
          disabled: answered,
        }"
        :disabled="answered"
        @click="guess(note)"
      >
        {{ note.toUpperCase() }}
      </button>
    </div>

    <!-- Resultado de la respuesta -->
    <div v-if="answered && lastResult" class="result" :class="lastResult.is_correct ? 'correct' : 'wrong'">
      <span v-if="lastResult.is_correct">✓ ¡Correcto!</span>
      <span v-else>✗ Era <strong>{{ lastResult.correct_note.toUpperCase() }}</strong></span>
    </div>

    <!-- Detalles de la nota (se muestran al responder) -->
    <div v-if="answered && noteDetails" class="note-details">
      <p class="note-details-title">Sobre la nota que sonó:</p>
      <ul>
        <li><strong>Nota:</strong> {{ noteDetails.name }} (octava {{ noteDetails.octave }})</li>
        <li><strong>Frecuencia:</strong> {{ noteDetails.frequency }} Hz</li>
        <li v-if="store.difficulty === 2">
          Con sus armónicos sonaron también {{ noteDetails.frequency * 2 }} Hz, {{ noteDetails.frequency * 3 }} Hz y {{ noteDetails.frequency * 4 }} Hz.
        </li>
        <li v-if="store.difficulty === 3">
          En el acorde también sonaron la 3ª mayor ({{ Math.round(noteDetails.frequency * Math.pow(2, 4/12) * 100) / 100 }} Hz) y la 5ª justa ({{ Math.round(noteDetails.frequency * Math.pow(2, 7/12) * 100) / 100 }} Hz).
        </li>
      </ul>
    </div>

    <!-- Error -->
    <p v-if="error" class="error">{{ error }}</p>

    <!-- Acciones post-respuesta -->
    <div v-if="answered" class="actions">
      <button v-if="store.sessionEnded" class="btn-primary" @click="goToScoreboard">
        Ver resultados
      </button>
      <button v-else class="btn-primary" :disabled="loadingNext" @click="nextRound">
        {{ loadingNext ? 'Cargando...' : 'Siguiente ronda →' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.difficulty-badge {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  padding: 0.6rem 1rem;
  border-radius: 0.5rem;
  margin-bottom: 1.25rem;
  border-left: 4px solid;
}

.diff-1 { background: #14532d22; border-color: #16a34a; }
.diff-2 { background: #1e3a5f22; border-color: #0ea5e9; }
.diff-3 { background: #4a044e22; border-color: #a855f7; }

.diff-label {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: #94a3b8;
}

.diff-desc {
  font-size: 0.85rem;
  color: #cbd5e1;
}

.note-details {
  background: #1e293b;
  border-radius: 0.5rem;
  padding: 0.9rem 1rem;
  margin-bottom: 1rem;
  font-size: 0.85rem;
  color: #94a3b8;
}

.note-details-title {
  font-weight: 700;
  color: #e2e8f0;
  margin-bottom: 0.5rem;
}

.note-details ul {
  list-style: none;
  padding: 0;
  margin: 0;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.note-details li strong {
  color: #cbd5e1;
}

.progress-bar {
  display: flex;
  justify-content: space-between;
  font-size: 0.85rem;
  color: #94a3b8;
  margin-bottom: 1.5rem;
}

.score {
  font-weight: 700;
  color: #a78bfa;
}

.play-area {
  text-align: center;
  margin-bottom: 1.5rem;
}

.btn-play {
  background: #0ea5e9;
  color: #fff;
  padding: 1rem 2rem;
  font-size: 1.1rem;
  border-radius: 999px;
}

.hint-play {
  margin-top: 0.5rem;
  font-size: 0.75rem;
  color: #475569;
}

.notes-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

/* La séptima nota ocupa el espacio restante centrado */
.notes-grid button:last-child {
  grid-column: span 1;
  margin-left: auto;
  margin-right: auto;
  width: 100%;
}

.btn-note {
  background: #334155;
  color: #cbd5e1;
  padding: 1rem;
  font-size: 1.1rem;
  border-radius: 0.5rem;
}

.btn-note.correct {
  background: #16a34a;
  color: #fff;
}

.btn-note.wrong {
  background: #dc2626;
  color: #fff;
}

.result {
  text-align: center;
  padding: 0.75rem;
  border-radius: 0.5rem;
  font-size: 1.1rem;
  font-weight: 600;
  margin-bottom: 1rem;
}

.result.correct { background: #14532d; color: #4ade80; }
.result.wrong   { background: #450a0a; color: #f87171; }

.error {
  color: #f87171;
  font-size: 0.9rem;
  text-align: center;
  margin-bottom: 1rem;
}

.actions {
  display: flex;
  justify-content: center;
}

.btn-primary {
  background: #7c3aed;
  color: #fff;
  padding: 0.9rem 2rem;
}
</style>
