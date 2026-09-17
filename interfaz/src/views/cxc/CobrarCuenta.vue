<script setup>
import { onMounted, reactive, ref } from 'vue'
import { Banknote, Check, LoaderCircle, Printer, X } from '@lucide/vue'
import api from '../../services/api'
import { toast } from 'vue3-toastify'

const props = defineProps({ cuenta: { type: Object, required: true }, catalogos: { type: Object, required: true } })
const emit = defineEmits(['cerrar', 'actualizada', 'imprimir'])
const dialogo = ref(null)
const guardando = ref(false)
const cargando = ref(false)
const pagos = ref([])
const hoy = new Date()
const fechaLocal = fecha => [fecha.getFullYear(), String(fecha.getMonth() + 1).padStart(2, '0'), String(fecha.getDate()).padStart(2, '0')].join('-')
const formulario = reactive({ forma_pago_id: '', total: Number(props.cuenta.saldo || 0).toFixed(2), documento_fecha: fechaLocal(hoy), documento_numero: '', documento_comprobante: '' })
const dinero = valor => Number(valor || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })

async function cargarPagos() {
  cargando.value = true
  try {
    const { data } = await api.get(`index.php/cxc/cuenta_cobrar/pagos/${encodeURIComponent(props.cuenta.id)}`)
    pagos.value = Array.isArray(data.lista) ? data.lista : []
  } catch (problema) {
    toast.error(problema.message || 'No se pudo cargar el historial de abonos.')
  } finally { cargando.value = false }
}

onMounted(() => { dialogo.value.showModal(); cargarPagos() })

function cerrar() {
  if (!guardando.value) emit('cerrar')
}

async function cobrar() {
  if (guardando.value) return
  const monto = Number(formulario.total)
  if (!formulario.forma_pago_id || !Number.isFinite(monto) || monto <= 0 || monto > Number(props.cuenta.saldo)) {
    toast.error('El abono debe ser mayor que cero y no superar el saldo.')
    return
  }
  guardando.value = true
  try {
    const { data } = await api.post(`index.php/cxc/cuenta_cobrar/pagar/${encodeURIComponent(props.cuenta.id)}`, { ...formulario })
    if (Number(data.exito) !== 1 || !data.linea?.id || !data.pago?.id) throw new Error(data.mensaje || 'No se pudo registrar el abono.')
    toast.success(data.mensaje)
    emit('actualizada', data.linea)
    emit('imprimir', data.pago)
    emit('cerrar')
  } catch (problema) {
    toast.error(problema.message || 'No se pudo registrar el abono.')
  } finally { guardando.value = false }
}
</script>

<template>
  <dialog ref="dialogo" class="maintenance-dialog m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%_-_2rem)] max-w-4xl overflow-y-auto rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-slate-950/50" aria-labelledby="cobrar-titulo" @cancel.prevent="cerrar">
    <form :aria-busy="guardando" @submit.prevent="cobrar">
      <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3"><div class="flex items-center gap-3"><span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent"><Banknote :size="20" aria-hidden="true" /></span><h2 id="cobrar-titulo" class="text-lg font-semibold tracking-tight">Cobrar factura {{ cuenta.factura_numero }}</h2></div><button type="button" class="flex size-10 shrink-0 items-center justify-center rounded-xl text-muted transition-colors hover:bg-soft hover:text-ink" :disabled="guardando" aria-label="Cerrar formulario" @click="cerrar"><X :size="20" aria-hidden="true" /></button></header>

      <div class="grid gap-5 p-5 lg:grid-cols-[1fr_0.9fr]">
        <fieldset :disabled="guardando" class="min-w-0 space-y-4">
          <legend class="sr-only">Datos del abono</legend>
          <div class="grid grid-cols-3 gap-2 rounded-xl border border-line bg-soft/40 p-4 text-sm"><div><span class="block text-xs text-muted">Total</span><strong>{{ cuenta.simbolo_moneda }} {{ dinero(cuenta.total) }}</strong></div><div><span class="block text-xs text-muted">Abonado</span><strong>{{ cuenta.simbolo_moneda }} {{ dinero(cuenta.abono) }}</strong></div><div><span class="block text-xs text-muted">Saldo</span><strong class="text-accent">{{ cuenta.simbolo_moneda }} {{ dinero(cuenta.saldo) }}</strong></div></div>
          <p class="text-sm font-medium text-ink">{{ cuenta.nombre_cliente }}</p>
          <div><label for="pago-forma" class="mb-2 block text-sm font-semibold">Forma de pago <span class="text-accent">*</span></label><select id="pago-forma" v-model="formulario.forma_pago_id" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" required><option value="" disabled>Seleccionar forma de pago</option><option v-for="item in catalogos.formas_pago" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
          <div class="grid gap-4 sm:grid-cols-2"><div><label for="pago-monto" class="mb-2 block text-sm font-semibold">Monto <span class="text-accent">*</span></label><input id="pago-monto" v-model="formulario.total" type="number" min="0.01" :max="cuenta.saldo" step="0.01" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-right text-sm tabular-nums text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" required /></div><div><label for="pago-fecha" class="mb-2 block text-sm font-semibold">Fecha documento</label><input id="pago-fecha" v-model="formulario.documento_fecha" type="date" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" /></div></div>
          <div><label for="pago-referencia" class="mb-2 block text-sm font-semibold">Referencia</label><input id="pago-referencia" v-model="formulario.documento_numero" maxlength="30" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" placeholder="Número de transferencia, cheque u otro" /></div>
          <div><label for="pago-nota" class="mb-2 block text-sm font-semibold">Nota o comprobante</label><textarea id="pago-nota" v-model="formulario.documento_comprobante" maxlength="250" rows="2" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10"></textarea></div>
        </fieldset>
        <section class="rounded-xl border border-line"><h3 class="border-b border-line px-4 py-3 text-sm font-semibold">Historial de abonos</h3><p v-if="cargando" class="p-6 text-center text-sm text-muted">Cargando…</p><p v-else-if="!pagos.length" class="p-6 text-center text-sm text-muted">Todavía no hay abonos.</p><ul v-else class="divide-y divide-line"><li v-for="pago in pagos" :key="pago.id" class="flex items-center justify-between gap-3 px-4 py-3"><div><strong class="block text-sm">{{ pago.recibo_numero }}</strong><span class="text-xs text-muted">{{ String(pago.fecha).slice(0, 10) }} · {{ pago.nombre_forma_pago }}</span></div><div class="text-right"><strong class="block text-sm tabular-nums">{{ cuenta.simbolo_moneda }} {{ dinero(pago.total) }}</strong><button type="button" class="inline-flex items-center gap-1 text-xs text-accent" @click="emit('imprimir', pago)"><Printer :size="13" />Recibo</button></div></li></ul></section>
      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-line bg-soft/40 px-5 py-3 sm:flex-row sm:justify-end"><button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-line bg-surface px-5 text-sm font-semibold transition-colors hover:bg-soft" :disabled="guardando" @click="cerrar">Cancelar</button><button type="submit" class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-5 text-sm font-semibold" :disabled="guardando"><LoaderCircle v-if="guardando" :size="17" class="animate-spin motion-reduce:animate-none" aria-hidden="true" /><Check v-else :size="17" aria-hidden="true" />{{ guardando ? 'Guardando…' : 'Registrar abono' }}</button></footer>
    </form>
  </dialog>
</template>
