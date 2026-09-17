<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { Ban, CircleDollarSign, Eye, LoaderCircle, Pencil, Plus, Printer, ReceiptText, Search, ShoppingCart, X } from '@lucide/vue'
import Ruta from '../../components/ui/Breadcrumb.vue'
import Tarjeta from '../../components/ui/BaseCard.vue'
import Tabla from '../../components/ui/BaseTable.vue'
import NativePdfDialog from '../../components/ui/NativePdfDialog.vue'
import FormularioVenta from './Principal.vue'
import api from '../../services/api'
import { toast } from 'vue3-toastify'

const hoy = new Date()
const fechaLocal = fecha => [fecha.getFullYear(), String(fecha.getMonth() + 1).padStart(2, '0'), String(fecha.getDate()).padStart(2, '0')].join('-')

const lista = ref([])
const catalogos = ref({ estados: [], sucursales: [] })
const cargando = ref(false)
const error = ref(false)
const detalleDialog = ref(null)
const ventaSeleccionada = ref(null)
const detalle = ref([])
const cargandoDetalle = ref(false)
const verFormulario = ref(false)
const accionDialog = ref(null)
const accion = ref('')
const motivo = ref('')
const procesandoAccion = ref(false)
const pdfUrl = ref('')
const urlApi = ruta => new URL(ruta, new URL(api.defaults.baseURL, window.location.origin)).href
const filtros = reactive({
  termino: '',
  venta_estado_id: '',
  sucursal_id: '',
  fecha_desde: fechaLocal(new Date(hoy.getFullYear(), hoy.getMonth(), 1)),
  fecha_hasta: fechaLocal(hoy),
})

const columnas = [
  { key: 'correlativo', label: 'Comprobante' },
  { key: 'fecha', label: 'Fecha' },
  { key: 'nombre_cliente', label: 'Cliente' },
  { key: 'nombre_sucursal', label: 'Sucursal' },
  { key: 'nombre_forma_pago', label: 'Forma de pago' },
  { key: 'total_precio', label: 'Total', class: 'text-end' },
  { key: 'nombre_estado', label: 'Estado' },
  { key: 'acciones', label: '', class: 'text-end' },
]

const totalListado = computed(() => lista.value.reduce((total, venta) => total + Number(venta.base || venta.total_precio || 0), 0))

function fechaHora(valor) {
  if (!valor) return '—'
  const fecha = new Date(String(valor).replace(' ', 'T'))
  return Number.isNaN(fecha.getTime()) ? String(valor) : fecha.toLocaleString('es-GT', { dateStyle: 'short', timeStyle: 'short' })
}

function monto(valor, simbolo = 'Q') {
  return `${simbolo || 'Q'} ${Number(valor || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}

function claseEstado(nombre) {
  const estado = String(nombre || '').toLowerCase()
  if (estado.includes('factur')) return 'sale-status--success'
  if (estado.includes('pagad')) return 'sale-status--paid'
  if (estado.includes('anulad')) return 'sale-status--danger'
  return 'sale-status--draft'
}

function imprimir(venta) {
  if (!venta?.correlativo) return toast.info('Factura la venta antes de imprimirla.')
  detalleDialog.value?.close()
  pdfUrl.value = urlApi(`index.php/venta/venta/imprimir/${encodeURIComponent(venta.id)}`)
}

async function cargarCatalogos() {
  try {
    const { data } = await api.get('index.php/venta/venta/get_datos')
    catalogos.value = {
      estados: Array.isArray(data.cat?.estados) ? data.cat.estados : [],
      sucursales: Array.isArray(data.cat?.sucursales) ? data.cat.sucursales : [],
    }
  } catch (problema) {
    toast.error(problema.message || 'No se pudieron cargar los filtros de ventas.')
  }
}

async function buscar() {
  if (cargando.value) return
  if (filtros.fecha_desde && filtros.fecha_hasta && filtros.fecha_desde > filtros.fecha_hasta) {
    toast.error('Revisa el rango de fechas.')
    return
  }
  cargando.value = true
  error.value = false
  try {
    const { data } = await api.get('index.php/venta/venta/buscar', { params: { ...filtros } })
    if (!Array.isArray(data.lista)) throw new Error(data.mensaje || 'No se pudo cargar el listado.')
    lista.value = data.lista
  } catch (problema) {
    lista.value = []
    error.value = true
    toast.error(problema.message || 'No se pudieron cargar las ventas.')
  } finally {
    cargando.value = false
  }
}

async function verDetalle(venta) {
  ventaSeleccionada.value = venta
  detalle.value = []
  cargandoDetalle.value = true
  detalleDialog.value?.showModal()
  try {
    const { data } = await api.get(`index.php/venta/venta/detalle/${encodeURIComponent(venta.id)}`)
    if (!Array.isArray(data.lista)) throw new Error(data.mensaje || 'No se pudo cargar el detalle.')
    detalle.value = data.lista
  } catch (problema) {
    toast.error(problema.message || 'No se pudo cargar el detalle de la venta.')
  } finally {
    cargandoDetalle.value = false
  }
}

function nuevaVenta() {
  ventaSeleccionada.value = null
  verFormulario.value = true
}

function editarVenta(venta) {
  if (String(venta.nombre_estado).toLowerCase() !== 'creado') {
    toast.info('Solo las ventas en estado Creado se pueden modificar.')
    return
  }
  detalleDialog.value?.close()
  ventaSeleccionada.value = venta
  verFormulario.value = true
}

async function cerrarFormulario() {
  verFormulario.value = false
  ventaSeleccionada.value = null
  await buscar()
}

async function ventaActualizada(venta) {
  ventaSeleccionada.value = venta
  if (String(venta.nombre_estado).toLowerCase() !== 'creado') {
    verFormulario.value = false
    ventaSeleccionada.value = null
    await buscar()
  }
}

function solicitarAccion(tipo, venta) {
  detalleDialog.value?.close()
  ventaSeleccionada.value = venta
  accion.value = tipo
  motivo.value = ''
  accionDialog.value?.showModal()
}

async function ejecutarAccion() {
  if (procesandoAccion.value || !ventaSeleccionada.value?.id) return
  if (accion.value === 'anular' && !motivo.value.trim()) {
    toast.error('Indica el motivo de la anulación.')
    return
  }
  procesandoAccion.value = true
  try {
    const payload = accion.value === 'anular' ? { motivo: motivo.value.trim() } : {}
    const { data } = await api.post(`index.php/venta/venta/${accion.value}/${encodeURIComponent(ventaSeleccionada.value.id)}`, payload)
    if (Number(data.exito) !== 1 || !data.linea?.id) throw new Error(data.mensaje || 'No se pudo cambiar el estado de la venta.')
    toast.success(data.mensaje)
    accionDialog.value?.close()
    ventaSeleccionada.value = null
    await buscar()
  } catch (problema) {
    toast.error(problema.message || 'No se pudo cambiar el estado de la venta.')
  } finally {
    procesandoAccion.value = false
  }
}

onMounted(async () => {
  await Promise.all([cargarCatalogos(), buscar()])
})
</script>

<template>
  <NativePdfDialog v-if="pdfUrl" :url="pdfUrl" @close="pdfUrl=''" />
  <FormularioVenta v-if="verFormulario" :venta="ventaSeleccionada" @cerrar="cerrarFormulario" @actualizar="ventaActualizada" />
  <div v-else class="sale-list-page">
    <header class="sale-list-header">
      <div>
        <Ruta :items="[{ label: 'Ventas' }, { label: 'Listado' }]" />
        <div class="mt-3 flex items-center gap-3"><span class="sale-list-title-icon"><ReceiptText :size="22" /></span><div><h1>Ventas</h1><p>Consulta los comprobantes y borradores generados.</p></div></div>
      </div>
      <button type="button" class="btn-primary sale-new-button" @click="nuevaVenta"><Plus :size="17" />Nueva venta</button>
    </header>

    <Tarjeta no-padding class="sale-list-card">
      <form class="sale-list-filters" @submit.prevent="buscar">
        <label class="sale-filter-search"><span>Buscar</span><div><Search :size="17" /><input v-model.trim="filtros.termino" placeholder="Correlativo, factura o referencia" /></div></label>
        <label><span>Estado</span><VSelect v-model="filtros.venta_estado_id" class="logy-select" :options="catalogos.estados" label="nombre" :reduce="item => item.id" placeholder="Todos"><template #no-options>No hay estados.</template></VSelect></label>
        <label><span>Sucursal</span><VSelect v-model="filtros.sucursal_id" class="logy-select" :options="catalogos.sucursales" label="nombre" :reduce="item => item.id" placeholder="Todas"><template #no-options>No hay sucursales.</template></VSelect></label>
        <label><span>Desde</span><input v-model="filtros.fecha_desde" type="date" /></label>
        <label><span>Hasta</span><input v-model="filtros.fecha_hasta" type="date" /></label>
        <button type="submit" class="sale-search-button" :disabled="cargando"><LoaderCircle v-if="cargando" :size="18" class="animate-spin" /><Search v-else :size="18" />Buscar</button>
      </form>

      <div v-if="cargando" class="sale-list-state"><LoaderCircle :size="24" class="animate-spin" /><span>Cargando ventas…</span></div>
      <div v-else-if="error" class="sale-list-state"><strong>No se pudo cargar el listado.</strong><button type="button" @click="buscar">Reintentar</button></div>
      <Tabla v-else :columns="columnas" :rows="lista" empty-text="No hay ventas para los filtros seleccionados." class="sale-list-table">
        <template #cell-correlativo="{ row }"><button type="button" class="sale-number" @click="verDetalle(row)">{{ row.correlativo || `Borrador #${row.id}` }}</button><small>{{ row.nombre_serie }}</small></template>
        <template #cell-fecha="{ value }">{{ fechaHora(value) }}</template>
        <template #cell-nombre_cliente="{ row }"><strong>{{ row.nombre_cliente || 'Consumidor final' }}</strong><small v-if="row.identificacion_cliente">{{ row.identificacion_cliente }}</small></template>
        <template #cell-total_precio="{ row }"><strong class="sale-amount">{{ monto(row.base || row.total_precio, row.simbolo_moneda) }}</strong></template>
        <template #cell-nombre_estado="{ value }"><span class="sale-status" :class="claseEstado(value)"><i></i>{{ value }}</span></template>
        <template #cell-acciones="{ row }"><button v-if="String(row.nombre_estado).toLowerCase() === 'creado'" type="button" class="sale-view-button" title="Modificar borrador" :aria-label="`Modificar venta ${row.id}`" @click="editarVenta(row)"><Pencil :size="17" /></button><button v-if="row.correlativo" type="button" class="sale-view-button" title="Ver PDF" :aria-label="`Ver comprobante ${row.correlativo}`" @click="imprimir(row)"><Printer :size="17" /></button><button type="button" class="sale-view-button" title="Ver detalle" :aria-label="`Ver venta ${row.correlativo || row.id}`" @click="verDetalle(row)"><Eye :size="17" /></button></template>
      </Tabla>

      <footer class="sale-list-footer"><span>{{ lista.length }} {{ lista.length === 1 ? 'venta' : 'ventas' }}</span><strong>Total mostrado: {{ monto(totalListado, lista[0]?.simbolo_moneda) }}</strong></footer>
    </Tarjeta>

    <dialog ref="detalleDialog" class="sale-detail-dialog" aria-labelledby="titulo-detalle-venta" @cancel.prevent="detalleDialog.close()">
      <header><div><span><ShoppingCart :size="20" /></span><div><h2 id="titulo-detalle-venta">{{ ventaSeleccionada?.correlativo || `Borrador #${ventaSeleccionada?.id || ''}` }}</h2><p>{{ ventaSeleccionada?.nombre_cliente || 'Consumidor final' }} · {{ ventaSeleccionada?.nombre_sucursal }}</p></div></div><button type="button" aria-label="Cerrar" @click="detalleDialog.close()"><X :size="20" /></button></header>
      <div v-if="cargandoDetalle" class="sale-detail-loading"><LoaderCircle :size="23" class="animate-spin" />Cargando detalle…</div>
      <div v-else class="sale-detail-body">
        <div class="sale-detail-meta"><div><span>Fecha</span><strong>{{ fechaHora(ventaSeleccionada?.fecha) }}</strong></div><div><span>Forma de pago</span><strong>{{ ventaSeleccionada?.nombre_forma_pago }}</strong></div><div><span>Estado</span><strong>{{ ventaSeleccionada?.nombre_estado }}</strong></div></div>
        <div class="sale-detail-lines"><article v-for="linea in detalle" :key="linea.id"><div><strong>{{ linea.nombre_producto }}</strong><span>{{ linea.codigo }} · {{ linea.nombre_unidad }}</span></div><span>{{ Number(linea.cantidad).toLocaleString('es-GT') }} × {{ monto(linea.precio, ventaSeleccionada?.simbolo_moneda) }}</span><strong>{{ monto(linea.base, ventaSeleccionada?.simbolo_moneda) }}</strong></article><p v-if="!detalle.length">Esta venta no tiene productos activos.</p></div>
      </div>
      <footer><div><span>Total</span><strong>{{ monto(ventaSeleccionada?.base || ventaSeleccionada?.total_precio, ventaSeleccionada?.simbolo_moneda) }}</strong></div><div class="sale-detail-actions"><button v-if="String(ventaSeleccionada?.nombre_estado).toLowerCase() === 'creado'" type="button" @click="editarVenta(ventaSeleccionada)"><Pencil :size="16" />Modificar</button><button v-if="ventaSeleccionada?.correlativo" type="button" @click="imprimir(ventaSeleccionada)"><Printer :size="16" />PDF</button><button v-if="String(ventaSeleccionada?.nombre_estado).toLowerCase() === 'facturada'" type="button" class="sale-action-pay" @click="solicitarAccion('pagar', ventaSeleccionada)"><CircleDollarSign :size="16" />Marcar pagada</button><button v-if="String(ventaSeleccionada?.nombre_estado).toLowerCase() !== 'anulada'" type="button" class="sale-action-cancel" @click="solicitarAccion('anular', ventaSeleccionada)"><Ban :size="16" />Anular</button><button type="button" @click="detalleDialog.close()">Cerrar</button></div></footer>
    </dialog>

    <dialog ref="accionDialog" class="sale-action-dialog" aria-labelledby="titulo-accion-venta" @cancel.prevent="!procesandoAccion && accionDialog.close()">
      <div class="sale-action-icon" :class="{ 'sale-action-icon--danger': accion === 'anular' }"><Ban v-if="accion === 'anular'" :size="24" /><CircleDollarSign v-else :size="24" /></div>
      <h2 id="titulo-accion-venta">{{ accion === 'anular' ? '¿Anular venta?' : '¿Marcar venta como pagada?' }}</h2>
      <p>{{ accion === 'anular' ? 'La venta quedará anulada y, si fue facturada, las existencias serán devueltas.' : 'La venta cambiará del estado Facturada a Pagada.' }}</p>
      <label v-if="accion === 'anular'"><span>Motivo de anulación</span><textarea v-model.trim="motivo" maxlength="500" rows="3" placeholder="Describe el motivo"></textarea></label>
      <footer><button type="button" :disabled="procesandoAccion" @click="accionDialog.close()">Cancelar</button><button type="button" class="btn-primary" :class="{ 'sale-action-danger': accion === 'anular' }" :disabled="procesandoAccion" @click="ejecutarAccion"><LoaderCircle v-if="procesandoAccion" :size="16" class="animate-spin" />{{ procesandoAccion ? 'Procesando…' : 'Confirmar' }}</button></footer>
    </dialog>
  </div>
</template>

<style scoped>
.sale-list-page{width:100%;max-width:1680px;margin:0 auto;padding:1.5rem}.sale-list-header{display:flex;align-items:flex-end;justify-content:space-between;gap:1rem;margin-bottom:1.25rem}.sale-list-header h1{font-size:1.4rem;font-weight:750;letter-spacing:-.025em}.sale-list-header p{margin-top:.2rem;color:var(--muted);font-size:.875rem}.sale-list-title-icon{display:grid;width:2.75rem;height:2.75rem;place-items:center;border:1px solid var(--line);border-radius:.75rem;background:color-mix(in srgb,var(--accent) 7%,var(--surface));color:var(--accent)}.sale-new-button,.sale-search-button{display:inline-flex;min-height:2.7rem;align-items:center;justify-content:center;gap:.5rem;border-radius:.55rem;padding:.6rem 1rem;font-size:.875rem;font-weight:700}.sale-list-filters{display:grid;grid-template-columns:minmax(16rem,1fr) 10rem 13rem 10rem 10rem auto;align-items:end;gap:.8rem;border-bottom:1px solid var(--line);padding:1rem 1.25rem}.sale-list-filters label>span{display:block;margin-bottom:.4rem;color:var(--muted);font-size:.8rem;font-weight:650}.sale-list-filters input,.sale-list-filters select{width:100%;min-height:2.65rem;border:1px solid var(--line);border-radius:.5rem;background:var(--surface);padding:.5rem .7rem;color:var(--ink);font-size:.875rem;outline:none}.sale-list-filters input:focus,.sale-list-filters select:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent)}.sale-filter-search>div{position:relative}.sale-filter-search svg{position:absolute;left:.75rem;top:50%;transform:translateY(-50%);color:var(--muted)}.sale-filter-search input{padding-left:2.3rem}.sale-search-button{border:1px solid var(--line);background:var(--surface);color:var(--ink)}.sale-search-button:hover{background:var(--soft)}.sale-list-state{display:flex;min-height:18rem;align-items:center;justify-content:center;gap:.7rem;color:var(--muted);font-size:.875rem}.sale-list-state button{border:1px solid var(--line);border-radius:.5rem;padding:.5rem .8rem;color:var(--ink)}.sale-list-table :deep(th){padding:.75rem 1rem;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.025em}.sale-list-table :deep(td){padding:.75rem 1rem;font-size:.85rem}.sale-list-table small{display:block;margin-top:.15rem;color:var(--muted);font-size:.72rem}.sale-number{font-weight:700;color:var(--accent)}.sale-number:hover{text-decoration:underline}.sale-amount{font-variant-numeric:tabular-nums}.sale-status{display:inline-flex;align-items:center;gap:.4rem;border-radius:999px;padding:.35rem .65rem;font-size:.75rem;font-weight:700}.sale-status i{width:.4rem;height:.4rem;border-radius:50%;background:currentColor}.sale-status--draft{background:rgba(245,158,11,.1);color:#b45309}.sale-status--success{background:rgba(5,150,105,.1);color:#047857}.sale-status--paid{background:rgba(37,99,235,.1);color:#2563eb}.sale-status--danger{background:rgba(220,38,38,.1);color:#dc2626}.sale-view-button{display:inline-grid;width:2.2rem;height:2.2rem;place-items:center;border-radius:.5rem;color:var(--muted)}.sale-view-button:hover{background:var(--soft);color:var(--accent)}.sale-list-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-top:1px solid var(--line);padding:.9rem 1.25rem;color:var(--muted);font-size:.8rem}.sale-list-footer strong{color:var(--ink)}.sale-detail-dialog{width:calc(100% - 2rem);max-width:48rem;margin:auto;max-height:calc(100dvh - 2rem);overflow:auto;border:1px solid var(--line);border-radius:.85rem;background:var(--surface);padding:0;color:var(--ink);box-shadow:0 24px 70px rgba(15,23,42,.3)}.sale-detail-dialog::backdrop{background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.sale-detail-dialog>header{display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--line);padding:1.1rem 1.25rem}.sale-detail-dialog>header>div{display:flex;align-items:center;gap:.75rem}.sale-detail-dialog>header>div>span{display:grid;width:2.6rem;height:2.6rem;place-items:center;border-radius:.65rem;background:color-mix(in srgb,var(--accent) 9%,var(--surface));color:var(--accent)}.sale-detail-dialog h2{font-size:1.05rem;font-weight:700}.sale-detail-dialog header p{margin-top:.15rem;color:var(--muted);font-size:.8rem}.sale-detail-dialog header button{display:grid;width:2.5rem;height:2.5rem;place-items:center;border-radius:.55rem;color:var(--muted)}.sale-detail-dialog header button:hover{background:var(--soft)}.sale-detail-loading{display:flex;min-height:14rem;align-items:center;justify-content:center;gap:.6rem;color:var(--muted);font-size:.875rem}.sale-detail-body{padding:1.25rem}.sale-detail-meta{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1rem}.sale-detail-meta>div{display:grid;gap:.25rem;border-radius:.6rem;background:var(--soft);padding:.75rem}.sale-detail-meta span{color:var(--muted);font-size:.75rem}.sale-detail-meta strong{font-size:.85rem}.sale-detail-lines{overflow:hidden;border:1px solid var(--line);border-radius:.65rem}.sale-detail-lines article{display:grid;grid-template-columns:minmax(0,1fr) auto auto;align-items:center;gap:1rem;border-bottom:1px solid var(--line);padding:.85rem 1rem;font-size:.85rem}.sale-detail-lines article:last-child{border-bottom:0}.sale-detail-lines article>div{display:grid;min-width:0}.sale-detail-lines article>div strong{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}.sale-detail-lines article>div span,.sale-detail-lines article>span{color:var(--muted);font-size:.75rem}.sale-detail-lines>p{padding:2rem;text-align:center;color:var(--muted);font-size:.85rem}.sale-detail-dialog>footer{display:flex;align-items:center;justify-content:space-between;border-top:1px solid var(--line);padding:1rem 1.25rem}.sale-detail-dialog>footer>div{display:flex;align-items:baseline;gap:.7rem}.sale-detail-dialog>footer span{color:var(--muted);font-size:.8rem}.sale-detail-dialog>footer strong{color:var(--accent);font-size:1.2rem}.sale-detail-dialog>footer button{min-height:2.5rem;border:1px solid var(--line);border-radius:.5rem;padding:.5rem 1rem;font-size:.875rem;font-weight:650}
.sale-detail-actions{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:.5rem}.sale-detail-actions button{display:inline-flex!important;align-items:center;justify-content:center;gap:.4rem}.sale-detail-actions .sale-action-pay{border-color:rgba(5,150,105,.25);color:#047857}.sale-detail-actions .sale-action-cancel{border-color:rgba(220,38,38,.2);color:#dc2626}.sale-action-dialog{width:calc(100% - 2rem);max-width:27rem;margin:auto;border:1px solid var(--line);border-radius:.85rem;background:var(--surface);padding:1.5rem;color:var(--ink);text-align:center;box-shadow:0 24px 70px rgba(15,23,42,.3)}.sale-action-dialog::backdrop{background:rgba(15,23,42,.58);backdrop-filter:blur(3px)}.sale-action-icon{display:grid;width:3.3rem;height:3.3rem;margin:0 auto 1rem;place-items:center;border-radius:50%;background:rgba(5,150,105,.1);color:#047857}.sale-action-icon--danger{background:rgba(220,38,38,.09);color:#dc2626}.sale-action-dialog h2{font-size:1.05rem;font-weight:700}.sale-action-dialog>p{margin-top:.45rem;color:var(--muted);font-size:.85rem;line-height:1.35rem}.sale-action-dialog label{display:block;margin-top:1rem;text-align:left}.sale-action-dialog label span{display:block;margin-bottom:.4rem;color:var(--muted);font-size:.8rem;font-weight:650}.sale-action-dialog textarea{width:100%;resize:vertical;border:1px solid var(--line);border-radius:.5rem;background:var(--surface);padding:.65rem .75rem;font-size:.875rem;outline:none}.sale-action-dialog textarea:focus{border-color:var(--accent);box-shadow:0 0 0 3px color-mix(in srgb,var(--accent) 10%,transparent)}.sale-action-dialog>footer{display:flex;justify-content:center;gap:.6rem;margin-top:1.25rem}.sale-action-dialog>footer button{display:inline-flex;min-height:2.6rem;align-items:center;justify-content:center;gap:.4rem;border:1px solid var(--line);border-radius:.5rem;padding:.55rem 1rem;font-size:.875rem;font-weight:650}.sale-action-dialog>footer .sale-action-danger{border-color:#dc2626;background:#dc2626;color:#fff}
@media(max-width:1250px){.sale-list-filters{grid-template-columns:repeat(3,1fr)}.sale-filter-search{grid-column:span 2}}
@media(max-width:760px){.sale-list-page{padding:1rem}.sale-list-header{align-items:flex-start;flex-direction:column}.sale-new-button{width:100%}.sale-list-filters{grid-template-columns:1fr 1fr}.sale-filter-search{grid-column:1/-1}.sale-search-button{grid-column:1/-1}.sale-list-footer{align-items:flex-start;flex-direction:column}.sale-detail-meta{grid-template-columns:1fr}.sale-detail-lines article{grid-template-columns:1fr auto}.sale-detail-lines article>span{grid-row:2}.sale-detail-dialog>footer{align-items:stretch;flex-direction:column;gap:1rem}.sale-detail-dialog>footer button{width:100%}}
</style>
