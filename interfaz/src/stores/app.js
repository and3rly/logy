import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useAppStore = defineStore('app', () => {
  const sidebarCollapsed = ref(false)
  const mobileSidebarOpen = ref(false)
  const preferredTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
  const theme = ref(localStorage.getItem('logy-theme') || preferredTheme)
  const menuAccent = ref(localStorage.getItem('logy-menu-accent') || '#2563eb')

  const applyAppearance = () => {
    document.documentElement.dataset.theme = theme.value
    document.documentElement.style.setProperty('--menu-accent', menuAccent.value)
  }

  const setTheme = (value) => {
    theme.value = value
    localStorage.setItem('logy-theme', value)
    applyAppearance()
  }

  const toggleTheme = () => setTheme(theme.value === 'dark' ? 'light' : 'dark')

  const setMenuAccent = (value) => {
    menuAccent.value = value
    localStorage.setItem('logy-menu-accent', value)
    applyAppearance()
  }

  const toggleSidebar = () => {
    if (window.innerWidth < 992) {
      mobileSidebarOpen.value = !mobileSidebarOpen.value
      return
    }
    sidebarCollapsed.value = !sidebarCollapsed.value
  }

  const closeMobileSidebar = () => {
    mobileSidebarOpen.value = false
  }

  applyAppearance()

  return {
    sidebarCollapsed,
    mobileSidebarOpen,
    theme,
    menuAccent,
    toggleSidebar,
    closeMobileSidebar,
    setTheme,
    toggleTheme,
    setMenuAccent,
  }
})
