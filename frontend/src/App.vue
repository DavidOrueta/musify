<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { getCurrentUser, login, logout, register, searchCatalog } from './services/api'
import logoUrl from './assets/a1c55ca8-1a92-4bff-b1a6-6d240a09887e.png'

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
const playerSong = ref({ title: 'CAROUSEL', artist: 'Travis Scott', image: '' })
const playerPlaying = ref(false)

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
  } catch {
    searchResults.value = { albums: [], artists: [], songs: [] }
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
  const response = await getCurrentUser().catch(() => ({ authenticated: false }))
  currentUser.value = response.authenticated ? response.user : null
})

onUnmounted(() => {
  clearTimeout(searchTimer)
  window.removeEventListener('musify:auth', handleAuthRequest)
  window.removeEventListener('musify:play', playTrack)
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

function playTrack(event) {
  playerSong.value = event.detail
  playerPlaying.value = true
}

function togglePlayer() {
  playerPlaying.value = !playerPlaying.value
}

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
        <div class="player-track"><div class="player-art">♪</div><span><strong>{{ playerSong.title }}</strong><small>{{ playerSong.artist }}</small></span></div>
        <div class="player-controls"><button aria-label="Canción anterior">‹</button><button class="play-button" :aria-label="playerPlaying ? 'Pausar' : 'Reproducir'" @click="togglePlayer">{{ playerPlaying ? 'Ⅱ' : '▶' }}</button><button aria-label="Canción siguiente">›</button></div>
        <div class="player-progress"><small>0:32</small><span><i></i></span><small>3:09</small></div>
      </footer>
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
