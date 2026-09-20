const apiHost = typeof window !== 'undefined' ? window.location.hostname : 'localhost'
const apiBase = import.meta.env.VITE_API_URL || `http://${apiHost}/musify/backend/api`

async function request(endpoint, options = {}) {
  const response = await fetch(`${apiBase}/${endpoint}`, {
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options,
  })
  const payload = await response.json().catch(() => ({}))
  if (!response.ok) {
    throw new Error(payload.error || 'No se pudo completar la petición.')
  }
  return payload
}

export const getAlbums = (limit = 50) => request(`catalog.php?resource=albums&limit=${limit}`)
export const getArtists = (limit = 50) => request(`catalog.php?resource=artists&limit=${limit}`)
export const getArtist = (id) => request(`catalog.php?resource=artist&id=${id}`)
export const getSongs = (search = '', limit = 50) => request(`catalog.php?resource=songs&limit=${limit}&search=${encodeURIComponent(search)}`)
export const getAlbum = (id) => request(`catalog.php?resource=album&id=${id}`)
export const searchCatalog = (query) => request(`catalog.php?resource=search&q=${encodeURIComponent(query)}`)

export const getCurrentUser = () => request('auth.php?action=me')
export const login = (credentials) => request('auth.php?action=login', { method: 'POST', body: JSON.stringify(credentials) })
export const register = (credentials) => request('auth.php?action=register', { method: 'POST', body: JSON.stringify(credentials) })
export const logout = () => request('auth.php?action=logout', { method: 'POST' })

export const getFavorites = () => request('favorites.php')
export const addFavorite = (songId) => request(`favorites.php?song_id=${songId}`, { method: 'POST' })
export const removeFavorite = (songId) => request(`favorites.php?song_id=${songId}`, { method: 'DELETE' })

export const getPlaylists = () => request('playlists.php')
export const getPlaylist = (id) => request(`playlists.php?id=${id}`)
export const createPlaylist = (playlist) => request('playlists.php', { method: 'POST', body: JSON.stringify(playlist) })
export const updatePlaylist = (id, playlist) => request(`playlists.php?id=${id}`, { method: 'PUT', body: JSON.stringify(playlist) })
export const deletePlaylist = (id) => request(`playlists.php?id=${id}`, { method: 'DELETE' })
export const addSongToPlaylist = (playlistId, songId) => request(`playlists.php?id=${playlistId}&action=add-song`, { method: 'POST', body: JSON.stringify({ song_id: songId }) })
export const removeSongFromPlaylist = (playlistId, songId) => request(`playlists.php?id=${playlistId}&action=remove-song&song_id=${songId}`, { method: 'DELETE' })
