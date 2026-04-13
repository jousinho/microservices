const AUDIO_BASE = import.meta.env.VITE_AUDIO_BRAIN_URL || 'http://localhost:8002'

// AudioContext se crea una sola vez y se reutiliza.
// Los navegadores exigen que sea creado tras un gesto del usuario.
let audioContext = null

function getContext() {
  if (!audioContext) {
    audioContext = new AudioContext()
  }
  return audioContext
}

export function useAudioPlayer() {
  async function play(noteId, difficulty) {
    const ctx = getContext()

    // Si el navegador suspendió el contexto (política autoplay), lo reanudamos.
    if (ctx.state === 'suspended') {
      await ctx.resume()
    }

    const url = `${AUDIO_BASE}/api/notes/${noteId}/audio?difficulty=${difficulty}`

    const response    = await fetch(url)
    const arrayBuffer = await response.arrayBuffer()
    const audioBuffer = await ctx.decodeAudioData(arrayBuffer)

    const source = ctx.createBufferSource()
    source.buffer = audioBuffer
    source.connect(ctx.destination)
    source.start(0)
  }

  return { play }
}
