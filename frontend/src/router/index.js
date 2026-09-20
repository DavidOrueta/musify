import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import FavoritesView from '../views/FavoritesView.vue'
import PlaylistsView from '../views/PlaylistsView.vue'
import ArtistsView from '../views/ArtistsView.vue'
import AlbumView from '../views/AlbumView.vue'
import ArtistProfileView from '../views/ArtistProfileView.vue'
import PlaylistDetailView from '../views/PlaylistDetailView.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/favoritos', name: 'favorites', component: FavoritesView },
    { path: '/listas', name: 'playlists', component: PlaylistsView },
    { path: '/lista/:id', name: 'playlist', component: PlaylistDetailView },
    { path: '/artistas', name: 'artists', component: ArtistsView },
    { path: '/artista/:id', name: 'artist', component: ArtistProfileView },
    { path: '/album/:id', name: 'album', component: AlbumView },
  ],
})