import { defineStore } from "pinia";
import api from '@/plugins/axios'

const urlBase = "index.php"

export const useAppSidebarMenuStore = defineStore({
  id: "appSidebarMenu",
  state: () => {
    menu: []
  },
  actions: {
    async cargarMenu() {
      try {
        let result = await api.get(`${urlBase}/modulo/buscar`);
        let res = result.data;

        if (res.lista) {
          this.menu = res.lista;
        }

      } catch (e) {
        console.log(e);
      }
    }
  }
});
