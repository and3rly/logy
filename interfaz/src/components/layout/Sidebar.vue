<script setup>
import { storeToRefs } from 'pinia'
import { computed, onMounted, ref } from 'vue'
import { AlertCircle, RefreshCw, X } from '@lucide/vue'
import { useAppStore } from '../../stores/app'
import { useMenuStore } from '../../stores/menu'
import api from '../../services/api'
import SidebarMenuItem from './SidebarMenuItem.vue'

const appStore = useAppStore()
const menuStore = useMenuStore()
const { sidebarCollapsed } = storeToRefs(appStore)
const { items, loading, error } = storeToRefs(menuStore)
const empresa = ref({ nombre: 'FerroAgro San Bernardino', logo: '' })
const logoConError = ref(false)
const logoUrl = computed(() => empresa.value.logo
  ? `https://lh3.googleusercontent.com/d/${encodeURIComponent(empresa.value.logo)}`
  : '')

async function cargarEmpresa() {
  try {
    const { data } = await api.get('index.php/mnt/empresa/actual')
    if (data.exito === 1 && data.empresa) empresa.value = data.empresa
  } catch {
    // La navegación puede continuar usando la identidad de respaldo.
  }
}

onMounted(() => {
  menuStore.loadMenu()
  cargarEmpresa()
})
</script>

<template>
  <aside class="sidebar" aria-label="Navegación principal">
    <div class="flex h-[76px] shrink-0 items-center gap-3 border-b border-line px-5">
      <div class="brand-mark hidden size-10 shrink-0 place-items-center rounded-xl bg-emerald-700 text-sm font-bold text-white" aria-hidden="true">FA</div>
      <div class="brand-copy flex h-14 min-w-0 flex-1 items-center">
        <img
          v-if="logoUrl && !logoConError"
          :src="logoUrl"
          :alt="`Logo de ${empresa.nombre}`"
          class="max-h-12 max-w-full object-contain object-left"
          @error="logoConError = true"
        />
        <div v-else class="flex min-w-0 flex-col whitespace-nowrap [&_strong]:truncate [&_strong]:text-base [&_strong]:font-bold [&_span]:text-xs [&_span]:text-muted">
          <strong>{{ empresa.nombre }}</strong>
          <span>Panel administrativo</span>
        </div>
      </div>
      <button
        class="ml-auto p-2 min-[992px]:hidden"
        type="button"
        aria-label="Cerrar menú"
        @click="appStore.closeMobileSidebar"
      >
        <X :size="20" />
      </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-5">
      <p class="nav-section-label mb-2 px-3 text-xs font-medium tracking-widest text-muted uppercase">Navegación</p>

      <div v-if="loading" class="flex flex-col gap-3" aria-label="Cargando menú">
        <span v-for="index in 5" :key="index" class="block h-9 animate-pulse rounded-lg bg-soft"></span>
      </div>

      <div v-else-if="error" class="my-3 flex flex-col items-center gap-3 rounded-xl border border-line p-3 text-center text-sm text-muted [&_button]:flex [&_button]:items-center [&_button]:gap-2 [&_button]:text-accent" role="alert">
        <AlertCircle :size="20" />
        <span v-if="!sidebarCollapsed">{{ error }}</span>
        <button v-if="!sidebarCollapsed" type="button" @click="menuStore.loadMenu(true)">
          <RefreshCw :size="14" /> Reintentar
        </button>
      </div>

      <template v-else-if="items.length">
        <SidebarMenuItem
          v-for="item in items"
          :key="item.id || `${item.name}-${item.url}`"
          :item="item"
        />
      </template>

      <div v-else class="my-3 flex flex-col items-center gap-3 rounded-xl border border-line p-3 text-center text-sm text-muted [&_button]:flex [&_button]:items-center [&_button]:gap-2 [&_button]:text-accent">
        <span v-if="!sidebarCollapsed">No hay opciones de menú disponibles.</span>
      </div>
    </nav>

    <div class="sidebar-status mx-4 mb-4 flex shrink-0 items-start gap-3 overflow-hidden rounded-xl border border-line bg-soft p-3 [&>div]:flex [&>div]:flex-col [&_strong]:text-xs [&_small]:mt-1 [&_small]:text-xs [&_small]:text-muted">
      <span class="mt-1 inline-block size-2 shrink-0 rounded-full bg-emerald-500" aria-hidden="true"></span>
      <div>
        <strong>Sistema operativo</strong>
        <small>Todos los servicios activos</small>
      </div>
    </div>
  </aside>
</template>
