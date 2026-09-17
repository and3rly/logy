<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ArrowLeft, Ban, Boxes, CheckCircle2, ClipboardPlus, LoaderCircle, Plus, Save, Search, Trash2, X } from '@lucide/vue'
import Tabla from '../../../components/ui/BaseTable.vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'

const propiedades = defineProps({ ajuste: { type: Object, default: null } })
const emitir = defineEmits(['cerrar', 'actualizar'])
const actual = ref(propiedades.ajuste)
const formulario = reactive({
  inventario_ajuste_tipo_id: propiedades.ajuste?.inventario_ajuste_tipo_id ?? null,
  sucursal_id: propiedades.ajuste?.sucursal_id ?? null,
  motivo: propiedades.ajuste?.motivo || '',
  observacion: propiedades.ajuste?.observacion || '',
  detalle: [],
})
const catalogos = ref({ tipos: [], estados: [], sucursales: [], productos: [], existencias: [] })
const cargando = ref(true)
const errorCarga = ref(false)
const ocupado = ref(false)
const cantidad = ref(1)
const busqueda = ref('')
const filtroProducto = ref('')
const buscador = ref(null)
const confirmacion = ref(null)
const accion = ref('')
const base = ref('')
const columnasProductos = [
  { key: 'codigo', label: 'Código' }, { key: 'nombre', label: 'Producto' },
  { key: 'nombre_um', label: 'Unidad' }, { key: 'nombre_categoria', label: 'Categoría' },
  { key: 'nombre_marca', label: 'Marca' }, { key: 'agregar', label: '', class: 'text-end' },
]
const columnasDetalle = [
  { key: 'codigo', label: 'Código' }, { key: 'nombre_producto', label: 'Producto' },
  { key: 'nombre_um', label: 'Unidad' }, { key: 'disponible', label: 'Stock actual', class: 'text-end' },
  { key: 'fecha_vence', label: 'Vencimiento' }, { key: 'cantidad', label: 'Cantidad', class: 'text-end' },
  { key: 'observacion', label: 'Observación' }, { key: 'quitar', label: '', class: 'text-end' },
]

const codigoEstado = computed(() => actual.value?.codigo_estado || 'BORRADOR')
const editable = computed(() => !actual.value || codigoEstado.value === 'BORRADOR')
const puedeEditar = computed(() => editable.value && !cargando.value && !errorCarga.value && !ocupado.value)
const tipoSeleccionado = computed(() => catalogos.value.tipos.find(item => String(item.id) === String(formulario.inventario_ajuste_tipo_id)) || null)
const naturaleza = computed(() => tipoSeleccionado.value?.naturaleza || actual.value?.naturaleza || '')
const esNegativo = computed(() => naturaleza.value === 'NEGATIVO')
const productosFiltrados = computed(() => {
  const termino = filtroProducto.value.trim().toLowerCase()
  if (!termino) return catalogos.value.productos
  return catalogos.value.productos.filter(item => [item.codigo, item.codigo_barra, item.nombre, item.nombre_categoria, item.nombre_marca]
    .some(valor => String(valor || '').toLowerCase().includes(termino)))
})
const totalCantidad = computed(() => formulario.detalle.reduce((total, linea) => total + Number(linea.cantidad || 0), 0))
const serializar = () => JSON.stringify(formulario)
const cambios = computed(() => base.value !== serializar())
const formatoCantidad = valor => Number(valor || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

function existencia(productoId, unidadId = null) {
  if (!formulario.sucursal_id) return 0
  return catalogos.value.existencias
    .filter(item => String(item.producto_id) === String(productoId)
      && String(item.sucursal_id) === String(formulario.sucursal_id)
      && (!unidadId || String(item.unidad_medida_id) === String(unidadId)))
    .reduce((total, item) => total + Number(item.cantidad || 0), 0)
}

async function cargar() {
  if (ocupado.value) return
  cargando.value = true
  errorCarga.value = false
  try {
    const [datos, detalle] = await Promise.all([
      api.get('index.php/inventario/ajuste/get_datos'),
      actual.value ? api.get(`index.php/inventario/ajuste/detalle/${encodeURIComponent(actual.value.id)}`) : Promise.resolve({ data: { lista: [] } }),
    ])
    if (!datos.data.cat || !Array.isArray(detalle.data.lista)) throw new Error('No se pudo cargar el ajuste.')
    catalogos.value = datos.data.cat
    formulario.detalle = detalle.data.lista.map(linea => ({
      ...linea,
      fecha_vence: linea.fecha_vence?.slice(0, 10) || null,
      cantidad: Number(linea.cantidad),
    }))
    base.value = serializar()
  } catch (problema) {
    errorCarga.value = true
    toast.error(problema.message || 'No se pudo cargar el ajuste.')
  } finally {
    cargando.value = false
  }
}

function agregar(producto) {
  if (!puedeEditar.value) return false
  if (!producto || !Number.isFinite(Number(cantidad.value)) || Number(cantidad.value) <= 0) {
    toast.error('Selecciona un producto e ingresa una cantidad válida.')
    return false
  }
  const existente = formulario.detalle.find(linea => String(linea.producto_id) === String(producto.id)
    && !linea.producto_presentacion_id && !linea.fecha_vence)
  if (existente) existente.cantidad = Number(existente.cantidad) + Number(cantidad.value)
  else formulario.detalle.push({
    producto_id: producto.id,
    unidad_medida_id: producto.unidad_medida_id,
    producto_presentacion_id: null,
    codigo: producto.codigo,
    nombre_producto: producto.nombre,
    nombre_um: producto.nombre_um,
    control_vence: Number(producto.control_vence || 0),
    fecha_vence: null,
    cantidad: Number(cantidad.value),
    observacion: '',
  })
  cantidad.value = 1
  busqueda.value = ''
  return true
}

function agregarCodigo() {
  const termino = busqueda.value.trim().toLowerCase()
  const producto = catalogos.value.productos.find(item => [item.codigo, item.codigo_barra]
    .some(valor => valor && String(valor).toLowerCase() === termino))
  if (producto) agregar(producto)
  else {
    filtroProducto.value = busqueda.value
    buscador.value.showModal()
  }
}

function quitar(linea) {
  if (!puedeEditar.value) return
  formulario.detalle.splice(formulario.detalle.indexOf(linea), 1)
}

function validar() {
  if (!formulario.inventario_ajuste_tipo_id || !formulario.sucursal_id) {
    toast.error('Selecciona el tipo de ajuste y la sucursal.')
    return false
  }
  if (!formulario.detalle.length) {
    toast.error('Agrega al menos un producto.')
    return false
  }
  if (Number(tipoSeleccionado.value?.requiere_observacion) === 1 && !formulario.motivo.trim() && !formulario.observacion.trim()) {
    toast.error('Este tipo de ajuste requiere un motivo u observación.')
    return false
  }
  if (formulario.detalle.some(linea => !Number.isFinite(Number(linea.cantidad)) || Number(linea.cantidad) <= 0)) {
    toast.error('Revisa las cantidades del detalle.')
    return false
  }
  if (esNegativo.value && formulario.detalle.some(linea => Number(linea.cantidad) > existencia(linea.producto_id, linea.unidad_medida_id))) {
    toast.error('Una de las cantidades supera el stock disponible en la sucursal.')
    return false
  }
  return true
}

function solicitar(tipo) {
  if (ocupado.value) return
  if (tipo === 'salir') {
    if (!editable.value || !cambios.value) {
      emitir('cerrar')
      return
    }
  } else if (tipo === 'guardar') {
    if (!puedeEditar.value || !validar()) return
  } else if (tipo === 'aplicar') {
    if (!actual.value || !editable.value) return
    if (cambios.value) {
      toast.info('Guarda los cambios antes de aplicar el ajuste.')
      return
    }
  } else if (tipo === 'anular' && (!actual.value || !['BORRADOR', 'APLICADO'].includes(codigoEstado.value))) return
  accion.value = tipo
  confirmacion.value.showModal()
}

async function ejecutar() {
  const tipo = accion.value
  if (ocupado.value) return
  if (tipo === 'salir') {
    confirmacion.value.close()
    emitir('cerrar')
    return
  }
  if (!['guardar', 'aplicar', 'anular'].includes(tipo)) return
  ocupado.value = true
  try {
    const datos = tipo === 'guardar' ? {
      inventario_ajuste_tipo_id: formulario.inventario_ajuste_tipo_id,
      sucursal_id: formulario.sucursal_id,
      motivo: formulario.motivo,
      observacion: formulario.observacion,
      detalle: formulario.detalle.map(linea => ({
        producto_id: linea.producto_id,
        unidad_medida_id: linea.unidad_medida_id,
        producto_presentacion_id: linea.producto_presentacion_id || null,
        fecha_vence: linea.fecha_vence || null,
        cantidad: Number(linea.cantidad),
        observacion: linea.observacion || null,
      })),
    } : undefined
    const id = actual.value?.id || ''
    const { data } = await api.post(`index.php/inventario/ajuste/${tipo}/${encodeURIComponent(id)}`, datos)
    if (Number(data.exito) !== 1 || !data.linea?.id) throw new Error(data.mensaje || 'No se pudo completar la operación.')
    toast.success(data.mensaje || 'Operación completada.')
    confirmacion.value.close()
    emitir('actualizar', data.linea)
  } catch (problema) {
    toast.error(problema.message || 'No se pudo completar la operación.')
  } finally {
    ocupado.value = false
  }
}

onMounted(cargar)
</script>

<template>
  <div class="maintenance-screen inventory-adjustment-form">
    <header class="mb-5 flex flex-wrap items-end justify-between gap-4">
      <div>
        <button class="mb-3 inline-flex items-center gap-2 text-xs font-semibold text-muted hover:text-accent" :disabled="ocupado" @click="solicitar('salir')"><ArrowLeft :size="15" />Inventario / Ajustes</button>
        <div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-xl border border-line bg-accent/5 text-accent"><ClipboardPlus :size="22" /></span><div><h1 class="text-xl font-bold">{{ actual?.numero || 'Nuevo ajuste de inventario' }}</h1><p class="mt-1 text-xs text-muted">{{ actual ? `${actual.nombre_estado} · ${actual.nombre_tipo}` : 'Registra una corrección positiva o negativa de stock' }}</p></div></div>
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="actual && codigoEstado === 'BORRADOR'" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/5 px-4 text-sm font-semibold text-emerald-700" :disabled="ocupado || cambios" @click="solicitar('aplicar')"><CheckCircle2 :size="17" />Aplicar al stock</button>
        <button v-if="actual && ['BORRADOR', 'APLICADO'].includes(codigoEstado)" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-red-500/30 px-4 text-sm font-semibold text-red-600" :disabled="ocupado" @click="solicitar('anular')"><Ban :size="17" />{{ codigoEstado === 'APLICADO' ? 'Anular y revertir' : 'Anular borrador' }}</button>
        <button v-if="editable" class="btn-primary inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold" :disabled="!puedeEditar" @click="solicitar('guardar')"><LoaderCircle v-if="ocupado" :size="17" class="animate-spin" /><Save v-else :size="17" />{{ ocupado ? 'Procesando…' : 'Guardar borrador' }}</button>
      </div>
    </header>

    <div v-if="cargando" class="grid min-h-72 place-items-center rounded-xl border border-line bg-surface"><div class="text-center"><LoaderCircle :size="28" class="mx-auto animate-spin text-accent" /><p class="mt-3 text-sm text-muted">Cargando ajuste…</p></div></div>
    <div v-else-if="errorCarga" class="grid min-h-72 place-items-center rounded-xl border border-line bg-surface text-center"><div><p class="text-sm text-muted">No se pudo cargar el ajuste.</p><button class="mt-3 rounded-lg border border-line px-4 py-2 text-sm" @click="cargar">Reintentar</button></div></div>

    <div v-else class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_320px]">
      <div class="space-y-5">
        <section class="rounded-xl border border-line bg-surface">
          <header class="border-b border-line px-5 py-4"><h2 class="font-semibold">Información del ajuste</h2><p class="mt-1 text-xs text-muted">Tipo, naturaleza y sucursal afectada</p></header>
          <fieldset :disabled="!puedeEditar" class="grid gap-4 p-5 sm:grid-cols-2">
            <div><label for="ajuste-form-tipo" class="mb-2 block text-xs font-semibold text-muted">Tipo de ajuste *</label><select id="ajuste-form-tipo" v-model="formulario.inventario_ajuste_tipo_id" class="min-h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm"><option :value="null" disabled>Seleccionar tipo</option><option v-for="item in catalogos.tipos" :key="item.id" :value="item.id">{{ item.nombre }} · {{ item.naturaleza === 'POSITIVO' ? 'Positivo' : 'Negativo' }}</option></select></div>
            <div><label for="ajuste-form-sucursal" class="mb-2 block text-xs font-semibold text-muted">Sucursal *</label><select id="ajuste-form-sucursal" v-model="formulario.sucursal_id" class="min-h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm"><option :value="null" disabled>Seleccionar sucursal</option><option v-for="item in catalogos.sucursales" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
            <div class="sm:col-span-2"><label for="ajuste-form-motivo" class="mb-2 block text-xs font-semibold text-muted">Motivo complementario</label><input id="ajuste-form-motivo" v-model="formulario.motivo" maxlength="150" class="min-h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm" placeholder="Describe brevemente la razón del ajuste" /></div>
          </fieldset>
        </section>

        <section class="overflow-hidden rounded-xl border border-line bg-surface">
          <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4"><div><h2 class="font-semibold">Productos</h2><p class="mt-1 text-xs text-muted">Las cantidades se capturan positivas; la naturaleza determina la operación</p></div><span class="rounded-full px-3 py-1 text-xs font-bold" :class="esNegativo ? 'bg-red-500/10 text-red-700' : 'bg-emerald-500/10 text-emerald-700'">{{ naturaleza ? (esNegativo ? 'Ajuste negativo' : 'Ajuste positivo') : 'Selecciona un tipo' }}</span></header>
          <div v-if="editable" class="grid items-end gap-2 border-b border-line bg-soft/30 p-4 sm:grid-cols-[100px_minmax(180px,1fr)_auto_auto]">
            <div><label for="ajuste-cantidad" class="mb-1 block text-xs text-muted">Cantidad</label><input id="ajuste-cantidad" v-model.number="cantidad" type="number" min="0.01" step="0.01" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm" /></div>
            <div><label for="ajuste-codigo" class="mb-1 block text-xs text-muted">Código o código de barras</label><div class="relative"><Search :size="16" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" /><input id="ajuste-codigo" v-model="busqueda" class="min-h-10 w-full rounded-lg border border-line bg-surface pl-9 pr-3 text-sm" placeholder="Escanea o escribe un código" @keydown.enter.prevent="agregarCodigo" /></div></div>
            <button class="btn-primary grid size-10 place-items-center rounded-lg" :disabled="!puedeEditar" aria-label="Agregar por código" @click="agregarCodigo"><Plus :size="17" /></button>
            <button class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-line bg-surface px-4 text-sm font-semibold" :disabled="!puedeEditar" @click="buscador.showModal()"><Search :size="16" />Buscar</button>
          </div>
          <Tabla :columns="columnasDetalle" :rows="formulario.detalle" row-key="producto_id" empty-text="Agrega productos al ajuste." class="[&_th]:px-3! [&_td]:px-3! [&_td]:py-2! [&_td]:text-xs">
            <template #cell-codigo="{ value }"><span class="rounded bg-soft px-2 py-1 font-mono text-xs">{{ value }}</span></template>
            <template #cell-nombre_producto="{ value }"><strong class="block min-w-44 whitespace-normal">{{ value }}</strong></template>
            <template #cell-disponible="{ row }"><span class="tabular-nums" :class="esNegativo && Number(row.cantidad) > existencia(row.producto_id, row.unidad_medida_id) ? 'font-bold text-red-600' : 'text-muted'">{{ formatoCantidad(existencia(row.producto_id, row.unidad_medida_id)) }}</span></template>
            <template #cell-fecha_vence="{ row }"><input v-if="Number(row.control_vence) === 1" v-model="row.fecha_vence" type="date" :disabled="!puedeEditar" class="min-h-9 rounded-lg border border-line bg-surface px-2 text-xs" /><span v-else class="text-muted">No aplica</span></template>
            <template #cell-cantidad="{ row }"><input v-model.number="row.cantidad" type="number" min="0.01" step="0.01" :disabled="!puedeEditar" class="min-h-9 w-24 rounded-lg border border-line bg-surface px-2 text-right text-xs" /></template>
            <template #cell-observacion="{ row }"><input v-model="row.observacion" maxlength="300" :disabled="!puedeEditar" class="min-h-9 w-48 rounded-lg border border-line bg-surface px-2 text-xs" placeholder="Opcional" /></template>
            <template #cell-quitar="{ row }"><button v-if="editable" class="grid size-8 place-items-center rounded-lg text-red-600 hover:bg-red-500/10" :disabled="!puedeEditar" aria-label="Quitar producto" @click="quitar(row)"><Trash2 :size="16" /></button></template>
          </Tabla>
        </section>
      </div>

      <aside class="sticky top-24 space-y-5">
        <section class="rounded-xl border border-line bg-surface p-5"><h2 class="text-sm font-semibold">Resumen</h2><dl class="mt-4 grid gap-3 text-sm"><div class="flex justify-between"><dt class="text-muted">Documento</dt><dd class="font-semibold">{{ actual?.numero || 'Pendiente' }}</dd></div><div class="flex justify-between"><dt class="text-muted">Naturaleza</dt><dd class="font-semibold" :class="esNegativo ? 'text-red-600' : 'text-emerald-600'">{{ naturaleza || '—' }}</dd></div><div class="flex justify-between"><dt class="text-muted">Productos</dt><dd class="font-semibold">{{ formulario.detalle.length }}</dd></div><div class="flex justify-between border-t border-line pt-3"><dt class="text-muted">Cantidad total</dt><dd class="text-lg font-bold tabular-nums">{{ formatoCantidad(totalCantidad) }}</dd></div></dl></section>
        <section class="rounded-xl border border-line bg-surface p-5"><label for="ajuste-observacion" class="block text-sm font-semibold">Observación general</label><textarea id="ajuste-observacion" v-model="formulario.observacion" rows="6" maxlength="300" :disabled="!puedeEditar" class="mt-3 w-full resize-y rounded-lg border border-line bg-surface p-3 text-sm" placeholder="Información adicional para auditoría"></textarea><p class="mt-1 text-right text-xs text-muted">{{ formulario.observacion.length }}/300</p></section>
      </aside>
    </div>

    <dialog ref="buscador" class="m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%_-_2rem)] max-w-5xl overflow-auto rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-slate-950/60" aria-labelledby="titulo-buscar-producto">
      <header class="flex items-center justify-between border-b border-line p-4"><div><h2 id="titulo-buscar-producto" class="font-semibold">Buscar productos</h2><p class="text-xs text-muted">Solo se muestran bienes activos</p></div><button class="grid size-9 place-items-center rounded-lg hover:bg-soft" aria-label="Cerrar" @click="buscador.close()"><X :size="18" /></button></header>
      <div class="border-b border-line p-4"><div class="relative"><Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-muted" /><input v-model="filtroProducto" class="min-h-10 w-full rounded-lg border border-line bg-surface pl-9 pr-3 text-sm" placeholder="Código, nombre, categoría o marca" /></div></div>
      <Tabla :columns="columnasProductos" :rows="productosFiltrados" empty-text="No se encontraron productos.">
        <template #cell-codigo="{ value }"><span class="rounded bg-soft px-2 py-1 font-mono text-xs">{{ value }}</span></template>
        <template #cell-nombre="{ value }"><div class="flex min-w-44 items-center gap-2"><Boxes :size="17" class="text-accent" /><strong>{{ value }}</strong></div></template>
        <template #cell-agregar="{ row }"><button class="inline-flex items-center gap-1 rounded-lg bg-accent/10 px-3 py-2 text-xs font-semibold text-accent" @click="agregar(row) && buscador.close()"><Plus :size="15" />Agregar</button></template>
      </Tabla>
    </dialog>

    <dialog ref="confirmacion" class="m-auto w-[calc(100%_-_2rem)] max-w-sm rounded-xl border border-line bg-surface p-6 text-center text-ink shadow-2xl backdrop:bg-slate-950/60" @cancel.prevent="!ocupado && confirmacion.close()">
      <div class="mx-auto grid size-12 place-items-center rounded-full" :class="accion === 'anular' ? 'bg-red-500/10 text-red-600' : accion === 'aplicar' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-accent/10 text-accent'"><Ban v-if="accion === 'anular'" :size="22" /><CheckCircle2 v-else-if="accion === 'aplicar'" :size="22" /><Save v-else :size="22" /></div>
      <h3 class="mt-4 text-lg font-bold">{{ accion === 'guardar' ? '¿Guardar borrador?' : accion === 'aplicar' ? '¿Aplicar ajuste al stock?' : accion === 'anular' ? '¿Anular y revertir ajuste?' : '¿Salir sin guardar?' }}</h3>
      <p class="mt-2 text-sm text-muted">{{ accion === 'aplicar' ? 'Se modificará el stock y se crearán los movimientos del Kardex.' : accion === 'anular' ? (codigoEstado === 'APLICADO' ? 'Se crearán movimientos inversos. La operación puede fallar si ya no existe stock suficiente.' : 'El borrador quedará anulado sin modificar el stock.') : accion === 'salir' ? 'Los cambios sin guardar se perderán.' : 'Podrás modificarlo mientras permanezca en borrador.' }}</p>
      <div class="mt-5 flex justify-center gap-2"><button class="min-h-10 rounded-lg border border-line px-4 text-sm font-semibold" :disabled="ocupado" @click="confirmacion.close()">Cancelar</button><button class="btn-primary inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold" :class="{ 'bg-red-600!': accion === 'anular' }" :disabled="ocupado" @click="ejecutar"><LoaderCircle v-if="ocupado" :size="16" class="animate-spin" />Confirmar</button></div>
    </dialog>
  </div>
</template>
