<script setup>
import { onMounted, reactive, ref } from 'vue'
import { Eye, FileSpreadsheet, Plus, RotateCcw, Search } from '@lucide/vue'
import Tarjeta from '../../../components/ui/BaseCard.vue'
import Tabla from '../../../components/ui/BaseTable.vue'
import Ruta from '../../../components/ui/Breadcrumb.vue'
import Form from './Form.vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'

const lista = ref([])
const catalogos = ref({ estados: [], sucursales: [] })
const cargando = ref(false)
const error = ref(false)
const verFormulario = ref(false)
const seleccionada = ref(null)
const hoy = new Date()
const fechaLocal = fecha => [fecha.getFullYear(), String(fecha.getMonth() + 1).padStart(2, '0'), String(fecha.getDate()).padStart(2, '0')].join('-')
const filtrosIniciales = () => ({
  termino: '', inventario_estado_id: '', sucursal_id: '',
  fecha_desde: fechaLocal(new Date(hoy.getFullYear(), hoy.getMonth(), 1)),
  fecha_hasta: fechaLocal(hoy),
})
const filtros = reactive(filtrosIniciales())
const columnas = [
  { key: 'numero', label: 'Número' }, { key: 'fecha', label: 'Fecha' },
  { key: 'nombre_sucursal', label: 'Sucursal' }, { key: 'archivo_nombre', label: 'Archivo' },
  { key: 'lineas', label: 'Productos', class: 'text-center' },
  { key: 'cantidad_total', label: 'Cantidad', class: 'text-end' },
  { key: 'nombre_estado', label: 'Estado' }, { key: 'nombre_usuario', label: 'Creado por' },
  { key: 'acciones', label: '', class: 'text-end' },
]

const formatoCantidad = valor => Number(valor || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatoFecha = valor => valor ? new Intl.DateTimeFormat('es-GT', { dateStyle: 'short', timeStyle: 'short' }).format(new Date(String(valor).replace(' ', 'T'))) : '—'
const claseEstado = codigo => codigo === 'PROCESADO'
  ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
  : codigo === 'ANULADO'
    ? 'bg-red-500/10 text-red-700 dark:text-red-300'
    : 'bg-blue-500/10 text-blue-700 dark:text-blue-300'

async function cargarCatalogos() {
  try {
    const { data } = await api.get('index.php/inventario/inventario_inicial/get_datos')
    if (!data.cat) throw new Error('No se pudieron cargar los catálogos.')
    catalogos.value = data.cat
  } catch (problema) {
    toast.error(problema.message || 'No se pudieron cargar los filtros.')
  }
}

async function buscar() {
  if (cargando.value) return
  if (filtros.fecha_desde && filtros.fecha_hasta && filtros.fecha_desde > filtros.fecha_hasta) {
    toast.error('La fecha inicial no puede ser mayor que la fecha final.')
    return
  }
  cargando.value = true
  error.value = false
  try {
    const { data } = await api.get('index.php/inventario/inventario_inicial/buscar', { params: { ...filtros } })
    if (!Array.isArray(data.lista)) throw new Error(data.mensaje || 'No se pudo cargar el listado.')
    lista.value = data.lista
  } catch (problema) {
    error.value = true
    toast.error(problema.message || 'No se pudieron cargar los inventarios iniciales.')
  } finally {
    cargando.value = false
  }
}

function abrir(inventario = null) {
  seleccionada.value = inventario
  verFormulario.value = true
}

function coincide(inventario) {
  const termino = filtros.termino.trim().toLocaleLowerCase('es')
  const texto = [inventario.numero, inventario.archivo_nombre, inventario.observacion]
    .filter(Boolean).join(' ').toLocaleLowerCase('es')
  const fecha = String(inventario.fecha || '').slice(0, 10)
  return (!termino || texto.includes(termino))
    && (!filtros.inventario_estado_id || String(inventario.inventario_estado_id) === String(filtros.inventario_estado_id))
    && (!filtros.sucursal_id || String(inventario.sucursal_id) === String(filtros.sucursal_id))
    && (!filtros.fecha_desde || fecha >= filtros.fecha_desde)
    && (!filtros.fecha_hasta || fecha <= filtros.fecha_hasta)
}

function actualizar(inventario) {
  const indice = lista.value.findIndex(item => String(item.id) === String(inventario.id))
  const visible = coincide(inventario)
  if (indice >= 0 && visible) lista.value.splice(indice, 1, inventario)
  else if (indice >= 0) lista.value.splice(indice, 1)
  else if (visible) lista.value.unshift(inventario)
  verFormulario.value = false
  seleccionada.value = null
}

function limpiar() {
  Object.assign(filtros, filtrosIniciales())
  buscar()
}

onMounted(async () => {
  await cargarCatalogos()
  await buscar()
})
</script>

<template>
  <Form v-if="verFormulario" :inventario="seleccionada" @cerrar="verFormulario = false" @actualizar="actualizar" />
  <div v-else class="maintenance-screen inventory-initial-list">
      <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="space-y-3">
          <Ruta :items="[{ label: 'Inventario' }, { label: 'Inventario inicial' }]" />
          <h2 class="flex items-center gap-2 text-xl font-semibold"><FileSpreadsheet :size="22" class="text-accent" />Inventario inicial</h2>
        </div>
        <button type="button" class="btn-primary inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold" @click="abrir()"><Plus :size="16" />Nueva carga</button>
      </div>

      <Tarjeta no-padding class="maintenance-catalog">
        <form class="flex flex-wrap items-end gap-2 border-b border-line p-4" @submit.prevent="buscar">
          <div class="min-w-56 flex-1"><label for="inventario-buscar" class="mb-1 block text-xs text-muted">Buscar</label><input id="inventario-buscar" v-model="filtros.termino" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm" placeholder="Número, archivo u observación" /></div>
          <div class="w-full sm:w-44"><label for="inventario-estado" class="mb-1 block text-xs text-muted">Estado</label><select id="inventario-estado" v-model="filtros.inventario_estado_id" class="min-h-10 w-full rounded-lg border border-line bg-surface px-2 text-sm"><option value="">Todos</option><option v-for="item in catalogos.estados" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
          <div class="w-full sm:w-44"><label for="inventario-sucursal" class="mb-1 block text-xs text-muted">Sucursal</label><select id="inventario-sucursal" v-model="filtros.sucursal_id" class="min-h-10 w-full rounded-lg border border-line bg-surface px-2 text-sm"><option value="">Todas</option><option v-for="item in catalogos.sucursales" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
          <div class="w-full sm:w-36"><label for="inventario-desde" class="mb-1 block text-xs text-muted">Desde</label><input id="inventario-desde" v-model="filtros.fecha_desde" type="date" class="min-h-10 w-full rounded-lg border border-line bg-surface px-2 text-sm" /></div>
          <div class="w-full sm:w-36"><label for="inventario-hasta" class="mb-1 block text-xs text-muted">Hasta</label><input id="inventario-hasta" v-model="filtros.fecha_hasta" type="date" class="min-h-10 w-full rounded-lg border border-line bg-surface px-2 text-sm" /></div>
          <button type="submit" class="grid size-10 place-items-center rounded-lg border border-line bg-surface" :disabled="cargando" aria-label="Buscar"><Search :size="17" /></button>
          <button type="button" class="grid size-10 place-items-center rounded-lg border border-line bg-surface text-muted" :disabled="cargando" aria-label="Limpiar filtros" @click="limpiar"><RotateCcw :size="17" /></button>
        </form>

        <p v-if="cargando" class="p-10 text-center text-sm text-muted">Consultando inventarios…</p>
        <div v-else-if="error" class="p-8 text-center"><button class="rounded-lg border border-line px-4 py-2 text-sm" @click="buscar">Reintentar</button></div>
        <Tabla v-else :columns="columnas" :rows="lista" empty-text="No se encontraron inventarios iniciales." class="[&_th]:px-3! [&_td]:px-3! [&_td]:py-2! [&_td]:text-[13px]">
          <template #cell-numero="{ row }"><button class="font-semibold text-accent" @click="abrir(row)">{{ row.numero }}</button></template>
          <template #cell-fecha="{ value }"><span class="text-muted">{{ formatoFecha(value) }}</span></template>
          <template #cell-archivo_nombre="{ value }"><span class="block max-w-64 truncate" :title="value">{{ value }}</span></template>
          <template #cell-lineas="{ value }"><span class="rounded-full bg-soft px-2 py-1 text-xs font-semibold">{{ value }}</span></template>
          <template #cell-cantidad_total="{ value }"><strong class="tabular-nums">{{ formatoCantidad(value) }}</strong></template>
          <template #cell-nombre_estado="{ row }"><span class="rounded-full px-2 py-1 text-xs font-medium" :class="claseEstado(row.codigo_estado)">{{ row.nombre_estado }}</span></template>
          <template #cell-acciones="{ row }"><button class="grid size-8 place-items-center rounded-lg text-muted hover:bg-soft" title="Abrir" @click="abrir(row)"><Eye :size="16" /></button></template>
        </Tabla>
        <p class="border-t border-line px-4 py-3 text-xs text-muted">{{ lista.length }} inventarios</p>
      </Tarjeta>
  </div>
</template>
