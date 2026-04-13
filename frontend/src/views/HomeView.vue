<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useGameAPI } from '../composables/useGameAPI.js'
import { useGameStore } from '../stores/gameStore.js'

const router  = useRouter()
const api     = useGameAPI()
const store   = useGameStore()

// ref() es el equivalente Vue de una variable reactiva.
// Cuando cambia, Vue repinta el template automáticamente.
const difficulty  = ref(1)
const totalRounds = ref(5)
const loading     = ref(false)
const error       = ref(null)

async function startGame() {
  loading.value = true
  error.value   = null

  try {
    const data = await api.startSession(difficulty.value, totalRounds.value)
    store.setSession(data)
    router.push('/game')
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div>
    <h1>🎵 Guess the Note</h1>

    <div class="card">
      <div class="field">
        <label>Dificultad</label>
        <div class="options">
          <button
            v-for="d in [1, 2, 3]"
            :key="d"
            :class="{ active: difficulty === d }"
            @click="difficulty = d"
          >
            {{ ['Fácil', 'Media', 'Difícil'][d - 1] }}
          </button>
        </div>
        <p class="hint">
          {{ ['Nota pura, octava 4', 'Nota con armónicos, octavas 3–5', 'Acorde de 3 notas'][difficulty - 1] }}
        </p>
      </div>

      <div class="field">
        <label>Rondas: <strong>{{ totalRounds }}</strong></label>
        <input type="range" min="3" max="10" v-model.number="totalRounds" />
      </div>

      <p v-if="error" class="error">{{ error }}</p>

      <button class="btn-primary" :disabled="loading" @click="startGame">
        {{ loading ? 'Iniciando...' : 'Empezar' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.card {
  background: #1e293b;
  border-radius: 1rem;
  padding: 2rem;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

label {
  font-size: 0.9rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.options {
  display: flex;
  gap: 0.5rem;
}

.options button {
  flex: 1;
  background: #334155;
  color: #cbd5e1;
}

.options button.active {
  background: #7c3aed;
  color: #fff;
}

.hint {
  font-size: 0.8rem;
  color: #64748b;
}

input[type="range"] {
  width: 100%;
  accent-color: #7c3aed;
}

.btn-primary {
  background: #7c3aed;
  color: #fff;
  width: 100%;
  padding: 1rem;
  font-size: 1.1rem;
}

.error {
  color: #f87171;
  font-size: 0.9rem;
  text-align: center;
}
</style>
