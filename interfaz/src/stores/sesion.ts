import { defineStore } from "pinia"
import api from '@/plugins/axios'

export const useSesionStore = defineStore("sesion", {
	state: () => ({
		urlBase: "index.php",
		token: localStorage.getItem("token") || null,
		usuario: JSON.parse(localStorage.getItem("usuario")) || null
	}),	
	getters: {
		isLoggedIn: (state) => !!state.token
	},
	actions: {
		async iniciar_sesion(datos) {
			try {
				let res = await api.post(`${this.urlBase}/sesion/login`, datos)
				
				if (res.data.exito == 1) {
					this.token = res.data.token
					this.usuario = res.data.usuario

					localStorage.setItem("token", this.token)
					localStorage.setItem("usuario", JSON.stringify(this.usuario))
				}

				return res.data
			} catch (e) {
				return false
			}
		},
		async cerrar_sesion() {
			try {
				let res = await api.post(`${this.urlBase}/sesion/cerrar_sesion`)

				if (res.data.exito) {
					this.token = null
					this.usuario = null

					localStorage.removeItem("token")
					localStorage.removeItem("usuario")

					return true
				}
			} catch (e) {
				return false
			}
		}
	}
})