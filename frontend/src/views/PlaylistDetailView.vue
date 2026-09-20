<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { addSongToPlaylist, deletePlaylist, getPlaylist, getSongs, removeSongFromPlaylist, updatePlaylist } from '../services/api'

const route = useRoute()
const router = useRouter()
const loading = ref(true)
const query = ref('')
const error = ref('')
const editing = ref(false)
const saving = ref(false)
const showCatalog = ref(false)
const form = ref({ name: '', image: '' })
const imageName = ref('')
const playlist = ref({ id: Number(route.params.id), name: 'Mi lista', image: '', songs: [] })
const catalog = ref([])
const addingSongIds = ref(new Set())
let catalogTimer

const availableSongs = computed(() => {
  const existing = new Set(playlist.value.songs.map((song) => Number(song.id)))
  const term = query.value.trim().toLowerCase()
  return catalog.value.filter((song) => !existing.has(Number(song.id)) && (!term || `${song.title} ${song.artist}`.toLowerCase().includes(term)))
})

function playSong(song) {
  window.dispatchEvent(new CustomEvent('musify:play', { detail: song }))
}

function readImageFile(event) {
  const file = event.target.files?.[0]
  if (!file) {
    form.value.image = ''
    imageName.value = ''
    return
  }

  imageName.value = file.name
  const reader = new FileReader()
  reader.onload = () => {
    form.value.image = String(reader.result || '')
  }
  reader.readAsDataURL(file)
}

function startEdit() {
  editing.value = true
  form.value = {
    name: playlist.value.name,
    image: playlist.value.image || '',
  }
  imageName.value = ''
  error.value = ''
}

async function savePlaylist() {
  const name = form.value.name.trim()
  if (!name) {
    error.value = 'Escribe un nombre para la lista.'
    return
  }

  saving.value = true
  error.value = ''

  try {
    const response = await updatePlaylist(playlist.value.id, {
      name,
      image: form.value.image || null,
    })
    playlist.value = {
      ...playlist.value,
      name: response.playlist.name,
      image: response.playlist.image || '',
    }
    editing.value = false
  } catch (requestError) {
    error.value = requestError.message
  } finally {
    saving.value = false
  }
}

async function deleteCurrentPlaylist() {
  if (!confirm('¿Seguro que quieres eliminar esta lista?')) return

  try {
    await deletePlaylist(playlist.value.id)
    router.push('/listas')
  } catch (requestError) {
    error.value = requestError.message
  }
}

async function loadPlaylist() {
  try {
    const [playlistResponse, songsResponse] = await Promise.all([
      getPlaylist(route.params.id),
      getSongs('', 50),
    ])
    playlist.value = playlistResponse.playlist
    catalog.value = songsResponse.songs || []
  } catch (requestError) {
    if (requestError.message !== 'Necesitas iniciar sesión.') error.value = requestError.message
    playlist.value = { ...playlist.value, songs: [] }
  } finally {
    loading.value = false
  }
}

async function addSong(song) {
  const songId = Number(song.id)
  if (addingSongIds.value.has(songId)) return
  addingSongIds.value.add(songId)
  addingSongIds.value = new Set(addingSongIds.value)
  try {
    await addSongToPlaylist(playlist.value.id, songId)
    playlist.value.songs.push(song)
  } catch (requestError) {
    error.value = requestError.message
  } finally {
    addingSongIds.value.delete(songId)
    addingSongIds.value = new Set(addingSongIds.value)
  }
}

async function removeSong(song) {
  try {
    await removeSongFromPlaylist(playlist.value.id, song.id)
    playlist.value.songs = playlist.value.songs.filter((item) => item.id !== song.id)
  } catch (requestError) {
    error.value = requestError.message
  }
}

onMounted(loadPlaylist)

watch(query, () => {
  clearTimeout(catalogTimer)
  catalogTimer = setTimeout(async () => {
    try {
      const response = await getSongs(query.value.trim(), 50)
      catalog.value = response.songs || []
    } catch (requestError) {
      error.value = requestError.message
    }
  }, 250)
})
</script>

<template>
  <section class="playlist-detail">
    <p v-if="loading" class="api-message">Cargando lista...</p>
    <template v-else>
      <header v-if="!editing" class="playlist-detail-header">
        <img v-if="playlist.image" :src="playlist.image" :alt="playlist.name" class="playlist-detail-cover">
        <div v-else class="playlist-detail-cover playlist-detail-placeholder">♪</div>
        <div class="playlist-detail-info">
          <span class="eyebrow">Lista</span>
          <h1>{{ playlist.name }}</h1>
          <div class="playlist-actions">
            <button type="button" title="Editar lista" aria-label="Editar lista" @click="startEdit"><span aria-hidden="true">✎</span> Editar</button>
            <button type="button" class="danger-button" title="Eliminar lista" aria-label="Eliminar lista" @click="deleteCurrentPlaylist"><span aria-hidden="true">×</span> Borrar</button>
          </div>
        </div>
      </header>

      <form v-else class="playlist-edit-form" @submit.prevent="savePlaylist">
        <strong>Editar lista</strong>
        <input v-model="form.name" type="text" placeholder="Nombre de la lista" maxlength="120">
        <input id="edit-playlist-image" class="visually-hidden" type="file" accept="image/*" @change="readImageFile">
        <label class="file-picker" for="edit-playlist-image"><span>Seleccionar imagen</span><small>{{ imageName || 'Mantener imagen actual' }}</small></label>
        <p v-if="error" class="playlist-error">{{ error }}</p>
        <div class="playlist-form-actions">
          <button type="submit" :disabled="saving">{{ saving ? 'Guardando...' : 'Guardar' }}</button>
          <button type="button" class="secondary-button" @click="editing = false">Cancelar</button>
        </div>
      </form>

      <div class="playlist-songs-heading"><h2 class="playlist-section-title">Canciones</h2><button type="button" class="add-songs-button" @click="showCatalog = !showCatalog">{{ showCatalog ? 'Ocultar catálogo' : 'Añadir canciones' }}</button></div>
      <section v-if="showCatalog" class="playlist-catalog">
        <div class="playlist-catalog-heading"><div><span class="eyebrow">Catálogo</span><strong>Añade canciones a tu lista</strong></div><small>{{ availableSongs.length }} disponibles</small></div>
        <input v-model="query" type="search" placeholder="Busca por canción o artista">
        <div class="catalog-song-grid"><article v-for="song in availableSongs.slice(0, 17)" :key="song.id" class="catalog-song"><img :src="song.image" :alt="song.title"><span><strong>{{ song.title }}</strong><small>{{ song.artist }}</small></span><button :disabled="addingSongIds.has(Number(song.id))" @click="addSong(song)" aria-label="Añadir canción">{{ addingSongIds.has(Number(song.id)) ? '…' : '+' }}</button></article></div>
      </section>

      <p v-if="error" class="playlist-error">{{ error }}</p>
      <div v-if="playlist.songs.length" class="playlist-song-list"><article v-for="song in playlist.songs" :key="song.id" class="song-card playlist-song-row" @dblclick="playSong(song)"><img :src="song.image" :alt="song.title" class="song-cover playlist-song-cover"><strong>{{ song.title }}</strong><small>{{ song.artist }}</small><button @click.stop="removeSong(song)" aria-label="Quitar canción">×</button></article></div><div v-else class="playlist-empty-songs"><span>♪</span><strong>Aún no hay canciones</strong><small>Pulsa “Añadir canciones” para llenar tu playlist.</small></div>
    </template>
  </section>
</template>
