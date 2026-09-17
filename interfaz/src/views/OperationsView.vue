<script setup>
import { Filter, Plus, Search } from '@lucide/vue'
import BaseCard from '../components/ui/BaseCard.vue'
import Breadcrumb from '../components/ui/Breadcrumb.vue'

const stages = [
  { label: 'Pendientes', value: 37, tone: 'bg-amber-500' },
  { label: 'En proceso', value: 64, tone: 'bg-sky-500' },
  { label: 'Completadas hoy', value: 128, tone: 'bg-emerald-500' },
]
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between [&_h2]:mt-2 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_p]:mt-2 [&_p]:text-sm [&_p]:text-muted">
      <div><Breadcrumb :items="[{ label: 'Operaciones' }]" /><h2>Operaciones</h2><p>Administra y da seguimiento al flujo operativo.</p></div>
      <button class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold" type="button"><Plus :size="17" /> Nueva operación</button>
    </div>
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
      <BaseCard v-for="stage in stages" :key="stage.label"><div class="flex items-center gap-3 [&>div]:flex [&>div]:flex-col [&_strong]:text-2xl [&_strong]:font-bold"><span class="inline-block size-2 shrink-0 rounded-full" :class="stage.tone"></span><div><span>{{ stage.label }}</span><strong>{{ stage.value }}</strong></div></div></BaseCard>
    </div>
    <BaseCard title="Listado de operaciones" subtitle="Consulta y filtra los registros disponibles">
      <template #actions><button class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors border-line bg-surface text-ink hover:bg-soft border min-h-9 px-3 py-1.5" type="button"><Filter :size="16" /> Filtros</button></template>
      <div class="flex min-h-72 flex-col items-center justify-center gap-3 px-4 py-8 text-center [&_h3]:font-semibold [&_p]:max-w-md [&_p]:text-sm [&_p]:text-muted">
        <div class="mb-6 flex w-full max-w-xl items-center gap-3 rounded-xl border border-line px-3 text-muted [&_input]:h-11 [&_input]:w-full [&_input]:outline-none"><Search :size="19" /><input type="search" placeholder="Buscar por referencia o cliente" aria-label="Buscar operaciones" /></div>
        <div class="grid size-12 place-items-center rounded-xl bg-soft text-accent"><Search :size="26" /></div><h3>Busca una operación</h3><p>Utiliza el campo de búsqueda o los filtros para consultar los registros.</p>
      </div>
    </BaseCard>
  </div>
</template>
