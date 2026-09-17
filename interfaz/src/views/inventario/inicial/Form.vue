<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { ArrowLeft, Ban, CheckCircle2, Download, FileSpreadsheet, LoaderCircle, Save, Upload, XCircle } from '@lucide/vue'
import Tabla from '../../../components/ui/BaseTable.vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'

const propiedades = defineProps({ inventario: { type: Object, default: null } })
const emitir = defineEmits(['cerrar', 'actualizar'])
const actual = ref(propiedades.inventario)
const formulario = reactive({ sucursal_id: propiedades.inventario?.sucursal_id ?? null, observacion: propiedades.inventario?.observacion || '' })
const catalogos = ref({ estados: [], sucursales: [] })
const archivo = ref(null)
const lineas = ref([])
const resumen = ref({ filas: 0, validas: 0, nuevos: 0, errores: 0 })
const archivoNombre = ref(propiedades.inventario?.archivo_nombre || '')
const cargando = ref(true)
const ocupado = ref(false)
const validado = ref(Boolean(propiedades.inventario))
const archivoInput = ref(null)
const confirmacion = ref(null)
const accion = ref('')

const columnas = [
  { key: 'fila', label: 'Fila', class: 'text-center' }, { key: 'codigo_producto', label: 'Código' },
  { key: 'nombre_producto', label: 'Producto' }, { key: 'marca', label: 'Marca' },
  { key: 'clasificacion', label: 'Clasificación' }, { key: 'unidad_codigo', label: 'Unidad' },
  { key: 'fecha_vence', label: 'Vencimiento' }, { key: 'cantidad', label: 'Cantidad', class: 'text-end' },
  { key: 'estado', label: 'Resultado' }, { key: 'mensaje', label: 'Detalle' },
]

const codigoEstado = computed(() => actual.value?.codigo_estado || '')
const esNuevo = computed(() => !actual.value)
const puedeGuardar = computed(() => esNuevo.value && validado.value && resumen.value.errores === 0 && archivo.value && !ocupado.value)
const nombreSucursal = computed(() => catalogos.value.sucursales.find(item => String(item.id) === String(formulario.sucursal_id))?.nombre || actual.value?.nombre_sucursal || '—')
const formatoCantidad = valor => Number(valor || 0).toLocaleString('es-GT', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
function invalidarValidacion() {
  if (!esNuevo.value || !archivo.value) return
  validado.value = false
  lineas.value = []
  resumen.value = { filas: 0, validas: 0, nuevos: 0, errores: 0 }
}

async function leerArchivo(evento) {
  const archivoSeleccionado = evento.target.files?.[0]
  evento.target.value = ''
  if (!archivoSeleccionado) return
  if (!/\.xlsx$/i.test(archivoSeleccionado.name)) return toast.error('Selecciona un archivo con extensión .xlsx.')
  if (archivoSeleccionado.size > 5 * 1024 * 1024) return toast.error('El archivo no puede superar 5 MB.')
  archivo.value = archivoSeleccionado
  archivoNombre.value = archivoSeleccionado.name
  lineas.value = []
  validado.value = false
  await validarArchivo()
}

async function validarArchivo() {
  if (!formulario.sucursal_id) {
    toast.error('Selecciona la sucursal antes de validar el archivo.')
    return
  }
  if (!archivo.value) return
  ocupado.value = true
  try {
    const contenido = new FormData()
    contenido.append('sucursal_id', formulario.sucursal_id)
    contenido.append('archivo', archivo.value)
    const { data } = await api.post('index.php/inventario/inventario_inicial/validar', contenido, { headers: { 'Content-Type': undefined } })
    if (!Array.isArray(data.lista) || !data.resumen) throw new Error(data.mensaje || 'No se pudo validar el archivo.')
    lineas.value = data.lista.map(linea => ({
      ...linea,
      mensaje: [...(linea.errores || []), ...(linea.advertencias || [])].join(' '),
    }))
    resumen.value = data.resumen
    validado.value = Number(data.exito) === 1
    if (validado.value) toast.success(data.mensaje)
    else toast.error(data.mensaje || 'Corrige las filas marcadas.')
  } catch (problema) {
    validado.value = false
    toast.error(problema.message || 'No se pudo validar el archivo.')
  } finally {
    ocupado.value = false
  }
}

async function cargar() {
  cargando.value = true
  try {
    const [datos, detalle] = await Promise.all([
      api.get('index.php/inventario/inventario_inicial/get_datos'),
      actual.value ? api.get(`index.php/inventario/inventario_inicial/detalle/${encodeURIComponent(actual.value.id)}`) : Promise.resolve({ data: { lista: [] } }),
    ])
    if (!datos.data.cat || !Array.isArray(detalle.data.lista)) throw new Error('No se pudo cargar el inventario inicial.')
    catalogos.value = datos.data.cat
    if (actual.value) {
      actual.value = detalle.data.encabezado || actual.value
      formulario.sucursal_id = actual.value.sucursal_id
      formulario.observacion = actual.value.observacion || ''
      archivoNombre.value = actual.value.archivo_nombre || ''
      lineas.value = detalle.data.lista.map((linea, indice) => ({
        ...linea,
        fila: indice + 1,
        codigo_producto: linea.codigo,
        marca: linea.nombre_marca,
        clasificacion: linea.nombre_categoria,
        unidad_codigo: linea.codigo_um,
        estado: 'REGISTRADO',
        mensaje: linea.observacion || '',
      }))
      resumen.value = { filas: lineas.value.length, validas: lineas.value.length, nuevos: 0, errores: 0 }
    }
  } catch (problema) {
    toast.error(problema.message || 'No se pudo cargar el inventario inicial.')
  } finally {
    cargando.value = false
  }
}

async function guardar() {
  if (!puedeGuardar.value) return
  if (formulario.observacion.length > 300) return toast.error('La observación no puede superar 300 caracteres.')
  ocupado.value = true
  try {
    const contenido = new FormData()
    contenido.append('sucursal_id', formulario.sucursal_id)
    contenido.append('observacion', formulario.observacion)
    contenido.append('archivo', archivo.value)
    const { data } = await api.post('index.php/inventario/inventario_inicial/guardar', contenido, { headers: { 'Content-Type': undefined } })
    if (Number(data.exito) !== 1 || !data.linea?.id) throw new Error(data.mensaje || 'No se pudo guardar el inventario inicial.')
    toast.success(data.mensaje)
    emitir('actualizar', data.linea)
  } catch (problema) {
    toast.error(problema.message || 'No se pudo guardar el inventario inicial.')
  } finally {
    ocupado.value = false
  }
}

function solicitar(tipo) {
  if (ocupado.value) return
  accion.value = tipo
  confirmacion.value.showModal()
}

async function ejecutar() {
  const tipo = accion.value
  if (tipo === 'salir') {
    confirmacion.value.close()
    emitir('cerrar')
    return
  }
  if (!['procesar', 'anular'].includes(tipo) || !actual.value?.id) return
  ocupado.value = true
  try {
    const { data } = await api.post(`index.php/inventario/inventario_inicial/${tipo}/${encodeURIComponent(actual.value.id)}`)
    if (Number(data.exito) !== 1 || !data.linea?.id) throw new Error(data.mensaje || 'No se pudo completar la operación.')
    toast.success(data.mensaje)
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
  <div class="maintenance-screen inventory-initial-form">
    <header class="mb-5 flex flex-wrap items-end justify-between gap-4">
      <div>
        <button class="mb-3 inline-flex items-center gap-2 text-xs font-semibold text-muted hover:text-accent" :disabled="ocupado" @click="esNuevo && archivo ? solicitar('salir') : emitir('cerrar')"><ArrowLeft :size="15" />Inventario / Inventario inicial</button>
        <div class="flex items-center gap-3"><span class="grid size-11 place-items-center rounded-xl border border-line bg-accent/5 text-accent"><FileSpreadsheet :size="22" /></span><div><h1 class="text-xl font-bold">{{ actual?.numero || 'Nueva carga de inventario inicial' }}</h1><p class="mt-1 text-xs text-muted">{{ actual ? `${actual.nombre_estado} · ${nombreSucursal}` : 'Valida un Excel antes de registrar las existencias de apertura' }}</p></div></div>
      </div>
      <div class="flex flex-wrap gap-2">
        <button v-if="actual && codigoEstado === 'VALIDADO'" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-emerald-500/30 bg-emerald-500/5 px-4 text-sm font-semibold text-emerald-700" :disabled="ocupado" @click="solicitar('procesar')"><CheckCircle2 :size="17" />Procesar inventario</button>
        <button v-if="actual && ['BORRADOR', 'VALIDADO'].includes(codigoEstado)" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-red-500/30 px-4 text-sm font-semibold text-red-600" :disabled="ocupado" @click="solicitar('anular')"><Ban :size="17" />Anular</button>
        <button v-if="esNuevo" class="btn-primary inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold" :disabled="!puedeGuardar" @click="guardar"><LoaderCircle v-if="ocupado" :size="17" class="animate-spin" /><Save v-else :size="17" />Guardar validación</button>
      </div>
    </header>

    <div v-if="cargando" class="grid min-h-72 place-items-center rounded-xl border border-line bg-surface"><div class="text-center"><LoaderCircle :size="28" class="mx-auto animate-spin text-accent" /><p class="mt-3 text-sm text-muted">Cargando inventario…</p></div></div>
    <div v-else class="space-y-5">
      <section class="rounded-xl border border-line bg-surface">
        <header class="border-b border-line px-5 py-4"><h2 class="font-semibold">Datos de la carga</h2><p class="mt-1 text-xs text-muted">Solo se admite inventario inicial para productos con existencia cero en la sucursal.</p></header>
        <fieldset :disabled="!esNuevo || ocupado" class="grid gap-4 p-5 md:grid-cols-2">
          <div><label for="inventario-inicial-sucursal" class="mb-2 block text-xs font-semibold text-muted">Sucursal *</label><select id="inventario-inicial-sucursal" v-model="formulario.sucursal_id" class="min-h-11 w-full rounded-lg border border-line bg-surface px-3 text-sm" @change="invalidarValidacion"><option :value="null" disabled>Seleccionar sucursal</option><option v-for="item in catalogos.sucursales" :key="item.id" :value="item.id">{{ item.nombre }}</option></select></div>
          <div><label class="mb-2 block text-xs font-semibold text-muted">Archivo</label><div class="flex min-h-11 items-center rounded-lg border border-line bg-soft/30 px-3 text-sm"><span class="truncate">{{ archivoNombre || 'Sin archivo seleccionado' }}</span></div></div>
          <div class="md:col-span-2"><label for="inventario-inicial-observacion" class="mb-2 block text-xs font-semibold text-muted">Observación</label><textarea id="inventario-inicial-observacion" v-model="formulario.observacion" maxlength="300" rows="2" class="w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm" placeholder="Referencia opcional de la carga"></textarea></div>
        </fieldset>
      </section>

      <section v-if="esNuevo" class="rounded-xl border border-line bg-surface p-5">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <div><h2 class="font-semibold">Archivo de inventario</h2><p class="mt-1 text-xs text-muted">Utiliza la plantilla, no cambies sus encabezados y carga hasta 1,000 filas.</p></div>
          <div class="flex flex-wrap gap-2"><a href="/plantillas/inventario_inicial.xlsx" download class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-line px-4 text-sm font-semibold"><Download :size="16" />Descargar plantilla</a><button class="btn-primary inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold" :disabled="ocupado || !formulario.sucursal_id" @click="archivoInput.click()"><Upload :size="16" />Seleccionar Excel</button><input ref="archivoInput" type="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" class="hidden" @change="leerArchivo" /></div>
        </div>
        <div v-if="archivo && !validado" class="mt-4 flex items-center justify-between gap-3 rounded-lg border border-amber-500/30 bg-amber-500/5 p-3 text-sm text-amber-800"><span>La validación debe repetirse porque cambió la sucursal o el archivo contiene errores.</span><button class="rounded-lg border border-amber-500/30 px-3 py-2 font-semibold" :disabled="ocupado" @click="validarArchivo">Validar nuevamente</button></div>
      </section>

      <section class="overflow-hidden rounded-xl border border-line bg-surface">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-line px-5 py-4"><div><h2 class="font-semibold">Detalle</h2><p class="mt-1 text-xs text-muted">La validación del servidor determina qué productos existen y cuáles se crearán.</p></div><div class="flex flex-wrap gap-2 text-xs"><span class="rounded-full bg-soft px-3 py-1 font-semibold">{{ resumen.filas }} filas</span><span class="rounded-full bg-emerald-500/10 px-3 py-1 font-semibold text-emerald-700">{{ resumen.validas }} válidas</span><span v-if="resumen.nuevos" class="rounded-full bg-blue-500/10 px-3 py-1 font-semibold text-blue-700">{{ resumen.nuevos }} nuevos</span><span v-if="resumen.errores" class="rounded-full bg-red-500/10 px-3 py-1 font-semibold text-red-700">{{ resumen.errores }} errores</span></div></header>
        <Tabla :columns="columnas" :rows="lineas" row-key="fila" empty-text="Carga un archivo para visualizar su validación." class="[&_th]:px-3! [&_td]:px-3! [&_td]:py-2! [&_td]:text-xs">
          <template #cell-codigo_producto="{ value }"><span class="rounded bg-soft px-2 py-1 font-mono">{{ value }}</span></template>
          <template #cell-nombre_producto="{ value }"><strong class="block min-w-44 whitespace-normal">{{ value }}</strong></template>
          <template #cell-fecha_vence="{ value }"><span class="text-muted">{{ value?.slice(0, 10) || '—' }}</span></template>
          <template #cell-cantidad="{ value }"><span class="tabular-nums font-semibold">{{ formatoCantidad(value) }}</span></template>
          <template #cell-estado="{ value }"><span class="rounded-full px-2 py-1 text-[11px] font-bold" :class="value === 'ERROR' ? 'bg-red-500/10 text-red-700' : value === 'NUEVO' ? 'bg-blue-500/10 text-blue-700' : 'bg-emerald-500/10 text-emerald-700'">{{ value }}</span></template>
          <template #cell-mensaje="{ value }"><span class="block max-w-96 whitespace-normal" :class="value ? 'text-amber-700' : 'text-muted'">{{ value || 'Sin observaciones' }}</span></template>
        </Tabla>
      </section>
    </div>

    <dialog ref="confirmacion" class="m-auto max-h-[calc(100dvh-2rem)] w-[min(92vw,430px)] overflow-auto rounded-xl border border-line bg-surface p-0 text-default shadow-2xl backdrop:bg-black/40">
      <div class="border-b border-line px-5 py-4"><h2 class="font-semibold">{{ accion === 'procesar' ? 'Procesar inventario inicial' : accion === 'anular' ? 'Anular inventario inicial' : 'Salir sin guardar' }}</h2></div>
      <div class="p-5 text-sm text-muted"><p v-if="accion === 'procesar'">Se crearán las existencias en stock y los movimientos positivos. Esta operación no se puede editar después.</p><p v-else-if="accion === 'anular'">El inventario quedará anulado y no modificará el stock.</p><p v-else>El archivo y la validación actual se descartarán.</p></div>
      <div class="flex justify-end gap-2 border-t border-line px-5 py-4"><button class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-line px-4 text-sm font-semibold" :disabled="ocupado" @click="confirmacion.close()"><XCircle :size="16" />Cancelar</button><button class="btn-primary inline-flex min-h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold" :disabled="ocupado" @click="ejecutar"><LoaderCircle v-if="ocupado" :size="16" class="animate-spin" />Confirmar</button></div>
    </dialog>
  </div>
</template>
