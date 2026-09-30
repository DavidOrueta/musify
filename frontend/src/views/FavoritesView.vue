<script setup>
import { computed, onMounted, ref } from 'vue'
import { getFavorites, removeFavorite } from '../services/api'

const favorites = ref([])
const loadError = ref('')
const sortBy = ref('title')

const sortedFavorites = computed(() => {
  const songs = [...favorites.value]
  const compareText = (first, second) => String(first || '').localeCompare(String(second || ''), 'es', { sensitivity: 'base' })

  songs.sort((first, second) => {
    if (sortBy.value === 'artist') {
      return compareText(first.artist, second.artist) || compareText(first.title, second.title)
    }
    if (sortBy.value === 'album') {
      return compareText(first.album, second.album) || compareText(first.title, second.title)
    }
    if (sortBy.value === 'date-desc') {
      return new Date(second.created_at) - new Date(first.created_at)
    }
    if (sortBy.value === 'date-asc') {
      return new Date(first.created_at) - new Date(second.created_at)
    }
    return compareText(first.title, second.title) || compareText(first.artist, second.artist)
  })

  return songs
})

function playSong(song) {
  const track = {
    ...song,
    artist: song.artist || 'Artista',
    image: song.image || 'https://placehold.co/600x600/10251b/8fe7c2?text=♪',
    audioUrl: song.audioUrl || 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
    duration: Number(song.duration) || 209,
  }
  window.dispatchEvent(new CustomEvent('musify:play', { detail: track }))
}

function queueSong(song) {
  const track = {
    ...song,
    artist: song.artist || 'Artista',
    image: song.image || 'https://placehold.co/600x600/10251b/8fe7c2?text=♪',
    audioUrl: song.audioUrl || 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
    duration: Number(song.duration) || 209,
  }
  window.dispatchEvent(new CustomEvent('musify:queue', { detail: track }))
}

async function loadFavorites() {
  try {
    const response = await getFavorites()
    favorites.value = response.songs || []
  } catch (requestError) {
    favorites.value = []
    loadError.value = requestError.message
    if (requestError.message === 'Necesitas iniciar sesión.') window.dispatchEvent(new CustomEvent('musify:auth'))
  }
}

async function toggleFavorite(song) {
  try {
    await removeFavorite(Number(song.id))
    favorites.value = favorites.value.filter((item) => Number(item.id) !== Number(song.id))
  } catch (requestError) {
    if (requestError?.message === 'Necesitas iniciar sesión.') {
      window.dispatchEvent(new CustomEvent('musify:auth'))
    }
  }
}

onMounted(loadFavorites)
</script>

<template>
  <section class="simple-view">
    <span class="eyebrow">Tu colección</span>
    <h1>Favoritos</h1>
    <p>Las canciones que quieres volver a escuchar aparecerán aquí.</p>
    <p v-if="loadError" class="api-message api-error">{{ loadError }}</p>

    <div class="favorites-toolbar">
      <label for="favorite-sort">Ordenar por</label>
      <select id="favorite-sort" v-model="sortBy">
        <option value="title">Canción (A-Z)</option>
        <option value="artist">Artista (A-Z)</option>
        <option value="album">Álbum (A-Z)</option>
        <option value="date-desc">Fecha añadida (recientes)</option>
        <option value="date-asc">Fecha añadida (antiguas)</option>
      </select>
    </div>

    <div v-if="favorites.length" class="song-grid">
      <article v-for="song in sortedFavorites" :key="song.id" class="song-card" @click="playSong(song)" @dblclick="playSong(song)">
        <img v-if="song.image" :src="song.image" :alt="song.title" class="song-cover">
        <div v-else class="song-art">♪</div>
        <span class="tag">CANCIÓN</span>
        <strong>{{ song.title }}</strong>
        <small>{{ song.artist }}</small>
        <div class="song-actions"><button type="button" class="queue-mini-button" aria-label="Añadir a la cola" title="Añadir a la cola" @click.stop="queueSong(song)">≡+</button><button type="button" aria-label="Quitar de favoritos" @click.stop="toggleFavorite(song)">♥</button></div>
      </article>
    </div>

    <div v-else class="empty-state">♡<strong>Aún no hay favoritas</strong></div>
  </section>
</template>