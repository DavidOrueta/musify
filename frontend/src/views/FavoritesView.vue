<script setup>
import { onMounted, ref } from 'vue'
import { getFavorites, removeFavorite } from '../services/api'

const favorites = ref([])
const loadError = ref('')

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
  } catch {
    // Ignore errors so the list remains stable when the user is not authenticated.
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

    <div v-if="favorites.length" class="song-grid">
      <article v-for="song in favorites" :key="song.id" class="song-card">
        <img v-if="song.image" :src="song.image" :alt="song.title" class="song-cover">
        <div v-else class="song-art">♪</div>
        <span class="tag">CANCIÓN</span>
        <strong>{{ song.title }}</strong>
        <small>{{ song.artist }}</small>
        <button type="button" aria-label="Quitar de favoritos" @click="toggleFavorite(song)">♥</button>
      </article>
    </div>

    <div v-else class="empty-state">♡<strong>Aún no hay favoritas</strong></div>
  </section>
</template>