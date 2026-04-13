const BASE = import.meta.env.VITE_GAME_SERVICE_URL || 'http://localhost:8001'

async function post(path, body = {}) {
  const res = await fetch(`${BASE}${path}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  })

  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.error || `HTTP ${res.status}`)
  }

  return res.json()
}

async function get(path) {
  const res = await fetch(`${BASE}${path}`)

  if (!res.ok) {
    const err = await res.json().catch(() => ({}))
    throw new Error(err.error || `HTTP ${res.status}`)
  }

  return res.json()
}

export function useGameAPI() {
  function startSession(difficulty, totalRounds) {
    return post('/api/sessions', { difficulty, total_rounds: totalRounds })
  }

  function nextRound(sessionId) {
    return post(`/api/sessions/${sessionId}/next-round`)
  }

  function submitAnswer(roundId, guess) {
    return post(`/api/rounds/${roundId}/answer`, { guess })
  }

  function getScoreboard(sessionId) {
    return get(`/api/sessions/${sessionId}/scoreboard`)
  }

  return { startSession, nextRound, submitAnswer, getScoreboard }
}
