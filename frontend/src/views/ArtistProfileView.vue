<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { addFavorite, getArtist, getFavorites, removeFavorite } from '../services/api'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const artist = ref({ name: 'Artista', albums: [], songs: [] })
const favoriteIds = ref(new Set())
const showAllSongs = ref(false)

const visibleSongs = computed(() => (showAllSongs.value ? artist.value.songs : artist.value.songs.slice(0, 6)))

function isFavorite(songId) {
  return favoriteIds.value.has(Number(songId))
}

function playSong(song) {
  const track = {
    ...song,
    artist: song.artist || artist.value.name,
    image: song.image || artist.value.image || 'https://placehold.co/600x600/10251b/8fe7c2?text=♪',
    audioUrl: song.audioUrl || 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
    duration: Number(song.duration) || 209,
  }
  window.dispatchEvent(new CustomEvent('musify:play', { detail: track }))
}

function queueSong(song) {
  const track = {
    ...song,
    artist: song.artist || artist.value.name,
    image: song.image || artist.value.image || 'https://placehold.co/600x600/10251b/8fe7c2?text=♪',
    audioUrl: song.audioUrl || 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
    duration: Number(song.duration) || 209,
  }
  window.dispatchEvent(new CustomEvent('musify:queue', { detail: track }))
}

async function toggleFavorite(song) {
  const songId = Number(song.id)
  try {
    if (isFavorite(songId)) {
      await removeFavorite(songId)
      favoriteIds.value.delete(songId)
      return
    }

    await addFavorite(songId)
    favoriteIds.value.add(songId)
  } catch {
    // Silently ignore missing auth session when toggling favorites.
  }
}

onMounted(async () => {
  try {
    const [artistResponse, favoritesResponse] = await Promise.all([
      getArtist(route.params.id),
      getFavorites().catch(() => ({ songs: [] })),
    ])

    artist.value = artistResponse.artist
    if (favoritesResponse.songs?.length) {
      favoriteIds.value = new Set(favoritesResponse.songs.map((song) => Number(song.id)))
    }
  } catch (requestError) {
    error.value = requestError.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="artist-profile">
    <p v-if="loading" class="api-message">Cargando perfil...</p>
    <p v-else-if="error" class="api-message api-error">{{ error }}</p>
    <template v-else>
      <header class="artist-profile-hero">
        <img v-if="artist.image" :src="artist.image" :alt="artist.name" class="artist-profile-image">
        <div v-else class="artist-profile-placeholder">♪</div>
        <div>
          <span class="eyebrow">Artista</span>
          <h1>{{ artist.name }}</h1>
          <p>{{ artist.bio || 'Canciones, álbumes y favoritos de este artista.' }}</p>
        </div>
      </header>

      <div class="section-heading"><h2>Canciones de {{ artist.name }}</h2><span>{{ artist.songs.length }} temas</span></div>
      <div class="song-grid" :class="{ 'is-expanded': showAllSongs }">
        <article v-for="song in visibleSongs" :key="song.id" class="song-card" @click="playSong(song)" @dblclick="playSong(song)">
          <img v-if="song.image" :src="song.image" :alt="song.title" class="song-cover">
          <div v-else class="song-art">♪</div>
          <span class="tag">CANCIÓN</span><strong>{{ song.title }}</strong><small>{{ song.artist }}</small>
          <div class="song-actions"><button type="button" class="queue-mini-button" aria-label="Añadir a la cola" title="Añadir a la cola" @click.stop="queueSong(song)">≡+</button><button type="button" @click.stop="toggleFavorite(song)" :aria-label="isFavorite(song.id) ? 'Quitar de favoritos' : 'Añadir a favoritos'">{{ isFavorite(song.id) ? '♥' : '♡' }}</button></div>
        </article>
      </div>
      <button v-if="artist.songs.length > 6" type="button" class="secondary-button" @click="showAllSongs = !showAllSongs">{{ showAllSongs ? 'Ver menos' : 'Ver más canciones' }}</button>

      <div class="section-heading"><h2>Álbumes</h2><span>{{ artist.albums.length }} álbumes</span></div>
      <div class="album-grid artist-albums">
        <RouterLink v-for="album in artist.albums" :key="album.id" :to="`/album/${album.id}`" class="album-card">
          <img :src="album.image" :alt="album.title"><span class="eyebrow">ÁLBUM</span><strong>{{ album.title }}</strong><small>{{ album.artist }}</small>
        </RouterLink>
      </div>
    </template>
  </section>
</template>
