<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { Check, FilePlus2, LoaderCircle, X } from '@lucide/vue'
import api from '../../services/api'
import { toast } from 'vue3-toastify'
const props=defineProps({catalogos:{type:Object,required:true}}),emit=defineEmits(['cerrar','guardada'])
const dialogo=ref(null),guardando=ref(false),hoy=new Date()
const fechaLocal=fecha=>[fecha.getFullYear(),String(fecha.getMonth()+1).padStart(2,'0'),String(fecha.getDate()).padStart(2,'0')].join('-')
const formulario=reactive({proveedor_id:'',moneda_id:'',factura_fecha:fechaLocal(hoy),factura_numero:'',factura_documento:'',credito_dias:0,total:'',referencia:''})
const proveedor=computed(()=>props.catalogos.proveedores.find(item=>String(item.id)===String(formulario.proveedor_id)))
const fechaVence=computed(()=>{if(!formulario.factura_fecha)return'';const fecha=new Date(`${formulario.factura_fecha}T12:00:00`);fecha.setDate(fecha.getDate()+Number(formulario.credito_dias||0));return fechaLocal(fecha)})
watch(proveedor,valor=>{if(valor)formulario.credito_dias=Number(valor.credito_dias||0)})
onMounted(()=>dialogo.value.showModal())
function cerrar(){if(!guardando.value)emit('cerrar')}
async function guardar(){
 if(guardando.value)return
 if(!formulario.proveedor_id||!formulario.moneda_id||!formulario.factura_fecha||!formulario.factura_numero.trim()||Number(formulario.total)<=0||Number(formulario.credito_dias)<0){toast.error('Completa y revisa los campos obligatorios.');return}
 guardando.value=true
 try{const {data}=await api.post('index.php/cxp/cuenta_pagar/guardar',{...formulario});if(Number(data.exito)!==1||!data.linea?.id)throw new Error(data.mensaje||'No se pudo crear la cuenta.');toast.success(data.mensaje);emit('guardada',data.linea)}
 catch(problema){toast.error(problema.message||'No se pudo crear la cuenta.')}finally{guardando.value=false}
}
</script>
<template>
 <dialog ref="dialogo" class="maintenance-dialog m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%_-_2rem)] max-w-3xl overflow-y-auto rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-slate-950/50" aria-labelledby="nueva-cxp-titulo" @cancel.prevent="cerrar">
  <form :aria-busy="guardando" @submit.prevent="guardar"><header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3"><div class="flex items-center gap-3"><span class="flex size-9 items-center justify-center rounded-lg bg-accent/10 text-accent"><FilePlus2 :size="20"/></span><h2 id="nueva-cxp-titulo" class="text-lg font-semibold">Nueva cuenta por pagar</h2></div><button type="button" class="flex size-10 items-center justify-center rounded-xl text-muted hover:bg-soft" :disabled="guardando" aria-label="Cerrar formulario" @click="cerrar"><X :size="20"/></button></header>
   <div class="space-y-4 p-5"><fieldset :disabled="guardando" class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2"><legend class="sr-only">Información de la cuenta por pagar</legend>
    <div class="sm:col-span-2"><label for="cxp-nueva-proveedor" class="mb-2 block text-sm font-semibold">Proveedor <span class="text-accent">*</span></label><select id="cxp-nueva-proveedor" v-model="formulario.proveedor_id" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm" required><option value="" disabled>Seleccionar proveedor</option><option v-for="item in catalogos.proveedores" :key="item.id" :value="item.id">{{item.nombre}}{{item.identificacion?` · ${item.identificacion}`:''}}</option></select></div>
    <div><label for="cxp-nueva-fecha" class="mb-2 block text-sm font-semibold">Fecha de factura <span class="text-accent">*</span></label><input id="cxp-nueva-fecha" v-model="formulario.factura_fecha" type="date" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm" required/></div>
    <div><label for="cxp-nueva-factura" class="mb-2 block text-sm font-semibold">Número de factura <span class="text-accent">*</span></label><input id="cxp-nueva-factura" v-model="formulario.factura_numero" maxlength="45" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm" required/></div>
    <div><label for="cxp-nueva-documento" class="mb-2 block text-sm font-semibold">Documento</label><input id="cxp-nueva-documento" v-model="formulario.factura_documento" maxlength="100" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm" placeholder="Serie, UUID u otra referencia"/></div>
    <div><label for="cxp-nueva-moneda" class="mb-2 block text-sm font-semibold">Moneda <span class="text-accent">*</span></label><select id="cxp-nueva-moneda" v-model="formulario.moneda_id" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm" required><option value="" disabled>Seleccionar moneda</option><option v-for="item in catalogos.monedas" :key="item.id" :value="item.id">{{item.nombre}} ({{item.codigo}})</option></select></div>
    <div><label for="cxp-nueva-dias" class="mb-2 block text-sm font-semibold">Días de crédito <span class="text-accent">*</span></label><input id="cxp-nueva-dias" v-model.number="formulario.credito_dias" type="number" min="0" step="1" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm" required/><p class="mt-1 text-xs text-muted">Vence: {{fechaVence||'—'}}</p></div>
    <div><label for="cxp-nueva-total" class="mb-2 block text-sm font-semibold">Total <span class="text-accent">*</span></label><input id="cxp-nueva-total" v-model="formulario.total" type="number" min="0.01" step="0.01" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-right text-sm tabular-nums" required/></div>
    <div class="sm:col-span-2"><label for="cxp-nueva-referencia" class="mb-2 block text-sm font-semibold">Referencia</label><textarea id="cxp-nueva-referencia" v-model="formulario.referencia" maxlength="300" rows="2" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm"></textarea></div>
   </fieldset></div>
   <footer class="flex flex-col-reverse gap-2 border-t border-line bg-soft/40 px-5 py-3 sm:flex-row sm:justify-end"><button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-line bg-surface px-5 text-sm font-semibold" :disabled="guardando" @click="cerrar">Cancelar</button><button type="submit" class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-5 text-sm font-semibold" :disabled="guardando"><LoaderCircle v-if="guardando" :size="17" class="animate-spin"/><Check v-else :size="17"/>{{guardando?'Guardando…':'Guardar'}}</button></footer>
  </form>
 </dialog>
</template>
