import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'

const normalizeUrl = (url) => {
  if (!url || url === '#' || /^javascript:/i.test(url)) return null
  return url.startsWith('/') ? url : `/${url}`
}

const normalizeItem = (item) => ({
  id: item.id,
  name: item.nombre || '',
  icon: item.icono || '',
  url: normalizeUrl(item.url),
  children: Array.isArray(item.children) ? item.children.map(normalizeItem) : [],
})

export const useMenuStore = defineStore('menu', () => {
  const items = ref([])
  const loading = ref(false)
  const error = ref('')
  const loaded = ref(false)
  let pendingRequest = null

  const hasItems = computed(() => items.value.length > 0)

  const loadMenu = async (force = false) => {
    if (loaded.value && !force) return items.value
    if (pendingRequest) return pendingRequest

    loading.value = true
    error.value = ''

    pendingRequest = api.get('index.php/modulo/buscar')
      .then(({ data }) => {
        items.value = Array.isArray(data.lista) ? data.lista.map(normalizeItem) : []
        loaded.value = true
        return items.value
      })
      .catch((requestError) => {
        error.value = requestError.message || 'No fue posible cargar el menú.'
        return []
      })
      .finally(() => {
        loading.value = false
        pendingRequest = null
      })

    return pendingRequest
  }

  const resetMenu = () => {
    items.value = []
    error.value = ''
    loaded.value = false
    pendingRequest = null
  }

  const findByPath = (path, source = items.value) => {
    for (const item of source) {
      if (item.url === path) return item
      const child = findByPath(path, item.children)
      if (child) return child
    }
    return null
  }

  return { items, loading, error, loaded, hasItems, loadMenu, resetMenu, findByPath }
})
