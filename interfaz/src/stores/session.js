import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import api from '../services/api'

const readStoredUser = () => {
  try {
    return JSON.parse(localStorage.getItem('usuario'))
  } catch {
    localStorage.removeItem('usuario')
    return null
  }
}

export const useSessionStore = defineStore('session', () => {
  const token = ref(localStorage.getItem('token'))
  const user = ref(readStoredUser())
  const isAuthenticated = computed(() => Boolean(token.value))

  const persistSession = (sessionToken, sessionUser) => {
    token.value = sessionToken
    user.value = sessionUser
    localStorage.setItem('token', sessionToken)
    localStorage.setItem('usuario', JSON.stringify(sessionUser))
  }

  const clearSession = () => {
    token.value = null
    user.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('usuario')
  }

  const login = async (credentials) => {
    try {
      const { data } = await api.post('index.php/sesion/login', credentials)

      if (data.exito === 1) persistSession(data.token, data.usuario)
      return data
    } catch (error) {
      return { exito: 0, mensaje: error.message || 'No se pudo iniciar sesión.' }
    }
  }

  const logout = async () => {
    try {
      await api.post('index.php/sesion/cerrar_sesion')
    } finally {
      clearSession()
    }
  }

  return { token, user, isAuthenticated, login, logout, clearSession }
})
