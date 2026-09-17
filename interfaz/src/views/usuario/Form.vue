<script setup>
import { onMounted, onBeforeUnmount, ref, reactive, computed, watch } from 'vue'
import { X as Cerrar, Users as IconoModulo, Check as Confirmar, LoaderCircle as Cargando } from '@lucide/vue'
import api from '../../services/api'
import { toast } from 'vue3-toastify'

const propiedades = defineProps({ registro: { type: Object, default: null } })
const emitir = defineEmits(['cerrar', 'guardada'])
const dialogo = ref(null)
const guardando = ref(false)
const cargandoCatalogos = ref(false)
const errorCatalogos = ref(false)
const catalogos = ref({})
const campos = [
  {
    "clave": "nombre",
    "label": "Nombre",
    "tipo": "text",
    "obligatorio": true
  },
  {
    "clave": "rol_id",
    "label": "Rol",
    "tipo": "select",
    "obligatorio": true,
    "catalogo": "roles"
  },
  {
    "clave": "alias",
    "label": "Usuario",
    "tipo": "text",
    "obligatorio": true
  },
  {
    "clave": "clave",
    "label": "Clave",
    "tipo": "password",
    "obligatorio": true,
    "soloNuevo": true
  },
  {
    "clave": "correo",
    "label": "Correo",
    "tipo": "email",
    "obligatorio": false
  },
  {
    "clave": "telefono",
    "label": "Teléfono",
    "tipo": "tel",
    "obligatorio": false
  },
  {
    "clave": "activo",
    "label": "Activo",
    "tipo": "switch",
    "obligatorio": false,
    "defecto": 1,
    "soloEditar": true
  }
]
const formulario = reactive(Object.fromEntries(campos.map(campo => [
  campo.clave, campo.tipo === 'password' ? '' : campo.tipo === 'switch' || campo.tipo === 'number'
    ? Number(propiedades.registro?.[campo.clave] ?? campo.defecto ?? 0)
    : propiedades.registro?.[campo.clave] ?? campo.defecto ?? (campo.tipo === 'select' ? null : '')
])))
const camposVisibles = computed(() => campos.filter(campo =>
  (!campo.soloNuevo || !propiedades.registro) && (!campo.soloEditar || propiedades.registro) &&
  (!campo.condicion || Number(formulario[campo.condicion]) === 1)))
function opciones(campo) {
  const lista = catalogos.value[campo.catalogo] || []
  const filtradas = campo.clave === 'municipio_id'
    ? lista.filter(item => String(item.departamento_id) === String(formulario.departamento_id)) : lista
  return filtradas.map(item => typeof item === 'object' ? { valor: item.id, nombre: item.nombre } : { valor: item, nombre: item })
}

async function cargarCatalogos() {
  cargandoCatalogos.value = true
  errorCatalogos.value = false
  try {
    const { data } = await api.get('index.php/usuario/get_datos')
    if (!data.cat || !["roles"].every(clave => Array.isArray(data.cat[clave]))) throw new Error('No se pudieron cargar los catálogos.')
    catalogos.value = data.cat
  } catch (problema) {
    errorCatalogos.value = true
    toast.error(problema.message || 'No se pudieron cargar los catálogos.')
  } finally { cargandoCatalogos.value = false }
}
onMounted(() => { dialogo.value.showModal(); cargarCatalogos() })
function cerrar() { if (!guardando.value) emitir('cerrar') }

async function guardar() {
  if (guardando.value || cargandoCatalogos.value || errorCatalogos.value) return
  const datos = {}
  for (const campo of campos) {
    if (campo.soloNuevo && propiedades.registro) continue
    const valor = formulario[campo.clave]
    datos[campo.clave] = typeof valor === 'string' && campo.tipo !== 'password' ? valor.trim() : valor
  }
  if (camposVisibles.value.some(campo => campo.obligatorio && !datos[campo.clave])) {
    toast.error('Completa los campos obligatorios.')
    return
  }
  if (camposVisibles.value.some(campo => campo.tipo === 'number' &&
    (!Number.isFinite(Number(datos[campo.clave])) || Number(datos[campo.clave]) < 0 ||
    (campo.paso === '1' && !Number.isInteger(Number(datos[campo.clave])))))) {
    toast.error('Revisa los valores numéricos.')
    return
  }

  guardando.value = true
  try {
    const id = propiedades.registro?.id ?? ''
    const { data: respuesta } = await api.post(`index.php/usuario/guardar/${encodeURIComponent(id)}`, datos)
    if (Number(respuesta.exito) !== 1 || !respuesta.linea?.id) throw new Error(respuesta.mensaje || 'No se pudo guardar.')
    emitir('guardada', respuesta.linea)
  } catch (problema) { toast.error(problema.message || 'No se pudo guardar.') }
  finally { guardando.value = false }
}
</script>

<template>
  <dialog ref="dialogo" class="maintenance-dialog m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%_-_2rem)] max-w-3xl overflow-y-auto rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-slate-950/50" aria-labelledby="titulo-registro" @cancel.prevent="cerrar">
    <form :aria-busy="guardando" @submit.prevent="guardar">
      <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3">
        <div class="flex items-center gap-3">
          <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent"><IconoModulo :size="20" aria-hidden="true" /></span>
          <div>
            <h2 id="titulo-registro" class="text-lg font-semibold tracking-tight">{{ registro ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
          </div>
        </div>
        <button type="button" class="flex size-10 shrink-0 items-center justify-center rounded-xl text-muted transition-colors hover:bg-soft hover:text-ink" aria-label="Cerrar formulario" :disabled="guardando" @click="cerrar"><Cerrar :size="20" aria-hidden="true" /></button>
      </header>

      <div class="space-y-4 p-5">

        <p v-if="cargandoCatalogos" role="status" class="text-sm text-muted">Cargando…</p>
        <button v-if="errorCatalogos" type="button" class="rounded-lg border border-line px-3 py-2 text-sm" @click="cargarCatalogos">Reintentar carga de catálogos</button>
        <fieldset :disabled="guardando || cargandoCatalogos || errorCatalogos" class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
          <legend class="sr-only">Información de usuario</legend>
          <div v-for="campo in camposVisibles" :key="campo.clave" :class="campo.tipo === 'switch' ? 'flex min-h-10 items-center gap-3' : ''">
            <label :for="'campo-' + campo.clave" class="block text-sm font-semibold" :class="{ 'mb-2': campo.tipo !== 'switch' }">{{ campo.label }} <span v-if="campo.obligatorio" class="text-accent">*</span></label>
            <template v-if="campo.tipo === 'switch'">
              <input :id="'campo-' + campo.clave" v-model="formulario[campo.clave]" type="checkbox" role="switch" :true-value="1" :false-value="0" class="relative h-6 w-11 shrink-0 cursor-pointer appearance-none rounded-full bg-muted transition-colors before:absolute before:left-0.5 before:top-0.5 before:size-5 before:rounded-full before:bg-white before:transition-transform checked:bg-accent checked:before:translate-x-5" />
            </template>
            <select v-else-if="campo.tipo === 'select'" :id="'campo-' + campo.clave" v-model="formulario[campo.clave]" :required="campo.obligatorio" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10">
              <option :value="null" disabled>Seleccionar</option>
              <option v-for="opcion in opciones(campo)" :key="opcion.valor" :value="opcion.valor">{{ opcion.nombre }}</option>
              <option v-if="formulario[campo.clave] && !opciones(campo).some(opcion => String(opcion.valor) === String(formulario[campo.clave]))" :value="formulario[campo.clave]">Actual: {{ formulario[campo.clave] }}</option>
            </select>
            <input v-else :id="'campo-' + campo.clave" v-model="formulario[campo.clave]" :type="campo.tipo" :required="campo.obligatorio" :min="campo.tipo === 'number' ? 0 : undefined" :step="campo.paso" :autocomplete="campo.tipo === 'password' ? 'new-password' : undefined" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink focus:border-accent focus:outline-none focus:ring-2 focus:ring-accent/10" />
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
