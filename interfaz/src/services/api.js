import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api/app/',
  timeout: 15000,
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    const status = error.response?.status

    if (status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('usuario')

      if (window.location.pathname !== '/login') window.location.assign('/login')
    }

    return Promise.reject({
      status: status || 0,
      message: error.response?.data?.mensaje
        || error.response?.data?.message
        || (error.response ? 'Ocurrió un error inesperado.' : 'No se pudo conectar con el servidor.'),
      originalError: error,
    })
  },
)

export default api
