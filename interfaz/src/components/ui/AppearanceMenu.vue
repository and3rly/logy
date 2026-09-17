<script setup>
import { storeToRefs } from 'pinia'
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Check, Moon, Palette, Sun } from '@lucide/vue'
import { useAppStore } from '../../stores/app'

const appStore = useAppStore()
const { theme, menuAccent } = storeToRefs(appStore)
const desplegable = ref(null)
function cerrarMenu(evento) {
  if (!desplegable.value?.contains(evento.target)) desplegable.value?.removeAttribute('open')
}
onMounted(() => document.addEventListener('click', cerrarMenu))
onBeforeUnmount(() => document.removeEventListener('click', cerrarMenu))

const accentOptions = [
  { name: 'Azul', value: '#2563eb' },
  { name: 'Celeste', value: '#0284c7' },
  { name: 'Cian', value: '#0891b2' },
  { name: 'Índigo', value: '#4f46e5' },
  { name: 'Violeta', value: '#7c3aed' },
  { name: 'Fucsia', value: '#c026d3' },
  { name: 'Rosa', value: '#db2777' },
  { name: 'Rojo', value: '#dc2626' },
  { name: 'Naranja', value: '#ea580c' },
  { name: 'Ámbar', value: '#d97706' },
  { name: 'Verde', value: '#059669' },
  { name: 'Verde bosque', value: '#15803d' },
  { name: 'Turquesa', value: '#0f766e' },
  { name: 'Grafito', value: '#475569' },
]
</script>

<template>
  <details ref="desplegable" class="relative" @keydown.esc.prevent="desplegable.removeAttribute('open'); desplegable.querySelector('summary').focus()">
    <summary
      class="relative grid size-10 shrink-0 list-none place-items-center rounded-xl border border-line bg-surface text-muted transition hover:bg-soft hover:text-ink [&::-webkit-details-marker]:hidden"
      aria-label="Personalizar apariencia"
      title="Personalizar apariencia"
    >
      <Moon v-if="theme === 'dark'" :size="20" />
      <Sun v-else :size="20" />
    </summary>

    <div class="fixed right-4 top-20 z-50 w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-line bg-surface p-4 shadow-xl sm:absolute sm:right-0 sm:top-full sm:mt-3">
      <div class="mb-4 flex items-center gap-2 text-sm"><Palette :size="17" /><strong>Apariencia</strong></div>
      <span class="mb-2 mt-4 block text-xs font-medium text-muted">Tema de la plantilla</span>
      <div class="theme-options grid grid-cols-2 gap-2" role="group" aria-label="Tema de color">
        <button type="button" :aria-pressed="theme === 'light'" :class="{ active: theme === 'light' }" @click="appStore.setTheme('light')">
          <Sun :size="17" /> Claro
        </button>
        <button type="button" :aria-pressed="theme === 'dark'" :class="{ active: theme === 'dark' }" @click="appStore.setTheme('dark')">
          <Moon :size="17" /> Oscuro
        </button>
      </div>

      <span class="mb-2 mt-4 block text-xs font-medium text-muted">Color principal</span>
      <div class="grid grid-cols-7 gap-2" role="group" aria-label="Color principal de la interfaz">
        <button
          v-for="option in accentOptions"
          :key="option.value"
          type="button"
          class="grid size-8 place-items-center rounded-lg border-2 border-surface bg-(--swatch) text-white outline-offset-2 hover:outline [&.active]:outline [&.active]:outline-(--swatch)"
          :class="{ active: menuAccent === option.value }"
          :aria-pressed="menuAccent === option.value"
          :style="{ '--swatch': option.value }"
          :aria-label="`Usar color ${option.name}`"
          :title="option.name"
          @click="appStore.setMenuAccent(option.value)"
        >
          <Check v-if="menuAccent === option.value" :size="14" />
        </button>
      </div>
    </div>
  </details>
</template>
