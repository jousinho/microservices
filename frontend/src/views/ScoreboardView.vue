<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useGameStore } from '../stores/gameStore.js'

const router = useRouter()
const store  = useGameStore()

if (!store.sessionId) {
  router.replace('/')
}

const percentage = computed(() =>
  store.totalRounds > 0
    ? Math.round((store.score / store.totalRounds) * 100)
    : 0
)

const message = computed(() => {
  if (percentage.value === 100) return '¡Perfecto! Oído de oro 🏆'
  if (percentage.value >= 70)  return '¡Muy bien! Buen oído 🎵'
  if (percentage.value >= 40)  return 'No está mal, sigue practicando 🎶'
  return '¡A practicar más! 💪'
})

function playAgain() {
  store.reset()
  router.push('/')
}
</script>

<template>
  <div>
    <h1>🏆 Resultado</h1>

    <div class="card">
      <div class="score-display">
        <span class="score-number">{{ store.score }}</span>
        <span class="score-total">/ {{ store.totalRounds }}</span>
      </div>

      <div class="percentage-bar">
        <div class="percentage-fill" :style="{ width: percentage + '%' }"></div>
      </div>
      <p class="percentage-text">{{ percentage }}% de aciertos</p>

      <p class="message">{{ message }}</p>

      <button class="btn-primary" @click="playAgain">
        Jugar de nuevo
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
  align-items: center;
  gap: 1.25rem;
  text-align: center;
}

.score-display {
  display: flex;
  align-items: baseline;
  gap: 0.25rem;
}

.score-number {
  font-size: 4rem;
  font-weight: 800;
  color: #a78bfa;
  line-height: 1;
}

.score-total {
  font-size: 1.5rem;
  color: #64748b;
}

.percentage-bar {
  width: 100%;
  height: 12px;
  background: #334155;
  border-radius: 999px;
  overflow: hidden;
}

.percentage-fill {
  height: 100%;
  background: #7c3aed;
  border-radius: 999px;
  transition: width 0.6s ease;
}

.percentage-text {
  font-size: 0.9rem;
  color: #94a3b8;
}

.message {
  font-size: 1.1rem;
  font-weight: 600;
  color: #e2e8f0;
}

.btn-primary {
  background: #7c3aed;
  color: #fff;
  padding: 0.9rem 2.5rem;
  font-size: 1rem;
  width: 100%;
}
</style>
