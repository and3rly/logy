<script setup>
import { Plus, ShieldCheck, UserCheck, Users } from '@lucide/vue'
import BaseCard from '../components/ui/BaseCard.vue'
import Breadcrumb from '../components/ui/Breadcrumb.vue'

const stats = [
  { label: 'Usuarios registrados', value: '248', icon: Users },
  { label: 'Usuarios activos', value: '231', icon: UserCheck },
  { label: 'Administradores', value: '12', icon: ShieldCheck },
]
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between [&_h2]:mt-2 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_p]:mt-2 [&_p]:text-sm [&_p]:text-muted"><div><Breadcrumb :items="[{ label: 'Usuarios' }]" /><h2>Usuarios</h2><p>Gestiona accesos, roles y permisos del sistema.</p></div><button class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold" type="button"><Plus :size="17" /> Nuevo usuario</button></div>
    <div class="mb-5 grid gap-4 sm:grid-cols-3">
      <BaseCard v-for="stat in stats" :key="stat.label"><div class="flex items-center gap-4 [&>div:last-child]:flex [&>div:last-child]:flex-col [&_strong]:text-2xl [&_span]:text-sm [&_span]:text-muted"><div class="grid size-11 place-items-center rounded-xl tone-blue"><component :is="stat.icon" :size="21" /></div><div><span>{{ stat.label }}</span><strong>{{ stat.value }}</strong></div></div></BaseCard>
    </div>
    <BaseCard title="Directorio de usuarios" subtitle="Personal con acceso a la plataforma">
      <div class="flex min-h-72 flex-col items-center justify-center gap-3 px-4 py-8 text-center [&_h3]:font-semibold [&_p]:max-w-md [&_p]:text-sm [&_p]:text-muted"><div class="grid size-12 place-items-center rounded-xl bg-soft text-accent"><Users :size="26" /></div><h3>Directorio preparado</h3><p>Conecta esta vista con el endpoint de usuarios para mostrar los registros.</p></div>
    </BaseCard>
  </div>
</template>
