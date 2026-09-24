<script setup>
import { onMounted, ref } from 'vue'
import { getArtists } from '../services/api'

const artists = ref([])

onMounted(async () => {
	try {
		const response = await getArtists(50)
		if (response.artists?.length) artists.value = response.artists
	} catch {
		artists.value = []
	}
})
</script>
<template><section class="simple-view"><span class="eyebrow">Explora</span><h1>Artistas</h1><p>Descubre perfiles, canciones destacadas y álbumes completos.</p><div v-if="artists.length" class="artist-grid"><RouterLink v-for="artist in artists" :key="artist.id || artist.name" :to="artist.id ? `/artista/${artist.id}` : '/artistas'" class="artist-card"><img v-if="artist.image" :src="artist.image" :alt="artist.name"><div v-else class="artist-placeholder">♪</div><strong>{{ artist.name }}</strong></RouterLink></div></section></template>