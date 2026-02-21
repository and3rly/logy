import { createRouter, createWebHistory } from "vue-router";
import { useSesionStore } from "@/stores/sesion";

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: "/login",
      name: "Login",
      component: () => import("../views/sesion/Login.vue"),
      meta: {titulo: "Iniciar sesión"}
    },
		{ 
      path: "/:pathMatch(.*)*", 
      component: () => import("../views/PageError.vue") 
    },
    {
      path: "/",
      component: () => import("../views/Principal.vue"),
      children: [
        {
          path: "", 
          name: "home",
          component: () => import("../views/Home.vue"),
          meta: {titulo: "Inicio"}
        },
        {
          path: "/usuario",
          name: "Usuario",
          component: () => import("../views/usuario/Principal.vue"),
          meta: {titulo: "Usuario"}
        },
        {
          path: "/sucursal",
          name: "Sucursal",
          component: () => import("../views/mnt/sucursal/Principal.vue"),
          meta: {titulo: "Sucursal"}
        },
        {
          path: "/marca",
          name: "Marca",
          component: () => import("../views/mnt/marca/Principal.vue"),
          meta: {titulo: "Marca"}
        },
        {
          path: "/unidad_medida",
          name: "UnidadMedida",
          component: () => import("../views/mnt/um/Principal.vue"),
          meta: {titulo: "Unidad de medida"}
        },
        {
          path: "/categoria",
          name: "Categoria",
          component: () => import("../views/mnt/categoria/Principal.vue"),
          meta: {titulo: "Categoría"}
        },
        {
          path: "/producto",
          name: "Producto",
          component: () => import("../views/mnt/producto/Principal.vue"),
          meta: {titulo: "Producto"}
        }
      ]
    }
  ],
});

router.beforeEach((to, from, next) => {
  const sesion = useSesionStore();

  if (to.meta.titulo) {
    document.title = to.meta.titulo + " - Logy";
  } else {
    document.title = "Logy";
  }

  if (to.name !== "Login" && !sesion.isLoggedIn) {
    return next({ name: "Login" });
  } else if (to.name == "Login" && sesion.isLoggedIn) {
    return next({ name: "home" });
  }

  next();
});

export default router;
