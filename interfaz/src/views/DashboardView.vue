<script setup>
import { ArrowDownRight, ArrowUpRight, Download, Eye, PackageCheck, Plus, ShoppingCart, Users, WalletCards } from '@lucide/vue'
import BaseCard from '../components/ui/BaseCard.vue'
import BaseTable from '../components/ui/BaseTable.vue'
import Breadcrumb from '../components/ui/Breadcrumb.vue'

const indicators = [
  { label: 'Ventas del mes', value: 'Q 248,590', change: '+12.4%', trend: 'up', icon: WalletCards, tone: 'blue' },
  { label: 'Operaciones', value: '1,284', change: '+8.2%', trend: 'up', icon: ShoppingCart, tone: 'violet' },
  { label: 'Clientes activos', value: '842', change: '+5.7%', trend: 'up', icon: Users, tone: 'teal' },
  { label: 'Entregas pendientes', value: '37', change: '-3.1%', trend: 'down', icon: PackageCheck, tone: 'amber' },
]

const columns = [
  { key: 'reference', label: 'Referencia' },
  { key: 'client', label: 'Cliente' },
  { key: 'date', label: 'Fecha' },
  { key: 'amount', label: 'Monto' },
  { key: 'status', label: 'Estado' },
  { key: 'actions', label: '', class: 'text-end' },
]

const operations = [
  { id: 1, reference: 'OP-10482', client: 'Distribuidora Central', initials: 'DC', date: '08 sep, 2026', amount: 'Q 18,450.00', status: 'Completada' },
  { id: 2, reference: 'OP-10481', client: 'Grupo Horizonte', initials: 'GH', date: '08 sep, 2026', amount: 'Q 9,840.00', status: 'En proceso' },
  { id: 3, reference: 'OP-10480', client: 'Comercial del Norte', initials: 'CN', date: '07 sep, 2026', amount: 'Q 24,120.00', status: 'Pendiente' },
  { id: 4, reference: 'OP-10479', client: 'Soluciones Maya', initials: 'SM', date: '07 sep, 2026', amount: 'Q 6,720.00', status: 'Completada' },
  { id: 5, reference: 'OP-10478', client: 'Inversiones Altura', initials: 'IA', date: '06 sep, 2026', amount: 'Q 13,590.00', status: 'Cancelada' },
]

const statusClass = (status) => ({
  Completada: 'status-success',
  'En proceso': 'status-info',
  Pendiente: 'status-warning',
  Cancelada: 'status-danger',
}[status])
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between [&_h2]:mt-2 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_p]:mt-2 [&_p]:text-sm [&_p]:text-muted">
      <div>
        <Breadcrumb :items="[{ label: 'Dashboard' }]" />
        <h2>Resumen general</h2>
        <p>Actividad y rendimiento de la operación al día de hoy.</p>
      </div>
      <div class="flex flex-wrap gap-3">
        <button class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors border-line bg-surface text-ink hover:bg-soft border" type="button"><Download :size="17" /> Exportar</button>
        <button class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold" type="button"><Plus :size="17" /> Nueva operación</button>
      </div>
    </div>

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      <BaseCard v-for="indicator in indicators" :key="indicator.label">
        <div class="flex flex-col">
          <div class="mb-5 flex items-center justify-between">
            <div class="grid size-11 place-items-center rounded-xl" :class="`tone-${indicator.tone}`">
              <component :is="indicator.icon" :size="22" />
            </div>
            <span class="trend" :class="indicator.trend">
              <component :is="indicator.trend === 'up' ? ArrowUpRight : ArrowDownRight" :size="15" />
              {{ indicator.change }}
            </span>
          </div>
          <span class="text-sm text-muted">{{ indicator.label }}</span>
          <strong class="mt-1 text-3xl font-bold tracking-tight tabular-nums">{{ indicator.value }}</strong>
          <small>comparado con el mes anterior</small>
        </div>
      </BaseCard>
    </div>

    <div class="mb-5 grid gap-5 xl:grid-cols-[1.5fr_1fr]">
      <BaseCard title="Rendimiento mensual" subtitle="Ingresos registrados durante los últimos seis meses">
        <div class="mb-5 flex items-end justify-between [&>div]:flex [&>div]:flex-col [&_strong]:mt-1 [&_strong]:text-2xl [&_span]:text-sm">
          <div><span>Total acumulado</span><strong>Q 1.28M</strong></div>
          <span class="trend up"><ArrowUpRight :size="15" /> 14.8%</span>
        </div>
        <div class="flex h-52 items-end gap-4 border-b border-line pt-3" aria-label="Gráfica de ingresos mensuales">
          <div v-for="bar in [{m:'Abr',v:55},{m:'May',v:72},{m:'Jun',v:62},{m:'Jul',v:83},{m:'Ago',v:75},{m:'Sep',v:92}]" :key="bar.m" class="group flex h-full flex-1 flex-col items-center gap-2 text-xs text-muted">
            <span class="opacity-0 group-hover:opacity-100">{{ bar.v }}%</span>
            <div class="flex w-8 max-w-full flex-1 items-end overflow-hidden rounded-t-md bg-soft"><div class="w-full rounded-t-md bg-linear-to-t from-blue-600 to-sky-400" :style="{ height: `${bar.v}%` }"></div></div>
            <span>{{ bar.m }}</span>
          </div>
        </div>
      </BaseCard>

      <BaseCard title="Distribución por estado" subtitle="Operaciones del mes actual">
        <div class="flex flex-col items-center gap-6">
          <div class="donut-chart"><span><strong>1,284</strong>Total</span></div>
          <div class="grid w-full gap-3 sm:grid-cols-2 [&>div]:flex [&>div]:items-center [&>div]:gap-2 [&>div]:text-sm [&_strong]:ml-auto">
            <div><span class="inline-block size-2 shrink-0 rounded-full bg-blue-600"></span><span>Completadas</span><strong>68%</strong></div>
            <div><span class="inline-block size-2 shrink-0 rounded-full bg-sky-500"></span><span>En proceso</span><strong>19%</strong></div>
            <div><span class="inline-block size-2 shrink-0 rounded-full bg-amber-500"></span><span>Pendientes</span><strong>9%</strong></div>
            <div><span class="inline-block size-2 shrink-0 rounded-full bg-red-500"></span><span>Canceladas</span><strong>4%</strong></div>
          </div>
        </div>
      </BaseCard>
    </div>

    <BaseCard title="Operaciones recientes" subtitle="Últimos movimientos registrados" no-padding>
      <template #actions><RouterLink class="text-sm font-semibold text-accent hover:underline" to="/operations">Ver todas</RouterLink></template>
      <BaseTable :columns="columns" :rows="operations">
        <template #cell-reference="{ value }"><strong class="font-semibold">{{ value }}</strong></template>
        <template #cell-client="{ row }">
          <div class="flex items-center gap-3"><span class="grid size-9 place-items-center rounded-lg bg-soft text-xs font-semibold">{{ row.initials }}</span><span>{{ row.client }}</span></div>
        </template>
        <template #cell-status="{ value }"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(value)">{{ value }}</span></template>
        <template #cell-actions><button class="grid size-9 place-items-center rounded-lg text-muted hover:bg-soft hover:text-accent" type="button" aria-label="Ver operación"><Eye :size="18" /></button></template>
      </BaseTable>
    </BaseCard>
  </div>
</template>
