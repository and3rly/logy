import { defineStore } from "pinia"
import api from '@/plugins/axios'

export const useSesionStore = defineStore("sesion", {
	state: () => ({
		urlBase: "index.php"
	}),	
	getters: {
		isLoggedIn: (state) => false
	},
	actions: {
		async iniciar_sesion(datos) {
			try {
				let res = await api.post(`${this.urlBase}/sesion/iniciar_sesion`, datos)
				console.log(res)
			} catch (e) {
				return false
			}
		}
	}
})