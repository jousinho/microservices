const AUDIO_BASE = import.meta.env.VITE_AUDIO_BRAIN_URL || 'http://localhost:8002'

// Caché en módulo: se carga una sola vez por sesión de navegador.
let catalogPromise = null

function fetchCatalog() {
  if (!catalogPromise) {
    catalogPromise = fetch(`${AUDIO_BASE}/api/notes`).then(r => r.json())
  }
  return catalogPromise
}

export function useNoteCatalog() {
  async function findNote(noteId) {
    const catalog = await fetchCatalog()
    return catalog.find(n => n.note_id === noteId) ?? null
  }

  return { findNote }
}
