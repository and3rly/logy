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
      text: "Mantenimientos",
      children: [
        {
          url: "/usuario", 
          text: "Usuarios", 
          icon: "fas fa-user" 
        },
        {
          url: "/sucursal", 
          text: "Sucursales", 
          icon: "fas fa-home" 
        },
        {
          url: "/marca", 
          text: "Marcas", 
          icon: "fas fa-tag" 
        },
        {
          url: "/unidad_medida", 
          text: "Unidades de medida", 
          icon: "fas fa-scale-balanced" 
        },
        {
          url: "/categoria", 
          text: "Categorias", 
          icon: "fas fa-folder-tree" 
        },
        {
          url: "/producto", 
          text: "Productos", 
          icon: "fas fa-boxes-stacked" 
        }
      ]
    }
	]}
});
