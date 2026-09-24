<script setup>
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { addFavorite, getAlbum, getFavorites, removeFavorite } from '../services/api'

const route = useRoute()
const loading = ref(true)
const error = ref('')
const favoriteIds = ref(new Set())
const favoriteError = ref('')
const album = ref({ title: 'Álbum', artist: '', image: '', songs: [] })

function isFavorite(songId) {
	return favoriteIds.value.has(Number(songId))
}

function playSong(song) {
	const track = {
		...song,
		artist: song.artist || album.value.artist,
		image: song.image || album.value.image || 'https://placehold.co/600x600/10251b/8fe7c2?text=♪',
		audioUrl: song.audioUrl || 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
		duration: Number(song.duration) || 209,
	}
	window.dispatchEvent(new CustomEvent('musify:play', { detail: track }))
}

function queueSong(song) {
	const track = {
		...song,
		artist: song.artist || album.value.artist,
		image: song.image || album.value.image || 'https://placehold.co/600x600/10251b/8fe7c2?text=♪',
		audioUrl: song.audioUrl || 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3',
		duration: Number(song.duration) || 209,
	}
	window.dispatchEvent(new CustomEvent('musify:queue', { detail: track }))
}

async function toggleFavorite(song) {
	const songId = Number(song.id)
	if (!Number.isInteger(songId) || songId < 1) return

	favoriteError.value = ''
	try {
		if (isFavorite(songId)) {
			await removeFavorite(songId)
			favoriteIds.value.delete(songId)
		} else {
			await addFavorite(songId)
			favoriteIds.value.add(songId)
		}
		favoriteIds.value = new Set(favoriteIds.value)
	} catch (requestError) {
		favoriteError.value = requestError.message
	}
}

async function loadAlbum() {
	loading.value = true
	error.value = ''
	try {
		const response = await getAlbum(route.params.id)
		album.value = response.album
		const favoritesResponse = await getFavorites().catch(() => ({ songs: [] }))
		favoriteIds.value = new Set((favoritesResponse.songs || []).map((song) => Number(song.id)))
	} catch (requestError) {
		error.value = requestError.message
	} finally {
		loading.value = false
	}
}

onMounted(loadAlbum)
watch(() => route.params.id, loadAlbum)
</script>
<template>
	<section class="album-view">
		<p v-if="loading" class="api-message">Cargando álbum...</p>
		<p v-else-if="error" class="api-message api-error">{{ error }}</p>
		<template v-else>
			<div class="album-hero">
				<img :src="album.image" :alt="album.title" class="album-cover album-cover-image">
				<div><span class="eyebrow">Álbum</span><h1>{{ album.title }}</h1><p>{{ album.artist }}</p><small>{{ album.songs.length }} canciones</small></div>
			</div>
			<div class="section-heading"><h2>Canciones del álbum</h2><span>{{ album.songs.length }} temas</span></div>
			<div class="song-grid">
				<article v-for="song in album.songs" :key="song.id" class="song-card" @click="playSong(song)" @dblclick="playSong(song)">
					<img :src="song.image || album.image || 'https://placehold.co/600x600/10251b/8fe7c2?text=♪'" :alt="song.title" class="song-cover">
					<span class="tag">CANCIÓN</span><strong>{{ song.title }}</strong><small>{{ song.artist }}</small>
					<div class="song-actions"><button type="button" class="queue-mini-button" aria-label="Añadir a la cola" title="Añadir a la cola" @click.stop="queueSong(song)">≡+</button><button type="button" :aria-label="isFavorite(song.id) ? 'Quitar de favoritos' : 'Añadir a favoritos'" @click.stop="toggleFavorite(song)">{{ isFavorite(song.id) ? '♥' : '♡' }}</button></div>
				</article>
			</div>
			<p v-if="favoriteError" class="api-message api-error">{{ favoriteError }}</p>
		</template>
	</section>
</template>