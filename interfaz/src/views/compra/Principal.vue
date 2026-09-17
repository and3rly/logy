<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ShoppingCart, Plus, Search, Eye, Printer } from '@lucide/vue'
import Tarjeta from '../../components/ui/BaseCard.vue'
import Tabla from '../../components/ui/BaseTable.vue'
import Ruta from '../../components/ui/Breadcrumb.vue'
import NativePdfDialog from '../../components/ui/NativePdfDialog.vue'
import Form from './Form.vue'
import api from '../../services/api'
import { toast } from 'vue3-toastify'
const lista = ref([])
const cargando = ref(false)
const error = ref(false)
const verFormulario = ref(false)
const seleccionada = ref(null)
const pdfUrl = ref('')
const urlApi = ruta => new URL(ruta, new URL(api.defaults.baseURL, window.location.origin)).href
const hoy = new Date()
const fechaLocal = fecha => [fecha.getFullYear(), String(fecha.getMonth()+1).padStart(2,'0'), String(fecha.getDate()).padStart(2,'0')].join('-')
const filtros = reactive({ termino: '', compra_estado_id: '', fecha_desde: fechaLocal(new Date(hoy.getFullYear(),hoy.getMonth(),1)), fecha_hasta: fechaLocal(hoy) })
const columnas = [
 {key:'numero',label:'Número'}, {key:'fecha',label:'Fecha'}, {key:'nombre_proveedor',label:'Proveedor'},
 {key:'factura_numero',label:'Factura'}, {key:'nombre_forma_pago',label:'Forma de pago'},
 {key:'total_costo',label:'Total',class:'text-end'}, {key:'nombre_estado',label:'Estado'}, {key:'acciones',label:'Acciones',class:'text-end'}
]
async function buscar() {
 if(cargando.value)return
 if(filtros.fecha_desde && filtros.fecha_hasta && filtros.fecha_desde > filtros.fecha_hasta){toast.error('Revisa el rango de fechas.');return}
 cargando.value=true;error.value=false
 try {
  const {data}=await api.get('index.php/compra/orden/buscar',{params:{...filtros}})
  if(!Array.isArray(data.lista))throw new Error(data.mensaje || 'No se pudo cargar el listado.')
  lista.value=data.lista
 }catch(problema){error.value=true;toast.error(problema.message || 'No se pudieron cargar las órdenes.')}
 finally{cargando.value=false}
}
function abrir(compra=null){seleccionada.value=compra;verFormulario.value=true}
function actualizar(compra){
 const esNueva = !seleccionada.value
 verFormulario.value=false
 seleccionada.value=null
 if(esNueva){
  const indice=lista.value.findIndex(registro=>String(registro.id)===String(compra.id))
  if(indice>=0)lista.value.splice(indice,1,compra)
  else lista.value.unshift(compra)
  return
 }
 buscar()
}
function imprimir(compra){pdfUrl.value=urlApi('index.php/compra/orden/imprimir/'+encodeURIComponent(compra.id))}
onMounted(buscar)
</script>
<template>
 <div class="maintenance-screen purchase-orders-list">
  <NativePdfDialog v-if="pdfUrl" :url="pdfUrl" @close="pdfUrl=''" />
  <Form v-if="verFormulario" :compra="seleccionada" @cerrar="verFormulario=false" @actualizar="actualizar" @imprimir="imprimir" />
  <template v-else>
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
    <div class="space-y-3"><Ruta :items="[{label:'Compras'},{label:'Órdenes de compra'}]" /><h2 class="flex items-center gap-2 text-xl font-semibold"><ShoppingCart :size="22" class="text-accent" aria-hidden="true" />Órdenes de compra</h2></div>
    <button type="button" class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-semibold" @click="abrir()"><Plus :size="16" aria-hidden="true" />Nueva orden</button>
   </div>
   <Tarjeta no-padding class="maintenance-catalog">
    <form class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 xl:grid-cols-[minmax(180px,1fr)_150px_150px_150px_auto]" @submit.prevent="buscar">
     <div><label for="oc-buscar" class="mb-1 block text-xs text-muted">Buscar</label><input id="oc-buscar" v-model="filtros.termino" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" placeholder="Número, factura o proveedor" /></div>
     <div><label for="oc-estado" class="mb-1 block text-xs text-muted">Estado</label><select id="oc-estado" v-model="filtros.compra_estado_id" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10"><option value="">Todos</option><option value="1">Creada</option><option value="2">Recibida</option><option value="3">Anulada</option></select></div>
     <div><label for="oc-desde" class="mb-1 block text-xs text-muted">Desde</label><input id="oc-desde" v-model="filtros.fecha_desde" type="date" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" /></div>
     <div><label for="oc-hasta" class="mb-1 block text-xs text-muted">Hasta</label><input id="oc-hasta" v-model="filtros.fecha_hasta" type="date" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" /></div>
     <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line bg-surface px-3 text-sm font-medium hover:bg-soft self-end" :disabled="cargando" title="Buscar órdenes" aria-label="Buscar órdenes"><Search :size="18" aria-hidden="true" /></button>
    </form>
    <p v-if="cargando" role="status" class="p-10 text-center text-sm text-muted">Cargando órdenes…</p>
    <div v-else-if="error" class="p-8 text-center"><button class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line bg-surface px-3 text-sm font-medium hover:bg-soft" @click="buscar">Reintentar</button></div>
    <Tabla v-else :columns="columnas" :rows="lista" empty-text="No se encontraron órdenes de compra." class="[&_th]:px-3! [&_td]:px-3! [&_td]:py-1.5! [&_td]:text-[13px]">
     <template #cell-numero="{row}"><button class="min-h-8 font-semibold text-accent" @click="abrir(row)">{{row.numero}}</button></template>
     <template #cell-fecha="{value}">{{String(value || '').slice(0,10)}}</template>
     <template #cell-total_costo="{row}"><span class="font-semibold tabular-nums">{{row.simbolo_moneda}} {{Number(row.total_costo || 0).toLocaleString('es-GT',{minimumFractionDigits:2,maximumFractionDigits:2})}}</span></template>
     <template #cell-nombre_estado="{row}"><span class="rounded-full px-2 py-1 text-xs" :class="Number(row.compra_estado_id)===2?'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300':Number(row.compra_estado_id)===3?'bg-red-500/10 text-red-700 dark:text-red-300':'bg-accent/10 text-accent'">{{row.nombre_estado}}</span></template>
     <template #cell-acciones="{row}"><button class="size-8 rounded-lg text-muted hover:bg-soft" title="Abrir orden" aria-label="Abrir orden" @click="abrir(row)"><Eye :size="16" class="mx-auto" /></button><button class="size-8 rounded-lg text-muted hover:bg-soft" title="Ver PDF" aria-label="Ver PDF" @click="imprimir(row)"><Printer :size="16" class="mx-auto" /></button></template>
    </Tabla>
    <p class="border-t border-line px-4 py-3 text-xs text-muted">{{lista.length}} órdenes</p>
   </Tarjeta>
  </template>
 </div>
</template>
