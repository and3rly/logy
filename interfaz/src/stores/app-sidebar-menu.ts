import { defineStore } from "pinia";

export const useAppSidebarMenuStore = defineStore({
  id: "appSidebarMenu",
  state: () => {
    return [
    {
			text: 'Menú',
			is_header: true
		},
    {
      url: "/mante/",
      icon: "fa fa-cog",
      text: "Mantenimiento",
      children: [
        {
          url: "/usuario", 
          text: "Usuario", 
          icon: "fas fa-user" 
        },
        {
          url: "/sucursal", 
          text: "Sucursal", 
          icon: "fas fa-home" 
        },
      ]
    }
	]}
});
