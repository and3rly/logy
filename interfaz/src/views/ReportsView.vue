<script setup>
import { BarChart3, CalendarDays, Download, FileSpreadsheet } from '@lucide/vue'
import BaseCard from '../components/ui/BaseCard.vue'
import Breadcrumb from '../components/ui/Breadcrumb.vue'

const reports = [
  { title: 'Resumen de ventas', description: 'Totales, impuestos y comparativa por período.', icon: BarChart3 },
  { title: 'Detalle operativo', description: 'Movimientos, responsables y tiempos de atención.', icon: FileSpreadsheet },
  { title: 'Cierre mensual', description: 'Consolidado financiero del período seleccionado.', icon: CalendarDays },
]
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between [&_h2]:mt-2 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_p]:mt-2 [&_p]:text-sm [&_p]:text-muted"><div><Breadcrumb :items="[{ label: 'Reportes' }]" /><h2>Reportes</h2><p>Información consolidada para análisis y toma de decisiones.</p></div></div>
    <div class="grid gap-5 lg:grid-cols-3">
      <BaseCard v-for="report in reports" :key="report.title">
        <div class="flex flex-col items-start [&_h3]:font-semibold [&_p]:my-3 [&_p]:text-sm [&_p]:text-muted"><div class="mb-5 grid size-12 place-items-center rounded-xl bg-soft text-accent"><component :is="report.icon" :size="23" /></div><h3>{{ report.title }}</h3><p>{{ report.description }}</p><button type="button" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors border border-blue-500/40 text-blue-600 hover:bg-blue-500/10 dark:text-blue-300 min-h-9 px-3 py-1.5"><Download :size="16" /> Generar reporte</button></div>
      </BaseCard>
    </div>
  </div>
</template>
