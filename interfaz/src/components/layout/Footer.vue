<script setup>
const currentYear = new Date().getFullYear()
</script>

<template>
  <footer class="flex flex-wrap items-center justify-between gap-2 border-t border-line bg-surface px-4 py-5 text-xs text-muted sm:px-8">
    <span>© {{ currentYear }} <strong>Logy Admin</strong>. Todos los derechos reservados.</span>
    <span class="hidden sm:block">Versión 1.0.0</span>
  </footer>
</template>
