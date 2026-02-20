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
