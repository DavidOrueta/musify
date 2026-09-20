<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { createPlaylist, getPlaylists } from '../services/api'

const playlists = ref([])
const showForm = ref(false)
const name = ref('')
const image = ref('')
const imageName = ref('')
const error = ref('')
const loadError = ref('')
const saving = ref(false)

function readImageFile(event) {
  const file = event.target.files?.[0]
  if (!file) {
    image.value = ''
    imageName.value = ''
    return
  }

  imageName.value = file.name
  const reader = new FileReader()
  reader.onload = () => {
    image.value = String(reader.result || '')
  }
  reader.readAsDataURL(file)
}

onMounted(async () => {
  try {
    const response = await getPlaylists()
    if (response.playlists?.length) playlists.value = response.playlists
  } catch (requestError) {
    playlists.value = []
    loadError.value = requestError.message
    if (requestError.message === 'Necesitas iniciar sesión.') window.dispatchEvent(new CustomEvent('musify:auth'))
  }
})

async function createNewPlaylist() {
  if (!name.value.trim()) {
    error.value = 'Escribe un nombre para la lista.'
    return
  }

  saving.value = true
  error.value = ''
  try {
    const response = await createPlaylist({ name: name.value.trim(), image: image.value || null })
    playlists.value.unshift(response.playlist)
    name.value = ''
    image.value = ''
    imageName.value = ''
    showForm.value = false
  } catch (requestError) {
    error.value = requestError.message
    if (requestError.message === 'Necesitas iniciar sesión.') window.dispatchEvent(new CustomEvent('musify:auth'))
  } finally {
    saving.value = false
  }
}

</script>

<template>
  <section class="simple-view">
    <span class="eyebrow">Tu música</span>
    <h1>Tus listas</h1>
    <p>Crea y organiza tus canciones favoritas.</p>
    <p v-if="loadError" class="api-message api-error">{{ loadError }}</p>

    <div class="playlist-grid">
      <div v-for="playlist in playlists" :key="playlist.id || playlist.name" class="playlist-card-shell">
        <RouterLink :to="`/lista/${playlist.id}`" class="playlist-card">
          <img :src="playlist.image || 'https://placehold.co/600x600/10251b/8fe7c2?text=♪'" :alt="playlist.name">
          <strong>{{ playlist.name }}</strong>
          <small>{{ playlist.song_count ?? 0 }} canciones</small>
        </RouterLink>

      </div>

      <button v-if="!showForm" class="new-playlist" type="button" @click="showForm = true">
        +
        <span>Nueva lista</span>
      </button>

      <form v-else class="new-playlist-form" @submit.prevent="createNewPlaylist">
        <strong>Nueva lista</strong>
        <input v-model="name" type="text" placeholder="Nombre de la lista" autofocus>
        <input id="new-playlist-image" class="visually-hidden" type="file" accept="image/*" @change="readImageFile">
        <label class="file-picker" for="new-playlist-image"><span>Seleccionar imagen</span><small>{{ imageName || 'Ninguna imagen seleccionada' }}</small></label>
        <p v-if="error">{{ error }}</p>
        <div>
          <button type="submit" :disabled="saving">{{ saving ? 'Guardando...' : 'Crear' }}</button>
          <button type="button" @click="showForm = false">Cancelar</button>
        </div>
      </form>
    </div>
  </section>
</template>
