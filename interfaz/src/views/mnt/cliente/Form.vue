<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { X as Cerrar, Users as IconoModulo, Check as Confirmar, LoaderCircle as Cargando } from '@lucide/vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'

const propiedades = defineProps({ registro: { type: Object, default: null } })
const emitir = defineEmits(['cerrar', 'guardada'])
const dialogo = ref(null)
const guardando = ref(false)
const cargandoCatalogos = ref(false)
const errorCatalogos = ref(false)
const catalogos = ref({})

const campos = [
  { clave: 'codigo', label: 'Código', tipo: 'text', maxlength: 10 },
  { clave: 'nombre', label: 'Nombre', tipo: 'text', obligatorio: true, maxlength: 150 },
  { clave: 'razon_social', label: 'Razón social', tipo: 'text', maxlength: 150 },
  { clave: 'identificacion', label: 'Identificación / NIT', tipo: 'text', maxlength: 20 },
  { clave: 'departamento_id', label: 'Departamento', tipo: 'select', catalogo: 'departamentos' },
  { clave: 'municipio_id', label: 'Municipio', tipo: 'select', catalogo: 'municipios' },
  { clave: 'direccion', label: 'Dirección', tipo: 'text', maxlength: 150 },
  { clave: 'telefono', label: 'Teléfono', tipo: 'tel', maxlength: 10, patron: '[0-9]+' },
  { clave: 'correo', label: 'Correo', tipo: 'email', maxlength: 70 },
  { clave: 'credito', label: 'Maneja crédito', tipo: 'switch', defecto: 0 },
  { clave: 'credito_limite', label: 'Límite de crédito', tipo: 'number', defecto: 0, condicion: 'credito', paso: '0.01' },
  { clave: 'credito_dias', label: 'Días de crédito', tipo: 'number', defecto: 0, condicion: 'credito', paso: '1' },
  { clave: 'activo', label: 'Activo', tipo: 'switch', defecto: 1, soloEditar: true },
]

const formulario = reactive(Object.fromEntries(campos.map(campo => [
  campo.clave,
  campo.tipo === 'switch' || campo.tipo === 'number'
    ? Number(propiedades.registro?.[campo.clave] ?? campo.defecto ?? 0)
    : propiedades.registro?.[campo.clave] ?? campo.defecto ?? (campo.tipo === 'select' ? null : ''),
])))

const camposVisibles = computed(() => campos.filter(campo =>
  (!campo.soloEditar || propiedades.registro) &&
  (!campo.condicion || Number(formulario[campo.condicion]) === 1)))

function opciones(campo) {
  const lista = catalogos.value[campo.catalogo] || []
  const filtradas = campo.clave === 'municipio_id'
    ? lista.filter(item => String(item.departamento_id) === String(formulario.departamento_id))
    : lista
  return filtradas.map(item => ({ valor: item.id, nombre: item.nombre }))
}

watch(() => formulario.departamento_id, (nuevo, anterior) => {
  if (anterior !== undefined && String(nuevo) !== String(anterior)) formulario.municipio_id = null
})

async function cargarCatalogos() {
  cargandoCatalogos.value = true
  errorCatalogos.value = false
  try {
    const { data } = await api.get('index.php/mnt/cliente/get_datos')
    if (!data.cat || !['departamentos', 'municipios'].every(clave => Array.isArray(data.cat[clave]))) {
      throw new Error('No se pudieron cargar los catálogos.')
    }
    catalogos.value = data.cat
  } catch (problema) {
    errorCatalogos.value = true
    toast.error(problema.message || 'No se pudieron cargar los catálogos.')
  } finally {
    cargandoCatalogos.value = false
  }
}

onMounted(() => {
  dialogo.value.showModal()
  cargarCatalogos()
})

function cerrar() {
  if (!guardando.value) emitir('cerrar')
}

async function guardar() {
  if (guardando.value || cargandoCatalogos.value || errorCatalogos.value) return

  const datos = {}
  for (const campo of campos) {
    const valor = formulario[campo.clave]
    datos[campo.clave] = typeof valor === 'string' ? (valor.trim() || null) : valor
  }

  if (!datos.nombre) {
    toast.error('Completa los campos obligatorios.')
    return
  }

  if (datos.telefono !== null && (!/^\d+$/.test(String(datos.telefono)) || Number(datos.telefono) > 2147483647)) {
    toast.error('El teléfono debe contener únicamente dígitos.')
    return
  }

  const numericos = camposVisibles.value.filter(campo => campo.tipo === 'number')
  if (numericos.some(campo => !Number.isFinite(Number(datos[campo.clave])) || Number(datos[campo.clave]) < 0 ||
    (campo.paso === '1' && !Number.isInteger(Number(datos[campo.clave]))))) {
    toast.error('Revisa los valores numéricos.')
    return
  }

  if (!Number(datos.credito)) {
    datos.credito_limite = 0
    datos.credito_dias = 0
  }

  guardando.value = true
  try {
    const id = propiedades.registro?.id ?? ''
    const { data: respuesta } = await api.post(`index.php/mnt/cliente/guardar/${encodeURIComponent(id)}`, datos)
    if (Number(respuesta.exito) !== 1 || !respuesta.linea?.id) throw new Error(respuesta.mensaje || 'No se pudo guardar el cliente.')
    emitir('guardada', respuesta.linea)
  } catch (problema) {
    toast.error(problema.message || 'No se pudo guardar el cliente.')
  } finally {
    guardando.value = false
  }
}
</script>

<template>
  <dialog ref="dialogo" class="maintenance-dialog m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%_-_2rem)] max-w-4xl overflow-y-auto rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-slate-950/50" aria-labelledby="titulo-cliente" @cancel.prevent="cerrar">
    <form :aria-busy="guardando" @submit.prevent="guardar">
      <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3">
        <div class="flex items-center gap-3">
          <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent"><IconoModulo :size="20" aria-hidden="true" /></span>
          <div>
            <h2 id="titulo-cliente" class="text-lg font-semibold tracking-tight">{{ registro ? 'Editar cliente' : 'Nuevo cliente' }}</h2>
            <p class="text-xs text-muted">Datos generales y condiciones comerciales</p>
          </div>
        </div>
        <button type="button" class="flex size-10 shrink-0 items-center justify-center rounded-xl text-muted transition-colors hover:bg-soft hover:text-ink" aria-label="Cerrar formulario" :disabled="guardando" @click="cerrar"><Cerrar :size="20" aria-hidden="true" /></button>
      </header>

      <div class="space-y-4 p-5">
        <p v-if="cargandoCatalogos" role="status" class="text-sm text-muted">Cargando ubicaciones…</p>
        <button v-if="errorCatalogos" type="button" class="rounded-lg border border-line px-3 py-2 text-sm" @click="cargarCatalogos">Reintentar carga de ubicaciones</button>

        <fieldset :disabled="guardando || cargandoCatalogos || errorCatalogos" class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
          <legend class="sr-only">Información del cliente</legend>
          <div v-for="campo in camposVisibles" :key="campo.clave" :class="campo.tipo === 'switch' ? 'flex min-h-10 items-center gap-3 self-end' : ''">
            <label :for="'cliente-' + campo.clave" class="block text-sm font-semibold" :class="{ 'mb-2': campo.tipo !== 'switch' }">{{ campo.label }} <span v-if="campo.obligatorio" class="text-accent">*</span></label>

            <input v-if="campo.tipo === 'switch'" :id="'cliente-' + campo.clave" v-model="formulario[campo.clave]" type="checkbox" role="switch" :true-value="1" :false-value="0" class="relative h-6 w-11 shrink-0 cursor-pointer appearance-none rounded-full bg-muted transition-colors before:absolute before:left-0.5 before:top-0.5 before:size-5 before:rounded-full before:bg-white before:transition-transform checked:bg-accent checked:before:translate-x-5" />

            <select v-else-if="campo.tipo === 'select'" :id="'cliente-' + campo.clave" v-model="formulario[campo.clave]" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10">
              <option :value="null">Sin especificar</option>
              <option v-for="opcion in opciones(campo)" :key="opcion.valor" :value="opcion.valor">{{ opcion.nombre }}</option>
              <option v-if="formulario[campo.clave] && !opciones(campo).some(opcion => String(opcion.valor) === String(formulario[campo.clave]))" :value="formulario[campo.clave]">Actual: {{ formulario[campo.clave] }}</option>
            </select>

            <input v-else :id="'cliente-' + campo.clave" v-model="formulario[campo.clave]" :type="campo.tipo" :required="campo.obligatorio" :maxlength="campo.maxlength" :pattern="campo.patron" :inputmode="campo.tipo === 'tel' ? 'numeric' : undefined" :min="campo.tipo === 'number' ? 0 : undefined" :step="campo.paso" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" />
          </div>
        </fieldset>
      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-line bg-soft/40 px-5 py-3 sm:flex-row sm:justify-end">
        <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-line bg-surface px-5 text-sm font-semibold transition-colors hover:bg-soft" :disabled="guardando" @click="cerrar">Cancelar</button>
        <button type="submit" class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-5 text-sm font-semibold" :disabled="guardando || cargandoCatalogos || errorCatalogos">
          <Cargando v-if="guardando" :size="17" class="animate-spin motion-reduce:animate-none" aria-hidden="true" /><Confirmar v-else :size="17" aria-hidden="true" />
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </footer>
    </form>
  </dialog>
</template>
