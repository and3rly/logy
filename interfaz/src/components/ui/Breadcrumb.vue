<script setup>
import { ChevronRight, Home } from '@lucide/vue'

defineProps({
  items: { type: Array, default: () => [] },
})
</script>

<template>
  <nav aria-label="Ruta de navegación">
    <ol class="flex items-center gap-2 text-xs text-muted [&_a]:flex [&_a]:hover:text-accent">
      <li><RouterLink to="/dashboard" aria-label="Inicio"><Home :size="15" /></RouterLink></li>
      <template v-for="(item, index) in items" :key="item.label">
        <li aria-hidden="true"><ChevronRight :size="14" /></li>
        <li :aria-current="index === items.length - 1 ? 'page' : undefined">
          <RouterLink v-if="item.to && index !== items.length - 1" :to="item.to">{{ item.label }}</RouterLink>
          <span v-else>{{ item.label }}</span>
        </li>
      </template>
    </ol>
  </nav>
</template>
