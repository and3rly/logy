<script setup>
import { onMounted, onBeforeUnmount, ref, reactive, computed, watch } from 'vue'
import { X as Cerrar, Package as IconoModulo, Check as Confirmar, LoaderCircle as Cargando, Upload as Subir, Image as Imagen } from '@lucide/vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'
import { QuillEditor } from '@vueup/vue-quill'
import '@vueup/vue-quill/dist/vue-quill.snow.css'
const propiedades = defineProps({ registro: { type: Object, default: null } })
const emitir = defineEmits(['cerrar', 'guardada'])
const dialogo = ref(null)
const guardando = ref(false)
const cargandoCatalogos = ref(false)
const errorCatalogos = ref(false)
const catalogos = ref({})
const campos = [
  {
    "clave": "codigo",
    "label": "Código",
    "tipo": "text",
    "obligatorio": true
  },
  {
    "clave": "codigo_barra",
    "label": "Código de barras",
    "tipo": "text",
    "obligatorio": false
  },
  {
    "clave": "nombre",
    "label": "Nombre",
    "tipo": "text",
    "obligatorio": true
  },
  {
    "clave": "tipo_producto",
    "label": "Tipo de producto",
    "tipo": "select",
    "obligatorio": true,
    "catalogo": "tipos",
    "defecto": "B"
  },
  {
    "clave": "marca_id",
    "label": "Marca",
    "tipo": "select",
    "obligatorio": true,
    "catalogo": "marcas"
  },
  {
    "clave": "unidad_medida_id",
    "label": "Unidad de medida",
    "tipo": "select",
    "obligatorio": true,
    "catalogo": "unidades"
  },
  {
    "clave": "categoria_id",
    "label": "Categoría",
    "tipo": "select",
    "obligatorio": true,
    "catalogo": "categorias"
  },
  {
    "clave": "costo",
    "label": "Costo",
    "tipo": "number",
    "obligatorio": false,
    "defecto": 0,
    "paso": "any"
  },
  {
    "clave": "precio",
    "label": "Precio",
    "tipo": "number",
    "obligatorio": false,
    "defecto": 0,
    "paso": "any"
  },
  {
    "clave": "existencia_minima",
    "label": "Existencia mínima",
    "tipo": "number",
    "obligatorio": false,
    "defecto": 0,
    "paso": "any"
  },
  {
    "clave": "control_vence",
    "label": "Control vence",
    "tipo": "switch",
    "obligatorio": false,
    "defecto": 0
  },
  {
    "clave": "activo",
    "label": "Activo",
    "tipo": "switch",
    "obligatorio": false,
    "defecto": 1,
    "soloEditar": false
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
    const { data } = await api.get('index.php/mnt/producto/get_datos')
    if (!data.cat || !["tipos","marcas","unidades","categorias"].every(clave => Array.isArray(data.cat[clave]))) throw new Error('No se pudieron cargar los catálogos.')
    catalogos.value = data.cat
  } catch (problema) {
    errorCatalogos.value = true
    toast.error(problema.message || 'No se pudieron cargar los catálogos.')
  } finally { cargandoCatalogos.value = false }
}
onMounted(() => { dialogo.value.showModal(); cargarCatalogos() })
function cerrar() { if (!guardando.value) emitir('cerrar') }

formulario.descripcion = propiedades.registro?.descripcion || ''
const selectorImagen = ref(null)
const errorImagen = ref(false)
const archivo = ref(null)
const imagenLocal = ref('')
const imagen = computed(() => imagenLocal.value || (propiedades.registro?.foto ? 'https://lh3.googleusercontent.com/d/' + encodeURIComponent(propiedades.registro.foto) : ''))
function seleccionarImagen(evento) {
  const seleccion = evento.target.files?.[0]
  if (!seleccion) return
  if (!['image/jpeg','image/png','image/webp','image/gif'].includes(seleccion.type)) {
    toast.error('Selecciona una imagen JPG, PNG, WebP o GIF.')
    evento.target.value = ''
    return
  }
  if (imagenLocal.value) URL.revokeObjectURL(imagenLocal.value)
  errorImagen.value = false
  archivo.value = seleccion
  imagenLocal.value = URL.createObjectURL(seleccion)
}
function deshacerImagen() {
  if (imagenLocal.value) URL.revokeObjectURL(imagenLocal.value)
  imagenLocal.value = ''
  archivo.value = null
  errorImagen.value = false
  if (selectorImagen.value) selectorImagen.value.value = ''
}
onBeforeUnmount(() => { if (imagenLocal.value) URL.revokeObjectURL(imagenLocal.value) })

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
  datos.descripcion = formulario.descripcion
  const contenido = new FormData()
  Object.entries(datos).forEach(([clave, valor]) => contenido.append(clave, valor ?? ''))
  if (archivo.value) contenido.append('foto', archivo.value)

  guardando.value = true
  try {
    const id = propiedades.registro?.id ?? ''
    const { data: respuesta } = await api.post(`index.php/mnt/producto/guardar/${encodeURIComponent(id)}`, contenido, { headers: { 'Content-Type': undefined } })
    if (Number(respuesta.exito) !== 1 || !respuesta.linea?.id) throw new Error(respuesta.mensaje || 'No se pudo guardar.')
    emitir('guardada', respuesta.linea)
  } catch (problema) { toast.error(problema.message || 'No se pudo guardar.') }
  finally { guardando.value = false }
}
</script>

<template>
  <dialog ref="dialogo" class="maintenance-dialog m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%_-_2rem)] max-w-6xl overflow-y-auto rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-slate-950/50" aria-labelledby="titulo-registro" @cancel.prevent="cerrar">
    <form :aria-busy="guardando" @submit.prevent="guardar">
      <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3">
        <div class="flex items-center gap-3">
          <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent"><IconoModulo :size="20" aria-hidden="true" /></span>
          <div>
            <h2 id="titulo-registro" class="text-lg font-semibold tracking-tight">{{ registro ? 'Editar producto' : 'Nuevo producto' }}</h2>
          </div>
        </div>
        <button type="button" class="flex size-10 shrink-0 items-center justify-center rounded-xl text-muted transition-colors hover:bg-soft hover:text-ink" aria-label="Cerrar formulario" :disabled="guardando" @click="cerrar"><Cerrar :size="20" aria-hidden="true" /></button>
      </header>

      <div class="space-y-4 p-5">

        <p v-if="cargandoCatalogos" role="status" class="text-sm text-muted">Cargando…</p>
        <button v-if="errorCatalogos" type="button" class="rounded-lg border border-line px-3 py-2 text-sm" @click="cargarCatalogos">Reintentar carga de catálogos</button>
        <fieldset :disabled="guardando || cargandoCatalogos || errorCatalogos" class="grid min-w-0 grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1fr)_300px]">
          <legend class="sr-only">Información de producto</legend>
          <div class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
          <div v-for="campo in camposVisibles" :key="campo.clave" :class="campo.tipo === 'switch' ? 'flex min-h-10 items-center gap-3' : campo.clave === 'nombre' ? 'sm:col-span-2' : ''">
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
          </div>
          <section class="min-w-0 self-start rounded-xl border border-line bg-soft/30 p-4" aria-labelledby="titulo-imagen">
            <h3 id="titulo-imagen" class="mb-3 text-sm font-semibold">Imagen del producto</h3>
            <button type="button" class="group flex aspect-square w-full items-center justify-center overflow-hidden rounded-xl border border-dashed border-line bg-surface p-3 transition-colors hover:border-accent focus-visible:outline-accent" :aria-label="imagen ? 'Cambiar imagen del producto' : 'Seleccionar imagen del producto'" @click="selectorImagen.click()">
              <img v-if="imagen && !errorImagen" :src="imagen" alt="Vista previa del producto" class="max-h-full max-w-full object-contain" @error="errorImagen = true" />
              <span v-else class="flex flex-col items-center gap-3 text-muted"><Imagen :size="42" aria-hidden="true" /><span class="text-xs">{{ errorImagen ? 'Imagen no disponible' : 'Seleccionar imagen' }}</span></span>
            </button>
            <input id="producto-imagen" ref="selectorImagen" type="file" accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" aria-label="Archivo de imagen del producto" @change="seleccionarImagen" />
            <button type="button" class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg border border-line bg-surface px-3 text-sm font-medium hover:bg-soft" @click="selectorImagen.click()"><Subir :size="16" aria-hidden="true" />{{ imagen ? 'Cambiar imagen' : 'Cargar imagen' }}</button>
            <div v-if="archivo" class="mt-3 flex min-w-0 items-center gap-2">
              <span class="min-w-0 flex-1 truncate text-xs text-muted" :title="archivo.name">{{ archivo.name }}</span>
              <button type="button" class="flex size-8 shrink-0 items-center justify-center rounded-lg text-muted hover:bg-soft" title="Deshacer selección" aria-label="Deshacer selección de imagen" @click="deshacerImagen"><Cerrar :size="15" aria-hidden="true" /></button>
            </div>
          </section>
          <div class="min-w-0 lg:col-span-2">
            <p id="producto-descripcion" class="mb-2 block text-sm font-semibold">Descripción</p>
            <div role="group" aria-labelledby="producto-descripcion" class="overflow-hidden rounded-lg border border-line bg-surface text-ink [&_.ql-toolbar]:border-line! [&_.ql-container]:border-line! [&_.ql-editor]:min-h-28 dark:[&_.ql-stroke]:stroke-slate-300 dark:[&_.ql-fill]:fill-slate-300 dark:[&_.ql-picker]:text-slate-300">
              <QuillEditor v-model:content="formulario.descripcion" content-type="html" theme="snow" toolbar="essential" :read-only="guardando || cargandoCatalogos || errorCatalogos" :options="{ placeholder: '' }" />
            </div>
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
