<script setup>
import { computed, onMounted, ref } from 'vue'
import {
  CircleAlert as Alerta,
  Pencil as Editar,
  Plus as Agregar,
  RefreshCw as Actualizar,
  Search as Buscar,
  ShieldCheck as IconoModulo,
  X as Cerrar,
} from '@lucide/vue'
import TarjetaBase from '../../../components/ui/BaseCard.vue'
import TablaBase from '../../../components/ui/BaseTable.vue'
import RutaNavegacion from '../../../components/ui/Breadcrumb.vue'
import FormularioRegistro from './Form.vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'

const registros = ref([])
const cargando = ref(false)
const error = ref('')
const termino = ref('')
const estado = ref('todos')
const mostrarFormulario = ref(false)
const seleccionada = ref(null)

const columnas = [
  { key: 'nombre', label: 'Rol' },
  { key: 'activo', label: 'Estado' },
  { key: 'acciones', label: 'Acciones', class: 'text-end' },
]

const activos = computed(() => registros.value.filter((registro) => Number(registro.activo) === 1).length)
const filtros = computed(() => [
  { valor: 'todos', nombre: 'Todos', cantidad: registros.value.length },
  { valor: '1', nombre: 'Activos', cantidad: activos.value },
  { valor: '0', nombre: 'Inactivos', cantidad: registros.value.length - activos.value },
])

const filtrados = computed(() => {
  const busqueda = termino.value.trim().toLocaleLowerCase('es')
  return registros.value.filter((registro) =>
    (estado.value === 'todos' || Number(registro.activo) === Number(estado.value))
    && String(registro.nombre ?? '').toLocaleLowerCase('es').includes(busqueda),
  )
})

function limpiarFiltros() {
  termino.value = ''
  estado.value = 'todos'
}

async function cargar() {
  if (cargando.value) return
  cargando.value = true
  error.value = ''
  try {
    const { data: respuesta } = await api.get('index.php/mnt/rol/buscar')
    if (!Array.isArray(respuesta.lista)) throw new Error(respuesta.mensaje || 'No se pudo obtener el listado de roles.')
    registros.value = respuesta.lista
  } catch (problema) {
    error.value = problema.message || 'No se pudo cargar el listado.'
  } finally {
    cargando.value = false
  }
}

function abrirFormulario(registro = null) {
  seleccionada.value = registro
  mostrarFormulario.value = true
}

function actualizarLista(registro) {
  const indice = registros.value.findIndex((elemento) => String(elemento.id) === String(registro.id))
  if (indice >= 0) registros.value.splice(indice, 1, registro)
  else registros.value.unshift(registro)
  mostrarFormulario.value = false
  toast.success('Rol guardado correctamente.')
}

onMounted(cargar)
</script>

<template>
  <div class="maintenance-screen">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
      <div class="space-y-3">
        <RutaNavegacion :items="[{ label: 'Mantenimiento' }, { label: 'Roles' }]" />
        <h2 class="flex items-center gap-2 text-xl font-semibold leading-tight tracking-tight">
          <IconoModulo :size="22" class="text-accent" aria-hidden="true" /> Roles
        </h2>
      </div>
      <button type="button" class="btn-primary inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold" @click="abrirFormulario()">
        <Agregar :size="16" aria-hidden="true" /> Nuevo
      </button>
    </div>

    <TarjetaBase no-padding class="maintenance-catalog">
      <div class="flex flex-col gap-2 border-b border-line px-4 py-2 sm:px-5 xl:flex-row xl:items-center xl:justify-between xl:gap-4">
        <div class="flex max-w-full flex-wrap gap-1" role="group" aria-label="Filtrar roles por estado">
          <button
            v-for="filtro in filtros"
            :key="filtro.valor"
            type="button"
            :aria-pressed="estado === filtro.valor"
            class="inline-flex min-h-11 items-center gap-2 border-b-2 px-3 text-xs font-semibold transition-colors sm:text-sm"
            :class="estado === filtro.valor ? 'border-accent text-accent' : 'border-transparent text-muted hover:text-ink'"
            @click="estado = filtro.valor"
          >
            {{ filtro.nombre }}
            <span class="rounded px-1.5 py-0.5 text-[11px] tabular-nums" :class="estado === filtro.valor ? 'bg-accent/10 text-accent' : 'bg-soft text-muted'">{{ cargando || error ? '—' : filtro.cantidad }}</span>
          </button>
        </div>
        <div class="flex min-w-0 items-center gap-2 xl:w-96 xl:shrink-0">
          <div class="relative min-w-0 flex-1">
            <label for="buscar-rol" class="sr-only">Buscar rol</label>
            <Buscar :size="16" class="pointer-events-none absolute left-3 top-3 text-muted" aria-hidden="true" />
            <input id="buscar-rol" v-model="termino" type="search" class="min-h-10 w-full rounded-lg border border-line bg-canvas py-2 pl-9 pr-3 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/10" placeholder="Buscar rol…" />
          </div>
          <button v-if="termino || estado !== 'todos'" type="button" class="flex size-10 shrink-0 items-center justify-center rounded-lg text-muted hover:bg-soft hover:text-accent" aria-label="Limpiar filtros" title="Limpiar filtros" @click="limpiarFiltros"><Cerrar :size="16" aria-hidden="true" /></button>
          <button type="button" class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg text-muted transition-colors hover:bg-soft hover:text-ink" aria-label="Actualizar roles" title="Actualizar roles" :disabled="cargando" @click="cargar">
            <Actualizar :size="17" :class="{ 'animate-spin motion-reduce:animate-none': cargando }" aria-hidden="true" />
          </button>
        </div>
      </div>

      <div v-if="cargando" class="px-6 py-8" role="status">
        <p class="mb-6 flex items-center justify-center gap-2 text-sm text-muted"><Actualizar :size="16" class="animate-spin motion-reduce:animate-none" aria-hidden="true" /> Cargando roles…</p>
        <div v-for="fila in 4" :key="fila" class="mb-4 flex animate-pulse items-center gap-4 motion-reduce:animate-none" aria-hidden="true"><span class="size-9 rounded-lg bg-soft"></span><span class="h-3 w-1/3 rounded bg-soft"></span><span class="ml-auto h-6 w-16 rounded-full bg-soft"></span></div>
      </div>
      <div v-else-if="error" class="flex flex-col items-center gap-3 px-6 py-12 text-center" role="alert">
        <Alerta :size="28" class="text-red-600 dark:text-red-300" aria-hidden="true" />
        <h3 class="font-semibold">No pudimos cargar los roles</h3>
        <p class="max-w-md text-sm text-muted">{{ error }}</p>
        <button type="button" class="mt-2 min-h-11 rounded-xl border border-line px-5 text-sm font-semibold hover:bg-soft" @click="cargar">Reintentar</button>
      </div>
      <template v-else>
        <TablaBase v-if="filtrados.length" :columns="columnas" :rows="filtrados" class="[&_th]:px-3! [&_th]:py-1.5! [&_th]:text-xs [&_td]:px-3! [&_td]:py-1! [&_td]:text-[13px]">
          <template #cell-nombre="{ row: registro }">
            <button type="button" class="group flex min-h-7 items-center gap-2 rounded-lg text-left" @click="abrirFormulario(registro)">
              <span class="flex size-7 items-center justify-center rounded-lg bg-accent/10 text-accent"><IconoModulo :size="14" aria-hidden="true" /></span>
              <span class="max-w-48 truncate font-semibold transition-colors group-hover:text-accent sm:max-w-80" :title="registro.nombre">{{ registro.nombre }}</span>
            </button>
          </template>
          <template #cell-activo="{ value: activo }">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset" :class="Number(activo) === 1 ? 'bg-emerald-500/10 text-emerald-700 ring-emerald-500/20 dark:text-emerald-300' : 'bg-soft text-muted ring-line'">
              <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span>{{ Number(activo) === 1 ? 'Activo' : 'Inactivo' }}
            </span>
          </template>
          <template #cell-acciones="{ row: registro }">
            <button type="button" class="inline-flex size-7 items-center justify-center rounded-lg text-muted transition-colors hover:bg-accent/10 hover:text-accent" :title="`Editar ${registro.nombre}`" :aria-label="`Editar ${registro.nombre}`" @click="abrirFormulario(registro)"><Editar :size="14" aria-hidden="true" /></button>
          </template>
        </TablaBase>
        <div v-else class="flex flex-col items-center px-6 py-14 text-center">
          <span class="mb-4 flex size-16 items-center justify-center rounded-2xl bg-soft text-muted"><Buscar v-if="registros.length" :size="28" aria-hidden="true" /><IconoModulo v-else :size="28" aria-hidden="true" /></span>
          <h3 class="font-semibold">{{ registros.length ? 'Sin coincidencias' : 'No hay roles registrados' }}</h3>
          <button type="button" class="mt-5 min-h-11 rounded-xl border border-line px-5 text-sm font-semibold text-accent hover:bg-accent/5" @click="registros.length ? limpiarFiltros() : abrirFormulario()">{{ registros.length ? 'Limpiar filtros' : 'Agregar rol' }}</button>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-line bg-soft/30 px-4 py-3 text-xs text-muted" aria-live="polite">
          <span><strong class="font-semibold text-ink">{{ filtrados.length }}</strong> de {{ registros.length }} roles</span>
          <span>Selecciona un rol para editarlo</span>
        </div>
      </template>
    </TarjetaBase>

    <FormularioRegistro v-if="mostrarFormulario" :registro="seleccionada" @cerrar="mostrarFormulario = false" @guardada="actualizarLista" />
  </div>
</template>
