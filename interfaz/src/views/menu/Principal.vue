<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  ChevronRight,
  CircleAlert,
  ExternalLink,
  Layers3,
  LoaderCircle,
  Pencil,
  Plus,
  RefreshCw,
  Search,
  Settings2,
} from '@lucide/vue'
import RutaNavegacion from '../../components/ui/Breadcrumb.vue'
import FormularioModulo from './FormularioModulo.vue'
import FormularioOpcion from './FormularioOpcion.vue'
import api from '../../services/api'
import { useMenuStore } from '../../stores/menu'
import { toast } from 'vue3-toastify'

const menuStore = useMenuStore()
const modulos = ref([])
const seleccionado = ref(null)
const termino = ref('')
const cargando = ref(false)
const error = ref('')
const moduloEdicion = ref(null)
const opcionEdicion = ref(null)
const mostrarModulo = ref(false)
const mostrarOpcion = ref(false)

const modulosFiltrados = computed(() => {
  const busqueda = termino.value.trim().toLocaleLowerCase('es')
  if (!busqueda) return modulos.value
  return modulos.value.filter((modulo) =>
    [modulo.nombre, modulo.url].some((valor) => String(valor ?? '').toLocaleLowerCase('es').includes(busqueda)),
  )
})

const opciones = computed(() => [...(seleccionado.value?.menus || [])]
  .sort((a, b) => Number(a.orden) - Number(b.orden)))

async function cargar() {
  if (cargando.value) return
  const seleccionadoId = seleccionado.value?.id
  cargando.value = true
  error.value = ''
  try {
    const { data } = await api.get('index.php/modulo/configuracion')
    if (!Array.isArray(data.lista)) throw new Error(data.mensaje || 'No se pudo cargar el menú.')
    modulos.value = data.lista.sort((a, b) => Number(a.orden) - Number(b.orden))
    seleccionado.value = modulos.value.find((item) => String(item.id) === String(seleccionadoId))
      || modulos.value[0]
      || null
  } catch (problema) {
    error.value = problema.message || 'No se pudo cargar el menú.'
  } finally {
    cargando.value = false
  }
}

function abrirModulo(modulo = null) {
  moduloEdicion.value = modulo
  mostrarModulo.value = true
}

function abrirOpcion(opcion = null) {
  opcionEdicion.value = opcion
  mostrarOpcion.value = true
}

async function moduloGuardado(modulo) {
  const indice = modulos.value.findIndex((item) => String(item.id) === String(modulo.id))
  if (indice >= 0) modulos.value.splice(indice, 1, modulo)
  else modulos.value.push(modulo)
  modulos.value.sort((a, b) => Number(a.orden) - Number(b.orden))
  seleccionado.value = modulo
  mostrarModulo.value = false
  await menuStore.loadMenu(true)
  toast.success('Módulo guardado correctamente.')
}

async function opcionGuardada(opcion) {
  const menus = seleccionado.value.menus || (seleccionado.value.menus = [])
  const indice = menus.findIndex((item) => String(item.id) === String(opcion.id))
  if (indice >= 0) menus.splice(indice, 1, opcion)
  else menus.push(opcion)
  mostrarOpcion.value = false
  await menuStore.loadMenu(true)
  toast.success('Opción guardada correctamente.')
}

onMounted(cargar)
</script>

<template>
  <div class="maintenance-screen menu-screen">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div class="space-y-3">
        <RutaNavegacion :items="[{ label: 'Configuración' }, { label: 'Menú' }]" />
        <h2 class="flex items-center gap-2 text-xl font-semibold leading-tight tracking-tight">
          <Settings2 :size="22" class="text-accent" aria-hidden="true" /> Menú
        </h2>
      </div>
      <button type="button" class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold" @click="abrirModulo()">
        <Plus :size="17" aria-hidden="true" /> Nuevo módulo
      </button>
    </div>

    <div v-if="error" class="mb-4 flex items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900/50 dark:bg-red-950/20 dark:text-red-300">
      <span class="flex items-center gap-2"><CircleAlert :size="18" />{{ error }}</span>
      <button type="button" class="font-semibold" @click="cargar">Reintentar</button>
    </div>

    <div class="menu-layout">
      <section class="menu-card menu-modules" aria-label="Módulos">
        <header class="menu-card-header">
          <div>
            <h3>Módulos</h3>
            <span>{{ modulos.length }}</span>
          </div>
          <button type="button" class="menu-icon-button" aria-label="Actualizar módulos" :disabled="cargando" @click="cargar">
            <RefreshCw :size="16" :class="{ 'animate-spin': cargando }" />
          </button>
        </header>
        <div class="menu-search">
          <Search :size="16" aria-hidden="true" />
          <input v-model="termino" type="search" placeholder="Buscar módulo" aria-label="Buscar módulo" />
        </div>

        <div v-if="cargando && !modulos.length" class="menu-state">
          <LoaderCircle :size="22" class="animate-spin text-accent" /><span>Cargando…</span>
        </div>
        <div v-else-if="!modulosFiltrados.length" class="menu-state"><span>Sin módulos</span></div>
        <div v-else class="menu-module-list">
          <button
            v-for="modulo in modulosFiltrados"
            :key="modulo.id"
            type="button"
            class="menu-module-item"
            :class="{ active: String(seleccionado?.id) === String(modulo.id) }"
            @click="seleccionado = modulo"
          >
            <span class="menu-module-icon"><i :class="modulo.icono || 'fa-solid fa-layer-group'" aria-hidden="true"></i></span>
            <span class="min-w-0 flex-1 text-left">
              <strong>{{ modulo.nombre }}</strong>
              <small>{{ Number(modulo.detalle) === 1 ? `${modulo.menus?.length || 0} opciones` : (modulo.url || 'Acceso directo') }}</small>
            </span>
            <span v-if="Number(modulo.activo) !== 1" class="menu-inactive-dot" title="Inactivo"></span>
            <ChevronRight :size="16" class="text-muted" aria-hidden="true" />
          </button>
        </div>
      </section>

      <section class="menu-card menu-options" aria-label="Opciones de menú">
        <template v-if="seleccionado">
          <header class="menu-options-header">
            <div class="menu-selected-title">
              <span><i :class="seleccionado.icono || 'fa-solid fa-layer-group'" aria-hidden="true"></i></span>
              <div>
                <div class="flex flex-wrap items-center gap-2">
                  <h3>{{ seleccionado.nombre }}</h3>
                  <span class="menu-status" :class="Number(seleccionado.activo) === 1 ? 'active' : 'inactive'">{{ Number(seleccionado.activo) === 1 ? 'Activo' : 'Inactivo' }}</span>
                </div>
                <small>Orden {{ seleccionado.orden }}</small>
              </div>
            </div>
            <div class="menu-header-actions">
              <button type="button" class="menu-secondary-button" @click="abrirModulo(seleccionado)"><Pencil :size="15" /> Editar</button>
              <button v-if="Number(seleccionado.detalle) === 1" type="button" class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold" @click="abrirOpcion()"><Plus :size="16" /> Agregar opción</button>
            </div>
          </header>

          <div v-if="Number(seleccionado.detalle) !== 1" class="menu-direct-link">
            <span><ExternalLink :size="19" /></span>
            <div><small>Ruta directa</small><strong>{{ seleccionado.url || 'Sin ruta' }}</strong></div>
          </div>

          <template v-else>
            <div class="menu-table-head">
              <span>Opciones</span><small>{{ opciones.length }}</small>
            </div>
            <div class="overflow-x-auto">
              <table class="menu-table">
                <thead><tr><th>Orden</th><th>Opción</th><th>Ruta</th><th>Estado</th><th aria-label="Acciones"></th></tr></thead>
                <tbody>
                  <tr v-for="opcion in opciones" :key="opcion.id">
                    <td><span class="menu-order">{{ opcion.orden }}</span></td>
                    <td><div class="menu-option-name"><i :class="opcion.icono || 'fa-regular fa-circle'" aria-hidden="true"></i><strong>{{ opcion.nombre }}</strong></div></td>
                    <td><code>{{ opcion.url }}</code></td>
                    <td><span class="menu-status" :class="Number(opcion.activo) === 1 ? 'active' : 'inactive'">{{ Number(opcion.activo) === 1 ? 'Activa' : 'Inactiva' }}</span></td>
                    <td class="text-right"><button type="button" class="menu-icon-button" :aria-label="`Editar ${opcion.nombre}`" @click="abrirOpcion(opcion)"><Pencil :size="15" /></button></td>
                  </tr>
                  <tr v-if="!opciones.length"><td colspan="5" class="menu-empty">No hay opciones en este módulo.</td></tr>
                </tbody>
              </table>
            </div>
          </template>
        </template>
        <div v-else class="menu-state menu-state-large"><Layers3 :size="26" /><span>Selecciona un módulo</span></div>
      </section>
    </div>

    <FormularioModulo v-if="mostrarModulo" :registro="moduloEdicion" @cerrar="mostrarModulo = false" @guardada="moduloGuardado" />
    <FormularioOpcion v-if="mostrarOpcion && seleccionado" :registro="opcionEdicion" :modulo-id="seleccionado.id" @cerrar="mostrarOpcion = false" @guardada="opcionGuardada" />
  </div>
</template>

<style scoped>
.menu-layout{display:grid;grid-template-columns:minmax(16rem,21rem) minmax(0,1fr);gap:1rem;align-items:start}.menu-card{min-width:0;overflow:hidden;border:1px solid var(--line);border-radius:.8rem;background:var(--surface);box-shadow:0 1px 2px rgb(15 23 42/.025)}.menu-card-header,.menu-options-header{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--line);padding:.9rem 1rem}.menu-card-header>div{display:flex;align-items:center;gap:.55rem}.menu-card h3{font-size:.92rem;font-weight:700}.menu-card-header span,.menu-table-head small{display:inline-flex;min-width:1.55rem;justify-content:center;border-radius:999px;background:var(--soft);padding:.18rem .45rem;color:var(--muted);font-size:.65rem;font-weight:700}.menu-search{position:relative;margin:.75rem}.menu-search svg{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted)}.menu-search input{width:100%;min-height:2.5rem;border:1px solid var(--line);border-radius:.5rem;background:var(--surface);padding:.5rem .75rem .5rem 2.25rem;color:var(--ink);font-size:.78rem;outline:none}.menu-search input:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--menu-accent) 10%,transparent)}.menu-module-list{max-height:calc(100dvh - 17rem);overflow-y:auto;border-top:1px solid var(--line)}.menu-module-item{display:flex;width:100%;align-items:center;gap:.7rem;border-bottom:1px solid var(--line);padding:.7rem .8rem;transition:.15s}.menu-module-item:last-child{border-bottom:0}.menu-module-item:hover{background:var(--soft)}.menu-module-item.active{background:color-mix(in srgb,var(--menu-accent) 8%,var(--surface));box-shadow:inset 3px 0 var(--menu-accent)}.menu-module-icon,.menu-selected-title>span{display:grid;width:2.2rem;height:2.2rem;flex:none;place-items:center;border:1px solid color-mix(in srgb,var(--menu-accent) 14%,var(--line));border-radius:.55rem;background:color-mix(in srgb,var(--menu-accent) 7%,var(--surface));color:var(--accent);font-size:.85rem}.menu-module-item strong{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:.78rem}.menu-module-item small,.menu-selected-title small{display:block;margin-top:.15rem;overflow:hidden;color:var(--muted);font-size:.66rem;text-overflow:ellipsis;white-space:nowrap}.menu-inactive-dot{width:.42rem;height:.42rem;flex:none;border-radius:50%;background:#94a3b8}.menu-options-header{min-height:4.65rem;padding:1rem 1.15rem}.menu-selected-title{display:flex;min-width:0;align-items:center;gap:.75rem}.menu-header-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.5rem}.menu-secondary-button,.menu-icon-button{display:inline-flex;align-items:center;justify-content:center;gap:.4rem;border:1px solid var(--line);border-radius:.5rem;background:var(--surface);color:var(--ink);font-size:.74rem;font-weight:650;transition:.15s}.menu-secondary-button{min-height:2.5rem;padding:.45rem .75rem}.menu-icon-button{width:2.25rem;height:2.25rem;color:var(--muted)}.menu-secondary-button:hover,.menu-icon-button:hover{background:var(--soft);color:var(--accent)}.menu-status{display:inline-flex;align-items:center;border-radius:999px;padding:.22rem .5rem;font-size:.62rem;font-weight:700}.menu-status.active{background:rgb(5 150 105/.1);color:#047857}.menu-status.inactive{background:var(--soft);color:var(--muted)}.menu-table-head{display:flex;align-items:center;gap:.5rem;padding:.75rem 1.15rem;color:var(--ink);font-size:.75rem;font-weight:700}.menu-table{width:100%;border-collapse:collapse;text-align:left;font-size:.75rem}.menu-table th{background:color-mix(in srgb,var(--soft) 75%,var(--surface));padding:.55rem 1rem;color:var(--muted);font-size:.64rem;font-weight:700;letter-spacing:.035em;text-transform:uppercase}.menu-table td{height:2.75rem;border-top:1px solid var(--line);padding:.45rem 1rem;white-space:nowrap}.menu-table tbody tr:hover{background:color-mix(in srgb,var(--menu-accent) 3%,var(--surface))}.menu-table code{border-radius:.35rem;background:var(--soft);padding:.24rem .42rem;color:var(--muted);font-size:.68rem}.menu-order{display:inline-flex;min-width:1.65rem;justify-content:center;border-radius:.35rem;background:var(--soft);padding:.22rem;color:var(--muted);font-weight:700}.menu-option-name{display:flex;align-items:center;gap:.55rem}.menu-option-name i{width:1.1rem;color:var(--accent);text-align:center}.menu-empty{height:9rem!important;text-align:center;color:var(--muted)}.menu-direct-link{display:flex;align-items:center;gap:.75rem;margin:1rem;border:1px solid var(--line);border-radius:.65rem;padding:1rem}.menu-direct-link>span{display:grid;width:2.25rem;height:2.25rem;place-items:center;border-radius:.55rem;background:var(--soft);color:var(--accent)}.menu-direct-link small,.menu-direct-link strong{display:block}.menu-direct-link small{color:var(--muted);font-size:.66rem}.menu-direct-link strong{margin-top:.15rem;font-size:.8rem}.menu-state{display:flex;min-height:9rem;align-items:center;justify-content:center;gap:.55rem;color:var(--muted);font-size:.75rem}.menu-state-large{min-height:17rem;flex-direction:column}.menu-options{min-height:17rem}
@media(max-width:850px){.menu-layout{grid-template-columns:1fr}.menu-module-list{max-height:18rem}.menu-options-header{align-items:flex-start;flex-direction:column}.menu-header-actions{width:100%}.menu-header-actions button{flex:1}}
</style>
