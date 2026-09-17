import { createRouter, createWebHistory } from 'vue-router'
import MainLayout from '../layouts/MainLayout.vue'
import { useSessionStore } from '../stores/session'

const routes = [
  {
    path: '/login',
    name: 'login',
    component: () => import('../views/LoginView.vue'),
    meta: { title: 'Iniciar sesión', guestOnly: true },
  },
  {
    path: '/',
    component: MainLayout,
    redirect: '/dashboard',
    meta: { requiresAuth: true },
    children: [
      { path: 'venta', name: 'Venta', component: () => import('../views/venta/Lista.vue'), meta: { title: 'Ventas' } },
      { path: 'compra', name: 'Compra', component: () => import('../views/compra/Principal.vue'), meta: { title: 'Órdenes de compra' } },
      { path: 'existencia', name: 'Existencia', component: () => import('../views/inventario/existencia/Principal.vue'), meta: { title: 'Existencias' } },
      { path: 'kardex', name: 'Kardex', component: () => import('../views/inventario/kardex/Principal.vue'), meta: { title: 'Kardex' } },
      { path: 'ajuste', name: 'AjusteInventario', component: () => import('../views/inventario/ajuste/Principal.vue'), meta: { title: 'Ajustes de inventario' } },
      { path: 'inventario', name: 'InventarioInicial', component: () => import('../views/inventario/inicial/Principal.vue'), meta: { title: 'Inventario inicial' } },
      { path: 'proveedor', name: 'Proveedor', component: () => import('../views/mnt/proveedor/Principal.vue'), meta: { title: 'Proveedores' } },
      { path: 'cliente', name: 'Cliente', component: () => import('../views/mnt/cliente/Principal.vue'), meta: { title: 'Clientes' } },
      { path: 'cuenta-cobrar', name: 'CuentaCobrar', component: () => import('../views/cxc/Principal.vue'), meta: { title: 'Cuentas por cobrar' } },
      { path: 'cuenta-pagar', name: 'CuentaPagar', component: () => import('../views/cxp/Principal.vue'), meta: { title: 'Cuentas por pagar' } },
      { path: 'sucursal', name: 'Sucursal', component: () => import('../views/mnt/sucursal/Principal.vue'), meta: { title: 'Sucursales' } },
      { path: 'usuario', name: 'Usuario', component: () => import('../views/usuario/Principal.vue'), meta: { title: 'Usuarios' } },
      { path: 'rol', name: 'Rol', component: () => import('../views/mnt/rol/Principal.vue'), meta: { title: 'Roles', breadcrumb: 'Roles' } },
      { path: 'producto', name: 'Producto', component: () => import('../views/mnt/producto/Principal.vue'), meta: { title: 'Productos' } },
      { path: 'menu', name: 'Menu', component: () => import('../views/menu/Principal.vue'), meta: { title: 'Menú', breadcrumb: 'Menú' } },
      {
        path: 'unidad_medida',
        name: 'UnidadMedida',
        component: () => import('../views/mnt/um/Principal.vue'),
        meta: { title: 'Unidades de medida', breadcrumb: 'Unidades de medida' },
      },
      {
        path: 'categoria',
        name: 'Categoria',
        component: () => import('../views/mnt/categoria/Principal.vue'),
        meta: { title: 'Categorías', breadcrumb: 'Categorías' },
      },
      {
        path: 'marca',
        name: 'Marca',
        component: () => import('../views/mnt/marca/Principal.vue'),
        meta: { title: 'Marcas', breadcrumb: 'Marcas' },
      },
      {
        path: 'moneda',
        name: 'Moneda',
        component: () => import('../views/mnt/moneda/Principal.vue'),
        meta: { title: 'Monedas', breadcrumb: 'Monedas' },
      },
      {
        path: 'dashboard',
        name: 'dashboard',
        component: () => import('../views/DashboardView.vue'),
        meta: { title: 'Dashboard', breadcrumb: 'Resumen' },
      },
      {
        path: 'operations',
        name: 'operations',
        component: () => import('../views/OperationsView.vue'),
        meta: { title: 'Operaciones', breadcrumb: 'Operaciones' },
      },
      {
        path: 'reports',
        name: 'reports',
        component: () => import('../views/ReportsView.vue'),
        meta: { title: 'Reportes', breadcrumb: 'Reportes' },
      },
      {
        path: 'users',
        name: 'users',
        redirect: '/usuario',
        meta: { title: 'Usuarios', breadcrumb: 'Usuarios' },
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('../views/SettingsView.vue'),
        meta: { title: 'Configuración', breadcrumb: 'Configuración' },
      },
      {
        path: ':pathMatch(.*)*',
        name: 'dynamic-module',
        component: () => import('../views/DynamicModuleView.vue'),
        meta: { title: 'Módulo' },
      },
    ],
  },
  { path: '/:pathMatch(.*)*', redirect: '/dashboard' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

router.beforeEach((to) => {
  const sessionStore = useSessionStore()

  if (to.matched.some((record) => record.meta.requiresAuth) && !sessionStore.isAuthenticated) {
    return { name: 'login', query: { redirect: to.fullPath } }
  }

  if (to.meta.guestOnly && sessionStore.isAuthenticated) return { name: 'dashboard' }
})

router.afterEach((to) => {
  document.title = `${to.meta.title || 'Administración'} · Logy Admin`
})

export default router
