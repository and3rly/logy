<script setup>
import { Bell, Building2, Save, Shield } from '@lucide/vue'
import BaseCard from '../components/ui/BaseCard.vue'
import Breadcrumb from '../components/ui/Breadcrumb.vue'

const sections = [
  { title: 'Datos de la organización', description: 'Nombre comercial, identificación y datos generales.', icon: Building2 },
  { title: 'Seguridad', description: 'Políticas de acceso y duración de las sesiones.', icon: Shield },
  { title: 'Notificaciones', description: 'Preferencias para alertas operativas y reportes.', icon: Bell },
]
</script>

<template>
  <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6 lg:p-8">
    <div class="mb-7 flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between [&_h2]:mt-2 [&_h2]:text-2xl [&_h2]:font-bold [&_h2]:tracking-tight [&_p]:mt-2 [&_p]:text-sm [&_p]:text-muted"><div><Breadcrumb :items="[{ label: 'Configuración' }]" /><h2>Configuración</h2><p>Personaliza el comportamiento general de la plataforma.</p></div><button class="btn-primary inline-flex min-h-10 items-center justify-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold" type="button"><Save :size="17" /> Guardar</button></div>
    <div class="grid items-start gap-5 lg:grid-cols-[250px_1fr]">
      <nav class="flex flex-wrap gap-1 rounded-2xl border border-line bg-surface p-2 lg:flex-col [&_button]:flex [&_button]:items-center [&_button]:gap-3 [&_button]:rounded-lg [&_button]:p-3 [&_button]:text-left [&_button]:text-sm [&_button]:hover:bg-soft [&_.active]:bg-soft [&_.active]:text-accent" aria-label="Secciones de configuración">
        <button v-for="(section, index) in sections" :key="section.title" type="button" :class="{ active: index === 0 }"><component :is="section.icon" :size="18" />{{ section.title }}</button>
      </nav>
      <BaseCard title="Datos de la organización" subtitle="Información principal visible en el sistema">
        <form class="grid gap-5 sm:grid-cols-2 [&_label]:mb-2 [&_label]:block [&_label]:text-sm [&_label]:font-medium" @submit.prevent>
          <div><label for="business-name">Nombre de la organización</label><input id="business-name" class="w-full min-h-11 rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink outline-none placeholder:text-muted focus:border-accent focus:ring-2 focus:ring-accent/15" value="Logy Solutions" /></div>
          <div><label for="tax-id">Identificación fiscal</label><input id="tax-id" class="w-full min-h-11 rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink outline-none placeholder:text-muted focus:border-accent focus:ring-2 focus:ring-accent/15" value="1234567-8" /></div>
          <div class="sm:col-span-2"><label for="email">Correo administrativo</label><input id="email" type="email" class="w-full min-h-11 rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink outline-none placeholder:text-muted focus:border-accent focus:ring-2 focus:ring-accent/15" value="administracion@logy.test" /></div>
          <div><label for="timezone">Zona horaria</label><select id="timezone" class="w-full min-h-11 rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink outline-none focus:border-accent focus:ring-2 focus:ring-accent/15"><option>América/Guatemala</option></select></div>
          <div><label for="currency">Moneda principal</label><select id="currency" class="w-full min-h-11 rounded-lg border border-line bg-surface px-3 py-2 text-sm text-ink outline-none focus:border-accent focus:ring-2 focus:ring-accent/15"><option>GTQ — Quetzal</option></select></div>
        </form>
      </BaseCard>
    </div>
  </div>
</template>
