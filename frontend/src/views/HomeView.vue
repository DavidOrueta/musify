<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { addFavorite, getAlbums, getFavorites, getSongs, removeFavorite } from '../services/api'

const albums = ref([
  { id: 20, title: 'La Joia', artist: 'Bad Gyal', image: 'https://i.scdn.co/image/ab67616d0000b27381909f39477fbb311c77ef35' },
  { id: 2, title: 'Un Verano Sin Ti', artist: 'Bad Bunny', image: 'https://image-cdn-fa.spotifycdn.com/image/ab67616d00001e0249d694203245f241a1bcaa72' },
  { id: 7, title: 'Motomami', artist: 'Rosalía', image: 'https://image-cdn-ak.spotifycdn.com/image/ab67616d00001e02ac8367a27c0eb7195dc3a58d' },
  { id: 22, title: 'Warm Up', artist: 'Bad Gyal', image: 'https://i.scdn.co/image/ab67616d0000b2737a0b5c826689bee750c3605f' },
  { id: 1, title: 'After Hours', artist: 'The Weeknd', image: 'https://image-cdn-fa.spotifycdn.com/image/ab67616d00001e028863bc11d2aa12b54f5aeb36' },
  { id: 6, title: 'El Mal Querer', artist: 'Rosalía', image: 'https://image-cdn-fa.spotifycdn.com/image/ab67616d00001e02b115d8632e69edce1a1da7d8' },
])

const songs = ref([
  { id: 'carousel', title: 'CAROUSEL', artist: 'Travis Scott', image: '' },
  { id: 'screw', title: 'R.I.P. SCREW', artist: 'Travis Scott', image: '' },
  { id: 'god', title: 'STOP TRYING TO BE GOD', artist: 'Travis Scott', image: '' },
  { id: 'bystanders', title: 'NO BYSTANDERS', artist: 'Travis Scott', image: '' },
  { id: 'moscu', title: 'Moscu Mule', artist: 'Bad Bunny', image: '' },
  { id: 'skeletons', title: 'SKELETONS', artist: 'Travis Scott', image: '' },
])

const favoriteIds = ref(new Set())
const showAllSongs = ref(false)

function diversifySongs(items, limit = 12) {
  const groups = new Map()
  items.forEach((song) => {
    const key = song.artist_id || song.artist
    if (!groups.has(key)) groups.set(key, [])
    groups.get(key).push(song)
  })

  const selected = []
  const usedAlbums = new Set()
  const queues = [...groups.values()]
  while (selected.length < limit && queues.length) {
    for (let index = queues.length - 1; index >= 0; index -= 1) {
      const queue = queues[index]
      const nextIndex = queue.findIndex((song) => !usedAlbums.has(song.album_id))
      const song = queue.splice(nextIndex < 0 ? 0 : nextIndex, 1)[0]
      if (song) {
        selected.push(song)
        if (song.album_id) usedAlbums.add(song.album_id)
      }
      if (!queue.length) queues.splice(index, 1)
      if (selected.length >= limit) break
    }
  }
  return selected
}

const visibleSongs = computed(() => (showAllSongs.value ? songs.value : songs.value.slice(0, 6)))

function isFavorite(songId) {
  return favoriteIds.value.has(Number(songId))
}

function playSong(song) {
  window.dispatchEvent(new CustomEvent('musify:play', { detail: song }))
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
    // Silent fail to avoid breaking the UI when the session is not authenticated.
  }
}

onMounted(async () => {
  try {
    const [albumsResponse, songsResponse, favoritesResponse] = await Promise.all([
      getAlbums(21),
      getSongs('', 100),
      getFavorites().catch(() => ({ songs: [] })),
    ])

    if (albumsResponse.albums?.length) albums.value = albumsResponse.albums
    if (songsResponse.songs?.length) songs.value = diversifySongs(songsResponse.songs)
    if (favoritesResponse.songs?.length) {
      favoriteIds.value = new Set(favoritesResponse.songs.map((song) => Number(song.id)))
    }
  } catch {
    // Keep the recovered visual sample available until MySQL is restored.
  }
})
</script>

<template>
  <section class="home-view">
    <div class="hero-panel"><div><span class="eyebrow">Musify</span><h1>Tu música favorita</h1><p>Descubre artistas, álbumes nuevos y playlists creadas para tu día.</p><div class="hero-actions"><RouterLink class="primary-button" to="/artistas">Explorar</RouterLink><RouterLink class="secondary-button" to="/listas">Tus listas</RouterLink></div></div><div class="hero-disc">♪</div></div>
    <div class="section-heading"><h2>Álbumes recientes</h2><RouterLink to="/artistas">Ver todo</RouterLink></div>
    <div class="album-grid"><RouterLink v-for="album in albums" :key="album.id" :to="`/album/${album.id}`" class="album-card"><img :src="album.image" :alt="album.title"><span class="eyebrow">ÁLBUM</span><strong>{{ album.title }}</strong><small>{{ album.artist }}</small></RouterLink></div>
    <div class="section-heading"><h2>Canciones populares</h2><span>{{ songs.length }} temas</span></div>
    <div class="song-grid"><article v-for="song in visibleSongs" :key="song.id" class="song-card" @dblclick="playSong(song)"><img v-if="song.image" :src="song.image" :alt="song.title" class="song-cover"><div v-else class="song-art">♪</div><span class="tag">CANCIÓN</span><strong>{{ song.title }}</strong><small>{{ song.artist }}</small><button type="button" @click.stop="toggleFavorite(song)" :aria-label="isFavorite(song.id) ? 'Quitar de favoritos' : 'Añadir a favoritos'">{{ isFavorite(song.id) ? '♥' : '♡' }}</button></article></div>
    <button v-if="songs.length > 6" type="button" class="secondary-button" @click="showAllSongs = !showAllSongs">{{ showAllSongs ? 'Ver menos' : 'Ver más canciones' }}</button>
  </section>
</template>