<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { Check, Layers3, LoaderCircle } from '@lucide/vue'
import api from '../../services/api'
import { toast } from 'vue3-toastify'

const props = defineProps({ registro: { type: Object, default: null } })
const emit = defineEmits(['cerrar', 'guardada'])
const router = useRouter()
const dialogo = ref(null)
const guardando = ref(false)
const iconos = ['fa-solid fa-layer-group','fa-solid fa-list','fa-solid fa-cart-shopping','fa-solid fa-boxes-stacked','fa-solid fa-cash-register','fa-solid fa-chart-line','fa-solid fa-users','fa-solid fa-gear']
const rutas = router.getRoutes().map((ruta) => ruta.path).filter((ruta) => ruta !== '/' && !ruta.includes(':') && !ruta.includes('*')).sort()
const formulario = reactive({
  nombre: props.registro?.nombre || '',
  icono: props.registro?.icono || 'fa-solid fa-layer-group',
  url: props.registro?.url || '',
  orden: Number(props.registro?.orden ?? 0),
  detalle: props.registro ? Number(props.registro.detalle) : 1,
  activo: props.registro ? Number(props.registro.activo) : 1,
})
const esGrupo = computed(() => Number(formulario.detalle) === 1)

onMounted(() => dialogo.value.showModal())
function cerrar(){ if(!guardando.value) emit('cerrar') }
function normalizarRuta(){ if(formulario.url && !formulario.url.startsWith('/')) formulario.url = `/${formulario.url}` }
async function guardar(){
  if(guardando.value) return
  normalizarRuta()
  const datos = { ...formulario, nombre: formulario.nombre.trim(), icono: formulario.icono.trim(), url: esGrupo.value ? null : formulario.url.trim() }
  if(!datos.nombre || (!esGrupo.value && !datos.url)){ toast.error('Completa los campos requeridos.'); return }
  guardando.value = true
  try{
    const id = props.registro?.id ?? ''
    const { data } = await api.post(`index.php/modulo/guardar/${encodeURIComponent(id)}`, datos)
    if(Number(data.exito)!==1 || !data.linea?.id) throw new Error(data.mensaje || 'No se pudo guardar el módulo.')
    emit('guardada', data.linea)
  }catch(problema){ toast.error(problema.message || 'No se pudo guardar.') }
  finally{ guardando.value = false }
}
</script>

<template>
  <dialog ref="dialogo" class="maintenance-dialog m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%_-_2rem)] max-w-2xl overflow-y-auto rounded-xl border border-line bg-surface p-0 text-ink" aria-labelledby="titulo-modulo" @cancel.prevent="cerrar">
    <form :aria-busy="guardando" @submit.prevent="guardar">
      <header class="flex items-center justify-between gap-3 border-b border-line">
        <div class="flex items-center gap-3"><span class="flex shrink-0 items-center justify-center"><Layers3 :size="19" /></span><h2 id="titulo-modulo">{{ registro ? 'Editar módulo' : 'Nuevo módulo' }}</h2></div>
        <button type="button" class="cerrar-dialogo" aria-label="Cerrar" :disabled="guardando" @click="cerrar"></button>
      </header>
      <div>
        <fieldset :disabled="guardando" class="grid min-w-0 grid-cols-1 gap-4 sm:grid-cols-2">
          <div class="sm:col-span-2"><label for="modulo-nombre" class="mb-2 block">Nombre <b class="text-accent">*</b></label><input id="modulo-nombre" v-model="formulario.nombre" class="w-full border border-line bg-surface px-3" placeholder="Ej. Inventario" required autofocus /></div>
          <div><label for="modulo-icono" class="mb-2 block">Icono</label><div class="menu-form-icon"><i :class="formulario.icono || 'fa-solid fa-layer-group'"></i><input id="modulo-icono" v-model="formulario.icono" list="iconos-modulo" class="w-full border border-line bg-surface px-3" placeholder="fa-solid fa-layer-group" /></div><datalist id="iconos-modulo"><option v-for="icono in iconos" :key="icono" :value="icono" /></datalist></div>
          <div><label for="modulo-orden" class="mb-2 block">Orden <b class="text-accent">*</b></label><input id="modulo-orden" v-model.number="formulario.orden" type="number" min="0" class="w-full border border-line bg-surface px-3" required /></div>
          <div class="sm:col-span-2"><label for="modulo-tipo" class="mb-2 block">Tipo</label><select id="modulo-tipo" v-model.number="formulario.detalle" class="w-full border border-line bg-surface px-3"><option :value="1">Grupo con opciones</option><option :value="0">Acceso directo</option></select></div>
          <div v-if="!esGrupo" class="sm:col-span-2"><label for="modulo-ruta" class="mb-2 block">Ruta <b class="text-accent">*</b></label><input id="modulo-ruta" v-model="formulario.url" list="rutas-modulo" class="w-full border border-line bg-surface px-3" placeholder="/ruta" required @blur="normalizarRuta" /><datalist id="rutas-modulo"><option v-for="ruta in rutas" :key="ruta" :value="ruta" /></datalist></div>
          <div class="flex items-center gap-3 sm:col-span-2"><label for="modulo-activo">Activo</label><input id="modulo-activo" v-model="formulario.activo" class="menu-switch" type="checkbox" role="switch" :true-value="1" :false-value="0" /></div>
        </fieldset>
      </div>
      <footer class="flex flex-col-reverse gap-2 border-t border-line sm:flex-row sm:justify-end"><button type="button" class="min-h-10 rounded-lg border border-line bg-surface px-5 text-sm font-semibold hover:bg-soft" :disabled="guardando" @click="cerrar">Cancelar</button><button type="submit" class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-5 text-sm font-semibold" :disabled="guardando"><LoaderCircle v-if="guardando" :size="17" class="animate-spin" /><Check v-else :size="17" />{{ guardando ? 'Guardando…' : 'Guardar' }}</button></footer>
    </form>
  </dialog>
</template>

<style scoped>
.menu-form-icon{position:relative}.menu-form-icon i{position:absolute;left:.8rem;top:50%;transform:translateY(-50%);color:var(--accent)}.menu-form-icon input{padding-left:2.35rem}.menu-switch{width:2.65rem;height:1.45rem;appearance:none;border-radius:999px;background:var(--muted);transition:.15s}.menu-switch:checked{background:var(--menu-accent)}.menu-switch:before{content:"";display:block;width:1.15rem;height:1.15rem;margin:.15rem;border-radius:50%;background:#fff;box-shadow:0 1px 2px rgb(0 0 0/.2);transition:.15s}.menu-switch:checked:before{transform:translateX(1.2rem)}
</style>
