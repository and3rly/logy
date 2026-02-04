import axios from "axios"
import router from "@/router"

const api = axios.create({
	baseURL: import.meta.env.VITE_API_BASE_URL
})

api.interceptors.request.use(config => {
	const token = localStorage.getItem("token")
	if (token) {
		config.headers.Authorization = `Bearer ${token}`
	}

	return config
}, error => Promise.reject(e))

api.interceptors.response.use(res => {
	return res
}, error => {

	if (!error.response) {
		return Promise.reject({
			estado: 0,
			mensaje: "No se pudo conetar con el servidor"
		})
	}

	const { status, message } = error.response

	switch (status) {
		case 401:
			localStorage.removeItem("token")
      		localStorage.removeItem("usuario")

      		router.push({ name: "Login" })

			return Promise.reject({
				estado: status,
				mensaje: "Tu sesión ha expirado. Inicia sesión nuevamente."
			}) 
		case 500:
			return Promise.reject({
				estado: status,
				mensaje: 'Error interno del servidor'
			})
		default: 
			return Promise.reject({
				estado: status,
				mensaje: data?.message || 'Ocurrió un error inesperado'
			})
	}
})

export default api;