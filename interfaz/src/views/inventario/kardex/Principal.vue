<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ArrowDownLeft, ArrowUpRight, BookOpenText, RotateCcw, Search } from '@lucide/vue'
import Tarjeta from '../../../components/ui/BaseCard.vue'
import Tabla from '../../../components/ui/BaseTable.vue'
import Ruta from '../../../components/ui/Breadcrumb.vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'

const lista = ref([])
const cargando = ref(false)
const error = ref(false)
const catalogos = ref({ sucursales: [], tipos: [] })
const resumen = ref({ movimientos: 0, entradas: 0, salidas: 0 })
const hoy = new Date()
const fechaLocal = fecha => [fecha.getFullYear(), String(fecha.getMonth() + 1).padStart(2, '0'), String(fecha.getDate()).padStart(2, '0')].join('-')
const filtrosIniciales = () => ({
  termino: '',
  sucursal_id: '',
  movimiento_tipo_id: '',
  fecha_desde: fechaLocal(new Date(hoy.getFullYear(), hoy.getMonth(), 1)),
  fecha_hasta: fechaLocal(hoy),
})
const filtros = reactive(filtrosIniciales())

const columnas = [
  { key: 'fecha', label: 'Fecha' },
  { key: 'producto', label: 'Producto' },
  { key: 'nombre_sucursal', label: 'Sucursal' },
  { key: 'nombre_tipo', label: 'Movimiento' },
  { key: 'documento', label: 'Documento' },
  { key: 'entrada', label: 'Entrada', class: 'text-end' },
  { key: 'salida', label: 'Salida', class: 'text-end' },
  { key: 'saldo', label: 'Saldo', class: 'text-end' },
  { key: 'nombre_usuario', label: 'Usuario' },
  { key: 'observacion', label: 'Observación' },
]

const formatoCantidad = valor => Number(valor || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatoFecha = valor => {
  if (!valor) return '—'
  const fecha = new Date(String(valor).replace(' ', 'T'))
  return new Intl.DateTimeFormat('es-GT', { dateStyle: 'short', timeStyle: 'short' }).format(fecha)
}
const claseFila = row => Number(row.cantidad) < 0
  ? 'bg-red-500/[0.04] hover:bg-red-500/[0.08]!'
  : 'bg-emerald-500/[0.035] hover:bg-emerald-500/[0.075]!'

async function cargarCatalogos() {
  try {
    const { data } = await api.get('index.php/inventario/kardex/get_datos')
    catalogos.value = data.cat || { sucursales: [], tipos: [] }
  } catch (problema) {
    toast.error(problema.message || 'No se pudieron cargar los filtros del Kardex.')
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
    const { data } = await api.get('index.php/inventario/kardex/buscar', { params: { ...filtros } })
    if (!Array.isArray(data.lista)) throw new Error(data.mensaje || 'No se pudo cargar el Kardex.')
    lista.value = data.lista
    resumen.value = { movimientos: 0, entradas: 0, salidas: 0, ...(data.resumen || {}) }
  } catch (problema) {
    error.value = true
    toast.error(problema.message || 'No se pudo cargar el Kardex.')
  } finally {
    cargando.value = false
  }
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
  <div class="maintenance-screen kardex-list">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
      <div class="space-y-3">
        <Ruta :items="[{ label: 'Inventario' }, { label: 'Kardex' }]" />
        <h2 class="flex items-center gap-2 text-xl font-semibold"><BookOpenText :size="22" class="text-accent" aria-hidden="true" />Kardex</h2>
      </div>
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted" aria-label="Leyenda de movimientos">
        <span class="inline-flex items-center gap-1.5"><ArrowDownLeft :size="15" class="text-emerald-600" />Entrada</span>
        <span class="inline-flex items-center gap-1.5"><ArrowUpRight :size="15" class="text-red-600" />Salida</span>
      </div>
    </div>

    <Tarjeta no-padding class="maintenance-catalog">
      <form class="flex flex-wrap items-end gap-2 border-b border-line px-4 py-3" @submit.prevent="buscar">
        <div class="min-w-[230px] flex-1"><label for="kardex-buscar" class="mb-1 block text-[11px] font-medium text-muted">Buscar</label><div class="relative"><Search :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" /><input id="kardex-buscar" v-model="filtros.termino" type="search" class="min-h-9 w-full rounded-lg border border-line bg-surface py-1.5 pl-9 pr-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" placeholder="Código o producto" /></div></div>
        <div class="w-full sm:w-[175px]"><label for="kardex-sucursal" class="mb-1 block text-[11px] font-medium text-muted">Sucursal</label><select id="kardex-sucursal" v-model="filtros.sucursal_id" class="min-h-9 w-full rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10"><option value="">Todas</option><option v-for="item in catalogos.sucursales" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
        <div class="w-full sm:w-[175px]"><label for="kardex-tipo" class="mb-1 block text-[11px] font-medium text-muted">Movimiento</label><select id="kardex-tipo" v-model="filtros.movimiento_tipo_id" class="min-h-9 w-full rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10"><option value="">Todos</option><option v-for="item in catalogos.tipos" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
        <div class="w-full sm:w-[145px]"><label for="kardex-desde" class="mb-1 block text-[11px] font-medium text-muted">Desde</label><input id="kardex-desde" v-model="filtros.fecha_desde" type="date" class="min-h-9 w-full rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" /></div>
        <div class="w-full sm:w-[145px]"><label for="kardex-hasta" class="mb-1 block text-[11px] font-medium text-muted">Hasta</label><input id="kardex-hasta" v-model="filtros.fecha_hasta" type="date" class="min-h-9 w-full rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" /></div>
        <button type="submit" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-lg bg-accent px-3 text-sm font-semibold text-white hover:opacity-90" :disabled="cargando" title="Buscar movimientos"><Search :size="16" /><span>Buscar</span></button>
        <button type="button" class="grid size-9 place-items-center rounded-lg border border-line bg-surface text-muted hover:bg-soft hover:text-ink" :disabled="cargando" title="Limpiar filtros" aria-label="Limpiar filtros" @click="limpiar"><RotateCcw :size="16" /></button>
      </form>

      <p v-if="cargando" role="status" class="p-10 text-center text-sm text-muted">Consultando movimientos…</p>
      <div v-else-if="error" class="p-8 text-center"><button class="inline-flex min-h-10 items-center justify-center rounded-lg border border-line bg-surface px-4 text-sm font-medium hover:bg-soft" @click="buscar">Reintentar</button></div>
      <Tabla v-else :columns="columnas" :rows="lista" :row-class="claseFila" empty-text="No se encontraron movimientos en el período seleccionado." class="[&_th]:px-3! [&_td]:px-3! [&_td]:py-2! [&_td]:text-[13px]">
        <template #cell-fecha="{ value }"><span class="whitespace-nowrap text-muted">{{ formatoFecha(value) }}</span></template>
        <template #cell-producto="{ row }"><div class="flex min-w-48 flex-col"><strong class="font-semibold text-ink">{{ row.nombre_producto }}</strong><span class="text-xs text-muted">{{ row.codigo_producto }} · {{ row.codigo_unidad }}</span></div></template>
        <template #cell-nombre_tipo="{ row }"><span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold" :class="Number(row.cantidad) < 0 ? 'bg-red-500/10 text-red-700 dark:text-red-300' : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'"><component :is="Number(row.cantidad) < 0 ? ArrowUpRight : ArrowDownLeft" :size="13" />{{ row.nombre_tipo }}</span></template>
        <template #cell-documento="{ row }"><span :class="row.numero_compra || row.numero_ajuste || row.numero_inventario ? 'font-medium text-accent' : 'text-muted'">{{ row.numero_compra || row.numero_ajuste || row.numero_inventario || '—' }}</span></template>
        <template #cell-entrada="{ value }"><strong v-if="Number(value)" class="tabular-nums text-emerald-700 dark:text-emerald-300">+{{ formatoCantidad(value) }}</strong><span v-else class="text-muted">—</span></template>
        <template #cell-salida="{ value }"><strong v-if="Number(value)" class="tabular-nums text-red-700 dark:text-red-300">-{{ formatoCantidad(value) }}</strong><span v-else class="text-muted">—</span></template>
        <template #cell-saldo="{ row }"><strong class="tabular-nums" :class="Number(row.saldo) < 0 ? 'text-red-700 dark:text-red-300' : 'text-ink'">{{ formatoCantidad(row.saldo) }} {{ row.codigo_unidad }}</strong></template>
        <template #cell-observacion="{ value }"><span class="block max-w-64 truncate text-muted" :title="value">{{ value || '—' }}</span></template>
      </Tabla>

      <div class="flex flex-wrap items-center justify-between gap-x-5 gap-y-2 border-t border-line bg-soft/30 px-4 py-3 text-xs text-muted">
        <span><strong class="font-semibold text-ink">{{ resumen.movimientos }}</strong> movimientos</span>
        <div class="flex flex-wrap gap-4"><span>Entradas: <strong class="text-emerald-700 dark:text-emerald-300">{{ resumen.entradas }}</strong></span><span>Salidas: <strong class="text-red-700 dark:text-red-300">{{ resumen.salidas }}</strong></span></div>
      </div>
    </Tarjeta>
  </div>
</template>
