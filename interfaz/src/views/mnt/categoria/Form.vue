<script setup>
import { onMounted, ref, reactive } from 'vue'
import { X as Cerrar, FolderTree as IconoModulo, Check as Confirmar, LoaderCircle as Cargando } from '@lucide/vue'
import api from '../../../services/api'
import { toast } from 'vue3-toastify'
import { coloresCategoria } from '../../../utils/categoryColors'

const propiedades = defineProps({ registro: { type: Object, default: null } })
const emitir = defineEmits(['cerrar', 'guardada'])
const dialogo = ref(null)
const guardando = ref(false)
const colores = coloresCategoria
const formulario = reactive({
  etiqueta: propiedades.registro?.etiqueta || 'light',
  nombre: propiedades.registro?.nombre || '',
  activo: propiedades.registro ? Number(propiedades.registro.activo) : 1,
})

onMounted(() => dialogo.value.showModal())

function cerrar() {
  if (!guardando.value) emitir('cerrar')
}

async function guardar() {
  if (guardando.value) return
  const datos = {
    etiqueta: formulario.etiqueta,
    nombre: formulario.nombre.trim(),
    activo: formulario.activo,
  }
  if (!datos.nombre) {
    toast.error('Completa el nombre.')
    return
  }
  guardando.value = true
  try {
    const id = propiedades.registro?.id ?? ''
    const { data: respuesta } = await api.post(`index.php/mnt/categoria/guardar/${encodeURIComponent(id)}`, datos)
    if (Number(respuesta.exito) !== 1 || !respuesta.linea?.id) {
      throw new Error(respuesta.mensaje || 'No se pudo guardar la categoría.')
    }
    emitir('guardada', respuesta.linea)
  } catch (problema) {
    toast.error(problema.message || 'No se pudo guardar. Intenta nuevamente.')
  } finally {
    guardando.value = false
  }
}
</script>

<template>
  <dialog ref="dialogo" class="maintenance-dialog m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%_-_2rem)] max-w-lg overflow-y-auto rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-slate-950/50" aria-labelledby="titulo-registro" @cancel.prevent="cerrar">
    <form :aria-busy="guardando" @submit.prevent="guardar">
      <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-3">
        <div class="flex items-center gap-3">
          <span class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-accent/10 text-accent"><IconoModulo :size="20" aria-hidden="true" /></span>
          <div>
            <h2 id="titulo-registro" class="text-lg font-semibold tracking-tight">{{ registro ? 'Editar categoría' : 'Nueva categoría' }}</h2>
          </div>
        </div>
        <button type="button" class="flex size-10 shrink-0 items-center justify-center rounded-xl text-muted transition-colors hover:bg-soft hover:text-ink" aria-label="Cerrar formulario" :disabled="guardando" @click="cerrar"><Cerrar :size="20" aria-hidden="true" /></button>
      </header>

      <div class="space-y-4 p-5">
        <fieldset :disabled="guardando" class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
          <legend class="sr-only">Información de la categoría</legend>
          <div class="sm:col-span-2">
            <label for="registro-nombre" class="mb-2 block text-sm font-semibold">Nombre <span class="text-accent">*</span></label>
            <input id="registro-nombre" v-model="formulario.nombre" class="min-h-10 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink placeholder:text-muted focus:border-accent focus:outline-none focus:ring-4 focus:ring-accent/10" placeholder="Ej. Bebidas" required autofocus />
          </div>
          <div class="sm:col-span-2">
            <label for="categoria-color" class="mb-2 block text-sm font-semibold">Color <span class="text-accent">*</span></label>
            <div class="flex items-center gap-3">
              <select id="categoria-color" v-model="formulario.etiqueta" required class="min-h-10 min-w-0 flex-1 rounded-lg border border-line bg-surface px-3 py-2 text-sm focus:border-accent">
                <option v-for="color in colores" :key="color.valor" :value="color.valor">{{ color.nombre }}</option>
                <option v-if="!colores.some(color => color.valor === formulario.etiqueta)" :value="formulario.etiqueta">{{ formulario.etiqueta }}</option>
              </select>
              <span aria-hidden="true" class="size-8 rounded-lg border border-line" :style="{ backgroundColor: colores.find(color => color.valor === formulario.etiqueta)?.color }"></span>
            </div>
          </div>
          <div v-if="registro" class="flex items-center gap-4 sm:col-span-2">
            <label for="registro-activa" class="text-sm font-semibold">Activo</label>
            <div class="relative shrink-0">
              <input id="registro-activa" v-model="formulario.activo" class="peer sr-only" type="checkbox" role="switch" :true-value="1" :false-value="0" />
              <label for="registro-activa" class="flex min-h-11 cursor-pointer items-center rounded-full peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-accent peer-disabled:cursor-not-allowed peer-disabled:opacity-50">
                <span class="flex h-6 w-11 items-center rounded-full p-0.5 transition-colors" :class="formulario.activo === 1 ? 'bg-accent' : 'bg-muted'"><span class="size-5 rounded-full bg-white shadow-sm transition-transform motion-reduce:transition-none" :class="{ 'translate-x-5': formulario.activo === 1 }"></span></span>
              </label>
            </div>
          </div>
        </fieldset>

      </div>

      <footer class="flex flex-col-reverse gap-2 border-t border-line bg-soft/40 px-5 py-3 sm:flex-row sm:justify-end">
        <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-line bg-surface px-5 text-sm font-semibold transition-colors hover:bg-soft" :disabled="guardando" @click="cerrar">Cancelar</button>
        <button type="submit" class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-5 text-sm font-semibold" :disabled="guardando">
          <Cargando v-if="guardando" :size="17" class="animate-spin motion-reduce:animate-none" aria-hidden="true" /><Confirmar v-else :size="17" aria-hidden="true" />
          {{ guardando ? 'Guardando…' : 'Guardar' }}
        </button>
      </footer>
    </form>
  </dialog>
</template>
