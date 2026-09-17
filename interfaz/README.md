# Logy Admin

Plantilla administrativa moderna construida con Vue 3, Vite, Vue Router, Pinia, Axios y Tailwind CSS 4. Lucide se utiliza en controles generales y Font Awesome interpreta los iconos del menú guardados en la base de datos.

## Ejecutar en desarrollo

```bash
npm install
npm run dev
```

## Compilar para producción

```bash
npm run build
```

La URL base de la futura API REST se configura con `VITE_API_BASE_URL`. Copia `.env.example` como `.env` y ajusta el valor para tu backend.

## Apariencia

Toda la interfaz actual utiliza utilidades de Tailwind: layout, login, vistas de ejemplo y mantenimiento de Monedas. Bootstrap fue retirado. El tema comparte las variables `surface`, `soft`, `canvas`, `ink`, `muted`, `line` y `accent` en `src/assets/css/main.css`. Las nuevas pantallas deben usar estas utilidades para mantener ambos temas, conservar los nombres originales de archivos (por ejemplo, `Principal.vue` y `Form.vue`) y llamar a la instancia compartida de Axios directamente desde las vistas.

La integración con Vite sigue la [documentación oficial de Tailwind](https://tailwindcss.com/docs/installation/using-vite). No requiere generar una hoja de estilos por módulo. Los desplegables usan elementos nativos `details` y el formulario de Monedas utiliza `dialog`, con comportamiento controlado por Vue.

El botón de sol/luna del encabezado permite cambiar toda la plantilla entre modo claro y oscuro. Desde el mismo menú se puede elegir el color de la opción activa del sidebar. Ambas preferencias se guardan en `localStorage`.

Los colores disponibles se administran en `src/components/ui/AppearanceMenu.vue` y el color inicial mediante la variable CSS `--menu-accent` de `src/assets/css/main.css`.

## Autenticación

La ruta `/login` utiliza el flujo existente del backend (`index.php/sesion/login`). Las rutas administrativas requieren una sesión válida, el token se agrega automáticamente a las solicitudes y la opción **Cerrar sesión** finaliza la sesión mediante `index.php/sesion/cerrar_sesion`.

Durante el desarrollo, Vite redirige las solicitudes `/api` a `http://logy.local/`, igual que la interfaz anterior. Este destino se puede cambiar en `vite.config.js`.

## Menú dinámico

Después de autenticar al usuario, el sidebar obtiene módulos y opciones desde `index.php/modulo/buscar`. Los nombres, rutas, jerarquías e iconos provienen directamente del backend. Las clases almacenadas en el campo `icono` se renderizan mediante Font Awesome, sin asignaciones de iconos dentro del frontend.

## Estructura principal

```text
src/
├── assets/css/main.css
├── components/
│   ├── layout/
│   │   ├── Footer.vue
│   │   ├── Header.vue
│   │   ├── Sidebar.vue
│   │   └── SidebarMenuItem.vue
│   └── ui/
│       ├── BaseCard.vue
│       ├── BaseTable.vue
│       └── Breadcrumb.vue
├── layouts/MainLayout.vue
├── router/index.js
├── services/api.js
├── stores/app.js
├── stores/menu.js
├── stores/session.js
├── views/
│   ├── DashboardView.vue
│   ├── LoginView.vue
│   ├── OperationsView.vue
│   ├── ReportsView.vue
│   ├── SettingsView.vue
│   ├── UsersView.vue
│   └── mnt/moneda/
│       ├── Principal.vue
│       └── Form.vue
├── App.vue
└── main.js
```
