<script setup>
import { storeToRefs } from 'pinia'
import { onBeforeUnmount, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import Sidebar from '../components/layout/Sidebar.vue'
import AppHeader from '../components/layout/Header.vue'
import AppFooter from '../components/layout/Footer.vue'
import { useAppStore } from '../stores/app'

const appStore = useAppStore()
const router = useRouter()
const { sidebarCollapsed, mobileSidebarOpen } = storeToRefs(appStore)

const handleResize = () => {
  if (window.innerWidth >= 992) appStore.closeMobileSidebar()
}

const removeAfterEach = router.afterEach(() => {
  if (window.innerWidth < 992) appStore.closeMobileSidebar()
})

onMounted(() => window.addEventListener('resize', handleResize))
onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
  removeAfterEach()
})
</script>

<template>
  <div
    class="app-shell"
    :class="{
      'sidebar-collapsed': sidebarCollapsed,
      'sidebar-mobile-open': mobileSidebarOpen,
    }"
  >
    <Sidebar />
    <button
      v-if="mobileSidebarOpen"
      class="sidebar-overlay"
      type="button"
      aria-label="Cerrar menú lateral"
      @click="appStore.closeMobileSidebar"
    />
    <AppHeader />
    <main id="main-content" class="main-content" tabindex="-1">
      <RouterView />
    </main>
    <AppFooter />
  </div>
</template>
