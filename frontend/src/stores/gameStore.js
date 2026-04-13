import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useGameStore = defineStore('game', () => {
  // Datos de la sesión activa
  const sessionId    = ref(null)
  const roundId      = ref(null)
  const noteId       = ref(null)
  const difficulty   = ref(1)
  const totalRounds  = ref(5)
  const currentRound = ref(0)
  const score        = ref(0)
  const sessionEnded = ref(false)

  function setSession(data) {
    sessionId.value    = data.session_id
    roundId.value      = data.round_id
    noteId.value       = data.note_id
    difficulty.value   = data.difficulty
    totalRounds.value  = data.total_rounds
    currentRound.value = data.current_round
    score.value        = data.score
    sessionEnded.value = false
  }

  function setRound(data) {
    roundId.value = data.round_id
    noteId.value  = data.note_id
    currentRound.value++
  }

  function applyAnswerResult(data) {
    score.value        = data.score
    sessionEnded.value = data.session_ended
  }

  function reset() {
    sessionId.value    = null
    roundId.value      = null
    noteId.value       = null
    currentRound.value = 0
    score.value        = 0
    sessionEnded.value = false
  }

  return {
    sessionId, roundId, noteId,
    difficulty, totalRounds, currentRound, score, sessionEnded,
    setSession, setRound, applyAnswerResult, reset,
  }
})
