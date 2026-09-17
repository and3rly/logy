<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { Boxes } from '@lucide/vue'
import BaseCard from '../components/ui/BaseCard.vue'
import Breadcrumb from '../components/ui/Breadcrumb.vue'
import { useMenuStore } from '../stores/menu'

const route = useRoute()
const menuStore = useMenuStore()
const menuItem = computed(() => menuStore.findByPath(route.path))
const title = computed(() => menuItem.value?.name || 'Módulo')
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between [&_h2]:mt-2 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_p]:mt-2 [&_p]:text-sm [&_p]:text-muted">
      <div>
        <Breadcrumb :items="[{ label: title }]" />
        <h2>{{ title }}</h2>
        <p>Sección cargada desde la configuración dinámica del sistema.</p>
      </div>
    </div>

    <BaseCard :title="title">
      <div class="flex min-h-72 flex-col items-center justify-center gap-3 px-4 py-8 text-center [&_h3]:font-semibold [&_p]:max-w-md [&_p]:text-sm [&_p]:text-muted">
        <div class="grid size-12 place-items-center rounded-xl bg-soft text-accent"><Boxes :size="26" /></div>
        <h3>Módulo disponible</h3>
        <p>La opción ya está integrada en la navegación. Aquí puede conectarse la vista funcional correspondiente.</p>
      </div>
    </BaseCard>
  </div>
</template>
