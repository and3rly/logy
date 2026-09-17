<script setup>
defineProps({
  title: { type: String, default: '' },
  subtitle: { type: String, default: '' },
  noPadding: { type: Boolean, default: false },
})
</script>

<template>
  <section class="min-w-0 overflow-hidden rounded-2xl border border-line bg-surface shadow-sm">
    <header v-if="title || subtitle || $slots.actions" class="flex flex-wrap items-center justify-between gap-3 px-6 pt-6 [&_h2]:text-base [&_h2]:font-semibold [&_p]:mt-1 [&_p]:text-sm [&_p]:text-muted">
      <div>
        <h2 v-if="title">{{ title }}</h2>
        <p v-if="subtitle">{{ subtitle }}</p>
      </div>
      <div v-if="$slots.actions" class="shrink-0"><slot name="actions" /></div>
    </header>
    <div :class="{ 'base-card-body': !noPadding }"><slot /></div>
  </section>
</template>
