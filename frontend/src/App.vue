<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { getCurrentUser, login, logout, register, searchCatalog } from './services/api'
import logoUrl from './assets/a1c55ca8-1a92-4bff-b1a6-6d240a09887e.png'

const demoAudioUrl = 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3'
const fallbackAudioUrl = 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3'
const defaultCoverUrl = 'https://placehold.co/600x600/10251b/8fe7c2?text=♪'
const search = ref('')
const searchResults = ref({ albums: [], artists: [], songs: [] })
const searchLoading = ref(false)
const searchError = ref('')
let searchTimer
const currentUser = ref(null)
const authOpen = ref(false)
const authMode = ref('login')
const authError = ref('')
const authForm = ref({ username: '', email: '', password: '' })
const audioRef = ref(null)
const playerSong = ref({ title: 'CAROUSEL', artist: 'Travis Scott', image: '', audioUrl: demoAudioUrl, duration: 209 })
const playerPlaying = ref(false)
const playerCurrentTime = ref(0)
const playerDuration = ref(209)
const playerQueue = ref([])
const queueOpen = ref(false)

async function updateSearchResults() {
  const term = search.value.trim()
  if (!term) {
    searchResults.value = { albums: [], artists: [], songs: [] }
    searchError.value = ''
    return
  }

  searchLoading.value = true
  searchError.value = ''
  try {
    const response = await searchCatalog(term)
    searchResults.value = {
      albums: response.albums || [],
      artists: response.artists || [],
      songs: response.songs || [],
    }
  } catch (error) {
    searchResults.value = { albums: [], artists: [], songs: [] }
    if (error?.message === 'Failed to fetch') {
      searchError.value = ''
      return
    }
    searchError.value = 'No se pudo realizar la búsqueda.'
  } finally {
    searchLoading.value = false
  }
}

watch(search, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(updateSearchResults, 250)
})

function handleAuthRequest() {
  openAuth('login')
}

onMounted(async () => {
  window.addEventListener('musify:auth', handleAuthRequest)
  window.addEventListener('musify:play', playTrack)
  window.addEventListener('musify:queue', (event) => addTrackToQueue(event.detail))
  const response = await getCurrentUser().catch(() => ({ authenticated: false }))
  currentUser.value = response.authenticated ? response.user : null
})

onUnmounted(() => {
  clearTimeout(searchTimer)
  window.removeEventListener('musify:auth', handleAuthRequest)
  window.removeEventListener('musify:play', playTrack)
  window.removeEventListener('musify:queue', (event) => addTrackToQueue(event.detail))
})

function openAuth(mode) {
  authMode.value = mode
  authError.value = ''
  authOpen.value = true
}

async function submitAuth() {
  authError.value = ''
  try {
    const response = authMode.value === 'login'
      ? await login({ email: authForm.value.email, password: authForm.value.password })
      : await register(authForm.value)
    currentUser.value = response.user
    authOpen.value = false
    authForm.value = { username: '', email: '', password: '' }
  } catch (error) {
    authError.value = error.message
  }
}

async function signOut() {
  await logout().catch(() => {})
  currentUser.value = null
}

function formatTime(value) {
  const totalSeconds = Number.isFinite(value) ? Math.max(0, Math.floor(value)) : 0
  const minutes = Math.floor(totalSeconds / 60)
  const seconds = totalSeconds % 60
  return `${minutes}:${String(seconds).padStart(2, '0')}`
}

function normalizeTrack(source) {
  return {
    id: source?.id || Date.now() + Math.random(),
    title: source?.title || 'Canción',
    artist: source?.artist || 'Artista',
    image: source?.image || source?.cover || defaultCoverUrl,
    audioUrl: source?.audioUrl || source?.file_url || fallbackAudioUrl,
    duration: Number(source?.duration) || 209,
  }
}

function addTrackToQueue(track) {
  const normalized = normalizeTrack(track)
  const key = `${normalized.title}-${normalized.artist}-${normalized.id}`
  const alreadyQueued = playerQueue.value.some((item) => `${item.title}-${item.artist}-${item.id}` === key)
  if (!alreadyQueued) {
    playerQueue.value = [...playerQueue.value, normalized]
  }
}

function playNextFromQueue() {
  if (!playerQueue.value.length) return
  const next = playerQueue.value.shift()
  playerQueue.value = [...playerQueue.value]
  if (next) {
    playTrack({ detail: next })
  }
}

function handleTrackEnded() {
  playerPlaying.value = false
  window.setTimeout(() => {
    playNextFromQueue()
  }, 0)
}

function playTrack(event) {
  const source = event?.detail || event
  const track = normalizeTrack(source)

  playerSong.value = track
  playerDuration.value = track.duration
  playerCurrentTime.value = 0
  playerPlaying.value = true

  if (audioRef.value) {
    audioRef.value.src = track.audioUrl
    audioRef.value.load()
    audioRef.value.currentTime = 0
    audioRef.value.play().catch(() => {
      playerPlaying.value = false
    })
  }
}

function togglePlayer() {
  const audio = audioRef.value
  if (!audio) return

  if (playerPlaying.value) {
    audio.pause()
    playerPlaying.value = false
    return
  }

  audio.src = playerSong.value.audioUrl || demoAudioUrl
  audio.load()
  audio.play().catch(() => {
    playerPlaying.value = false
  })
  playerPlaying.value = true
}

function updatePlayerTime() {
  if (audioRef.value) {
    playerCurrentTime.value = Number(audioRef.value.currentTime) || 0
  }
}

function seekPlayer(event) {
  const audio = audioRef.value
  if (!audio) return

  const nextTime = Number(event.target.value)
  audio.currentTime = nextTime
  playerCurrentTime.value = nextTime
}

const progressPercent = computed(() => {
  const total = playerDuration.value || 1
  return total > 0 ? (playerCurrentTime.value / total) * 100 : 0
})

const matchingItems = computed(() => {
  const items = []

  searchResults.value.albums.forEach((album) => {
    items.push({
      id: album.id,
      title: album.title,
      artist: album.artist,
      image: album.image,
      kind: 'ÁLBUM',
      href: `/album/${album.id}`,
    })
  })

  searchResults.value.songs.forEach((song) => {
    items.push({
      id: song.id,
      title: song.title,
      artist: song.artist,
      image: song.image,
      kind: 'CANCIÓN',
      href: song.album_id ? `/album/${song.album_id}` : song.artist_id ? `/artista/${song.artist_id}` : '/artistas',
    })
  })

  searchResults.value.artists.forEach((artist) => {
    items.push({
      id: artist.id,
      title: artist.name,
      artist: 'Artista',
      image: artist.image,
      kind: 'ARTISTA',
      href: `/artista/${artist.id}`,
    })
  })

  return items.slice(0, 8)
})
</script>

<template>
  <div class="app-shell">
    <aside class="sidebar">
      <RouterLink class="brand" to="/"><img :src="logoUrl" alt="Musify"></RouterLink>
      <nav class="main-nav" aria-label="Navegación principal">
        <RouterLink to="/" exact-active-class="is-active">Inicio</RouterLink>
        <RouterLink to="/favoritos" active-class="is-active">Favoritos</RouterLink>
        <RouterLink to="/listas" active-class="is-active">Tus listas</RouterLink>
        <RouterLink to="/artistas" active-class="is-active">Artistas</RouterLink>
      </nav>
      <div class="sidebar-footer"><span class="status-dot"></span><span>Tu música, a tu manera</span></div>
    </aside>
    <div class="workspace">
      <header class="topbar">
        <div class="search-wrap">
          <label class="search-box"><span>Buscar</span><input v-model="search" type="search" placeholder="Artistas, álbumes o canciones"><kbd>⌕</kbd></label>
          <div v-if="search" class="search-results">
            <small v-if="searchLoading" class="search-status">Buscando...</small>
            <small v-else-if="searchError" class="search-status search-status-error">{{ searchError }}</small>
            <small v-else-if="!matchingItems.length" class="search-status">Sin resultados.</small>
            <RouterLink v-for="item in matchingItems" :key="`${item.kind}-${item.id}`" :to="item.href" class="search-result">
              <img :src="item.image" :alt="item.title"><span><strong>{{ item.title }}</strong><small>{{ item.artist }}</small></span><b>{{ item.kind }}</b>
            </RouterLink>
          </div>
        </div>
        <div class="account-actions"><span class="account-name">{{ currentUser?.username || 'Invitado' }}</span><button v-if="currentUser" class="outline-button" type="button" @click="signOut">Cerrar sesión</button><button v-else class="outline-button" type="button" @click="openAuth('login')">Iniciar sesión</button></div>
      </header>
      <main class="page-content"><RouterView :search="search" /></main>
      <footer class="player-bar">
        <audio
          ref="audioRef"
          :src="playerSong.audioUrl || demoAudioUrl"
          preload="metadata"
          @loadedmetadata="() => {
            const duration = Number(audioRef?.value?.duration) || Number(playerSong.duration) || 209
            playerDuration.value = duration
          }"
          @timeupdate="updatePlayerTime"
          @pause="playerPlaying = false"
          @ended="handleTrackEnded"
        ></audio>
        <div class="player-track">
          <div v-if="playerSong.image" class="player-art"><img :src="playerSong.image" :alt="playerSong.title"></div>
          <div v-else class="player-art">♪</div>
          <span><strong>{{ playerSong.title }}</strong><small>{{ playerSong.artist }}</small></span>
        </div>
        <div class="player-controls">
          <button aria-label="Canción anterior">‹</button>
          <button type="button" class="queue-button" aria-label="Abrir cola de reproducción" title="Abrir cola de reproducción" @click="queueOpen = !queueOpen">≡</button>
          <button class="play-button" :aria-label="playerPlaying ? 'Pausar' : 'Reproducir'" @click="togglePlayer">{{ playerPlaying ? 'Ⅱ' : '▶' }}</button>
          <button type="button" aria-label="Siguiente" @click="playNextFromQueue">›</button>
        </div>
        <div class="player-progress"><small>{{ formatTime(playerCurrentTime) }}</small><input type="range" min="0" :max="playerDuration || 1" step="0.1" :value="playerCurrentTime" :style="{ '--progress': `${progressPercent}%` }" aria-label="Posición de reproducción" @input="seekPlayer"><small>{{ formatTime(playerDuration) }}</small></div>
      </footer>
      <div v-if="queueOpen" class="queue-panel">
        <div class="queue-header">
          <strong>Cola</strong>
          <button type="button" @click="queueOpen = false">Cerrar</button>
        </div>
        <div v-if="!playerQueue.length" class="queue-empty">Vacia por ahora.</div>
        <button v-for="track in playerQueue" :key="`${track.id}-${track.title}`" type="button" class="queue-item" @click="playTrack({ detail: track }); queueOpen = false">
          <img v-if="track.image" :src="track.image" :alt="track.title">
          <span><strong>{{ track.title }}</strong><small>{{ track.artist }}</small></span>
        </button>
      </div>
    </div>
  </div>
  <div v-if="authOpen" class="auth-backdrop" @click.self="authOpen = false">
    <form class="auth-modal" @submit.prevent="submitAuth">
      <button class="auth-close" type="button" aria-label="Cerrar" @click="authOpen = false">×</button>
      <span class="eyebrow">Musify</span>
      <h2>{{ authMode === 'login' ? 'Iniciar sesión' : 'Crear cuenta' }}</h2>
      <input v-if="authMode === 'register'" v-model="authForm.username" required type="text" placeholder="Nombre de usuario">
      <input v-model="authForm.email" required type="email" placeholder="Correo electrónico">
      <input v-model="authForm.password" required type="password" minlength="6" placeholder="Contraseña">
      <p v-if="authError" class="auth-error">{{ authError }}</p>
      <button class="primary-button" type="submit">{{ authMode === 'login' ? 'Entrar' : 'Registrarme' }}</button>
      <button class="auth-switch" type="button" @click="openAuth(authMode === 'login' ? 'register' : 'login')">{{ authMode === 'login' ? 'Crear una cuenta' : 'Ya tengo una cuenta' }}</button>
    </form>
  </div>
</template>
