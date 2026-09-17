<script setup>
import { onMounted, reactive, ref } from 'vue'
import { CircleDollarSign, Eye, Plus, RotateCcw, Search } from '@lucide/vue'
import Tarjeta from '../../components/ui/BaseCard.vue'
import Tabla from '../../components/ui/BaseTable.vue'
import Ruta from '../../components/ui/Breadcrumb.vue'
import NativePdfDialog from '../../components/ui/NativePdfDialog.vue'
import NuevaCuenta from './NuevaCuenta.vue'
import PagarCuenta from './PagarCuenta.vue'
import api from '../../services/api'
import { toast } from 'vue3-toastify'

const lista=ref([]), resumen=ref([]), catalogos=ref({proveedores:[],monedas:[],formas_pago:[]})
const cargando=ref(false), error=ref(false), verNueva=ref(false), seleccionada=ref(null)
const pdfUrl=ref('')
const urlApi=ruta=>new URL(ruta,new URL(api.defaults.baseURL,window.location.origin)).href
const filtros=reactive({termino:'',proveedor_id:'',moneda_id:'',estado:''})
const columnas=[
 {key:'factura_numero',label:'Factura'},{key:'nombre_proveedor',label:'Proveedor'},
 {key:'factura_fecha',label:'Emisión'},{key:'fecha_vence',label:'Vence'},
 {key:'total',label:'Total',class:'text-end'},{key:'abono',label:'Pagado',class:'text-end'},
 {key:'saldo',label:'Saldo',class:'text-end'},{key:'estado',label:'Estado'},{key:'acciones',label:'',class:'text-end'}]
const dinero=valor=>Number(valor||0).toLocaleString('es-GT',{minimumFractionDigits:2,maximumFractionDigits:2})
const clasesEstado=estado=>({PENDIENTE:'bg-accent/10 text-accent',PARCIAL:'bg-amber-500/10 text-amber-700 dark:text-amber-300',PAGADA:'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',VENCIDA:'bg-red-500/10 text-red-700 dark:text-red-300',ANULADA:'bg-slate-500/10 text-muted'}[estado]||'bg-soft text-muted')

async function cargarCatalogos(){const {data}=await api.get('index.php/cxp/cuenta_pagar/get_datos');if(!data.cat)throw new Error('No se pudieron cargar los catálogos.');catalogos.value=data.cat}
async function buscar(){
 if(cargando.value)return;cargando.value=true;error.value=false
 try{const {data}=await api.get('index.php/cxp/cuenta_pagar/buscar',{params:{...filtros}});if(!Array.isArray(data.lista))throw new Error(data.mensaje||'No se pudo cargar la cartera.');lista.value=data.lista;resumen.value=Array.isArray(data.resumen)?data.resumen:[]}
 catch(problema){error.value=true;toast.error(problema.message||'No se pudo cargar la cartera.')}finally{cargando.value=false}
}
function limpiar(){Object.assign(filtros,{termino:'',proveedor_id:'',moneda_id:'',estado:''});buscar()}
function agregar(){verNueva.value=false;buscar()}
function actualizar(){seleccionada.value=null;buscar()}
function imprimir(pago){pdfUrl.value=urlApi(`index.php/cxp/cuenta_pagar/imprimir/${encodeURIComponent(pago.id)}`)}
onMounted(async()=>{try{await cargarCatalogos();await buscar()}catch(problema){error.value=true;toast.error(problema.message)}})
</script>

<template>
 <div class="maintenance-screen payables-list">
  <NativePdfDialog v-if="pdfUrl" :url="pdfUrl" @close="pdfUrl=''" />
  <NuevaCuenta v-if="verNueva" :catalogos="catalogos" @cerrar="verNueva=false" @guardada="agregar" />
  <PagarCuenta v-if="seleccionada" :cuenta="seleccionada" :catalogos="catalogos" @cerrar="seleccionada=null" @actualizada="actualizar" @imprimir="imprimir" />
  <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div class="space-y-3"><Ruta :items="[{label:'Compras'},{label:'Cuentas por pagar'}]"/><h2 class="flex items-center gap-2 text-xl font-semibold"><CircleDollarSign :size="22" class="text-accent"/>Cuentas por pagar</h2></div><button class="btn-primary inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold" @click="verNueva=true"><Plus :size="16"/>Nueva cuenta</button></div>
  <div v-if="resumen.length" class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4"><Tarjeta v-for="item in resumen" :key="item.moneda_id"><p class="text-xs font-medium text-muted">Saldo {{item.codigo_moneda}}</p><strong class="mt-1 block text-xl tabular-nums">{{item.simbolo_moneda}} {{dinero(item.saldo)}}</strong><p class="mt-1 text-xs text-muted">Vencido: {{item.simbolo_moneda}} {{dinero(item.vencido)}}</p></Tarjeta></div>
  <Tarjeta no-padding>
   <form class="grid gap-3 border-b border-line p-4 sm:grid-cols-2 xl:grid-cols-[minmax(190px,1fr)_190px_150px_140px_auto_auto]" @submit.prevent="buscar">
    <div><label for="cxp-buscar" class="mb-1 block text-xs text-muted">Buscar</label><input id="cxp-buscar" v-model="filtros.termino" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm" placeholder="Factura, proveedor o identificación"/></div>
    <div><label for="cxp-proveedor" class="mb-1 block text-xs text-muted">Proveedor</label><select id="cxp-proveedor" v-model="filtros.proveedor_id" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm"><option value="">Todos</option><option v-for="item in catalogos.proveedores" :key="item.id" :value="item.id">{{item.nombre}}</option></select></div>
    <div><label for="cxp-moneda" class="mb-1 block text-xs text-muted">Moneda</label><select id="cxp-moneda" v-model="filtros.moneda_id" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm"><option value="">Todas</option><option v-for="item in catalogos.monedas" :key="item.id" :value="item.id">{{item.codigo}}</option></select></div>
    <div><label for="cxp-estado" class="mb-1 block text-xs text-muted">Estado</label><select id="cxp-estado" v-model="filtros.estado" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 text-sm"><option value="">Todos</option><option value="pendiente">Pendiente</option><option value="parcial">Parcial</option><option value="pagada">Pagada</option><option value="vencida">Vencida</option><option value="anulada">Anulada</option></select></div>
    <button type="submit" class="self-end inline-flex min-h-10 items-center justify-center gap-2 rounded-lg border border-line px-3 text-sm font-medium" :disabled="cargando"><Search :size="16"/>Buscar</button><button type="button" class="self-end grid size-10 place-items-center rounded-lg border border-line text-muted" :disabled="cargando" title="Limpiar" @click="limpiar"><RotateCcw :size="16"/></button>
   </form>
   <p v-if="cargando" class="p-10 text-center text-sm text-muted">Consultando cuentas…</p><div v-else-if="error" class="p-8 text-center"><button class="rounded-lg border border-line px-4 py-2 text-sm" @click="buscar">Reintentar</button></div>
   <Tabla v-else :columns="columnas" :rows="lista" empty-text="No se encontraron cuentas por pagar." class="[&_th]:px-3! [&_td]:px-3! [&_td]:text-[13px]">
    <template #cell-factura_numero="{row}"><button class="font-semibold text-accent" @click="seleccionada=row">{{row.factura_numero}}</button></template>
    <template #cell-factura_fecha="{value}">{{String(value||'').slice(0,10)}}</template><template #cell-fecha_vence="{value}">{{String(value||'').slice(0,10)}}</template>
    <template #cell-total="{row}"><span class="tabular-nums">{{row.simbolo_moneda}} {{dinero(row.total)}}</span></template><template #cell-abono="{row}"><span class="tabular-nums text-muted">{{row.simbolo_moneda}} {{dinero(row.abono)}}</span></template><template #cell-saldo="{row}"><strong class="tabular-nums">{{row.simbolo_moneda}} {{dinero(row.saldo)}}</strong></template>
    <template #cell-estado="{value}"><span class="rounded-full px-2 py-1 text-xs font-medium" :class="clasesEstado(value)">{{value}}</span></template><template #cell-acciones="{row}"><button class="grid size-8 place-items-center rounded-lg text-muted hover:bg-soft" title="Ver y pagar" :disabled="Number(row.saldo)<=0||Number(row.anulado)===1" @click="seleccionada=row"><Eye :size="16"/></button></template>
   </Tabla><p class="border-t border-line px-4 py-3 text-xs text-muted">{{lista.length}} cuentas</p>
  </Tarjeta>
 </div>
</template>
