<script setup>
import { onMounted, reactive, ref } from 'vue'
import { PackageSearch, RotateCcw, Search } from '@lucide/vue'
import Tarjeta from '../../../components/ui/BaseCard.vue'
import Tabla from '../../../components/ui/BaseTable.vue'
import Ruta from '../../../components/ui/Breadcrumb.vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'
import { estiloEtiquetaCategoria } from '../../../utils/categoryColors'

const lista = ref([])
const cargando = ref(false)
const error = ref(false)
const catalogos = ref({ sucursales: [], categorias: [] })
const resumen = ref({ productos: 0, sucursales: 0, bajo_minimo: 0, agotados: 0, proximos_vencer: 0 })
const filtros = reactive({ termino: '', sucursal_id: '', categoria_id: '', estado: '' })

const columnas = [
  { key: 'producto', label: 'Producto' },
  { key: 'nombre_categoria', label: 'Categoría' },
  { key: 'nombre_marca', label: 'Marca' },
  { key: 'nombre_sucursal', label: 'Sucursal' },
  { key: 'cantidad', label: 'Existencia', class: 'text-end' },
  { key: 'existencia_minima', label: 'Mínimo', class: 'text-end' },
  { key: 'lotes', label: 'Lotes', class: 'text-center' },
  { key: 'fecha_vence_proxima', label: 'Próximo vencimiento' },
  { key: 'estado', label: 'Estado' },
]

const estadoDe = (row) => {
  const cantidad = Number(row.cantidad || 0)
  const minima = Number(row.existencia_minima || 0)
  if (cantidad <= 0) return { texto: 'Agotado', clase: 'bg-red-500/10 text-red-700 dark:text-red-300' }
  if (cantidad <= minima) return { texto: 'Bajo mínimo', clase: 'bg-amber-500/10 text-amber-700 dark:text-amber-300' }
  return { texto: 'Disponible', clase: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' }
}

const claseFila = row => {
  const estado = estadoDe(row).texto
  if (estado === 'Agotado') return 'bg-red-500/[0.055] hover:bg-red-500/[0.10]!'
  if (estado === 'Bajo mínimo') return 'bg-amber-500/[0.055] hover:bg-amber-500/[0.10]!'
  return 'bg-emerald-500/[0.035] hover:bg-emerald-500/[0.075]!'
}

const formatoCantidad = valor => Number(valor || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
const formatoFecha = valor => valor ? new Intl.DateTimeFormat('es-GT', { dateStyle: 'medium', timeZone: 'UTC' }).format(new Date(`${String(valor).slice(0, 10)}T00:00:00Z`)) : '—'

async function cargarCatalogos() {
  try {
    const { data } = await api.get('index.php/inventario/existencia/get_datos')
    catalogos.value = data.cat || { sucursales: [], categorias: [] }
  } catch (problema) {
    toast.error(problema.message || 'No se pudieron cargar los filtros de existencias.')
  }
}

async function buscar() {
  if (cargando.value) return
  cargando.value = true
  error.value = false
  try {
    const { data } = await api.get('index.php/inventario/existencia/buscar', { params: { ...filtros } })
    if (!Array.isArray(data.lista)) throw new Error(data.mensaje || 'No se pudo cargar el inventario.')
    lista.value = data.lista
    resumen.value = { ...resumen.value, ...(data.resumen || {}) }
  } catch (problema) {
    error.value = true
    toast.error(problema.message || 'No se pudieron cargar las existencias.')
  } finally {
    cargando.value = false
  }
}

function limpiar() {
  Object.assign(filtros, { termino: '', sucursal_id: '', categoria_id: '', estado: '' })
  buscar()
}

onMounted(async () => {
  await cargarCatalogos()
  await buscar()
})
</script>

<template>
  <div class="maintenance-screen inventory-existence-list">
    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
      <div class="space-y-3">
        <Ruta :items="[{ label: 'Inventario' }, { label: 'Existencias' }]" />
        <h2 class="flex items-center gap-2 text-xl font-semibold"><PackageSearch :size="22" class="text-accent" aria-hidden="true" />Existencias</h2>
      </div>
      <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted" aria-label="Leyenda de existencias">
        <span class="inline-flex items-center gap-1.5"><i class="size-2.5 rounded-full bg-emerald-500"></i>Disponible</span>
        <span class="inline-flex items-center gap-1.5"><i class="size-2.5 rounded-full bg-amber-500"></i>Bajo mínimo</span>
        <span class="inline-flex items-center gap-1.5"><i class="size-2.5 rounded-full bg-red-500"></i>Sin existencia</span>
      </div>
    </div>

    <Tarjeta no-padding class="maintenance-catalog">
      <form class="flex flex-wrap items-end gap-2 border-b border-line px-4 py-3" @submit.prevent="buscar">
        <div class="min-w-[230px] flex-1"><label for="existencia-buscar" class="mb-1 block text-[11px] font-medium text-muted">Buscar</label><div class="relative"><Search :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" /><input id="existencia-buscar" v-model="filtros.termino" type="search" class="min-h-9 w-full rounded-lg border border-line bg-surface py-1.5 pl-9 pr-3 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" placeholder="Código, producto o marca" /></div></div>
        <div class="w-full sm:w-[175px]"><label for="existencia-sucursal" class="mb-1 block text-[11px] font-medium text-muted">Sucursal</label><select id="existencia-sucursal" v-model="filtros.sucursal_id" class="min-h-9 w-full rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10"><option value="">Todas</option><option v-for="item in catalogos.sucursales" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
        <div class="w-full sm:w-[175px]"><label for="existencia-categoria" class="mb-1 block text-[11px] font-medium text-muted">Categoría</label><select id="existencia-categoria" v-model="filtros.categoria_id" class="min-h-9 w-full rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10"><option value="">Todas</option><option v-for="item in catalogos.categorias" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
        <div class="w-full sm:w-[145px]"><label for="existencia-estado" class="mb-1 block text-[11px] font-medium text-muted">Estado</label><select id="existencia-estado" v-model="filtros.estado" class="min-h-9 w-full rounded-lg border border-line bg-surface px-2.5 py-1.5 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10"><option value="">Todos</option><option value="disponible">Disponible</option><option value="bajo">Bajo mínimo</option><option value="agotado">Agotado</option></select></div>
        <button type="submit" class="inline-flex min-h-9 items-center justify-center gap-2 rounded-lg bg-accent px-3 text-sm font-semibold text-white hover:opacity-90" :disabled="cargando" title="Buscar existencias"><Search :size="16" /><span>Buscar</span></button>
        <button type="button" class="grid size-9 place-items-center rounded-lg border border-line bg-surface text-muted hover:bg-soft hover:text-ink" :disabled="cargando" title="Limpiar filtros" aria-label="Limpiar filtros" @click="limpiar"><RotateCcw :size="16" /></button>
      </form>

      <p v-if="cargando" role="status" class="p-10 text-center text-sm text-muted">Consultando existencias…</p>
      <div v-else-if="error" class="p-8 text-center"><button class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line bg-surface px-3 text-sm font-medium hover:bg-soft" @click="buscar">Reintentar</button></div>
      <Tabla v-else :columns="columnas" :rows="lista" :row-class="claseFila" row-key="clave" empty-text="No se encontraron existencias con los filtros seleccionados." class="[&_th]:px-3! [&_td]:px-3! [&_td]:py-2! [&_td]:text-[13px]">
        <template #cell-producto="{ row }"><div class="flex min-w-52 items-center gap-2.5"><i class="size-2.5 shrink-0 rounded-full" :class="estadoDe(row).texto === 'Disponible' ? 'bg-emerald-500' : estadoDe(row).texto === 'Agotado' ? 'bg-red-500' : 'bg-amber-500'"></i><div class="flex flex-col"><strong class="font-semibold text-ink">{{ row.nombre_producto }}</strong><span class="text-xs text-muted">{{ row.codigo || 'Sin código' }} · {{ row.codigo_unidad }}</span></div></div></template>
        <template #cell-nombre_categoria="{ row }"><span class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-semibold" :style="estiloEtiquetaCategoria(row.etiqueta_categoria)"><span class="size-1.5 rounded-full" :style="{ backgroundColor: 'var(--category-color)' }" aria-hidden="true"></span>{{ row.nombre_categoria }}</span></template>
        <template #cell-nombre_marca="{ value }"><span class="text-ink">{{ value }}</span></template>
        <template #cell-cantidad="{ row }"><strong class="tabular-nums" :class="estadoDe(row).texto === 'Disponible' ? 'text-ink' : estadoDe(row).texto === 'Agotado' ? 'text-red-600' : 'text-amber-600'">{{ formatoCantidad(row.cantidad) }} {{ row.codigo_unidad }}</strong></template>
        <template #cell-existencia_minima="{ row }"><span class="tabular-nums text-muted">{{ formatoCantidad(row.existencia_minima) }}</span></template>
        <template #cell-lotes="{ value }"><span class="inline-flex min-w-7 justify-center rounded-full bg-soft px-2 py-1 text-xs font-semibold">{{ value }}</span></template>
        <template #cell-fecha_vence_proxima="{ row }"><span :class="row.fecha_vence_proxima ? 'text-ink' : 'text-muted'">{{ formatoFecha(row.fecha_vence_proxima) }}</span></template>
        <template #cell-estado="{ row }"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="estadoDe(row).clase">{{ estadoDe(row).texto }}</span></template>
      </Tabla>
      <p class="border-t border-line px-4 py-3 text-xs text-muted">{{ lista.length }} registros de existencia</p>
    </Tarjeta>
  </div>
</template>
