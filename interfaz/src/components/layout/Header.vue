<script setup>
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { Bell, ChevronDown, LogOut, Menu, Settings, UserRound } from '@lucide/vue'
import { useAppStore } from '../../stores/app'
import { useMenuStore } from '../../stores/menu'
import { useSessionStore } from '../../stores/session'
import AppearanceMenu from '../ui/AppearanceMenu.vue'

const appStore = useAppStore()
const menuStore = useMenuStore()
const sessionStore = useSessionStore()
const router = useRouter()
const signingOut = ref(false)
const desplegable = ref(null)
function cerrarMenu(evento) {
  if (!desplegable.value?.contains(evento.target)) desplegable.value?.removeAttribute('open')
}
onMounted(() => document.addEventListener('click', cerrarMenu))
onBeforeUnmount(() => document.removeEventListener('click', cerrarMenu))
const userName = computed(() => sessionStore.user?.nombre || sessionStore.user?.alias || 'Usuario')
const userAlias = computed(() => sessionStore.user?.alias || 'Administrador')
const userInitials = computed(() => userName.value
  .split(' ')
  .filter(Boolean)
  .slice(0, 2)
  .map((part) => part[0])
  .join('')
  .toUpperCase())

const handleLogout = async () => {
  if (signingOut.value) return
  signingOut.value = true
  await sessionStore.logout()
  menuStore.resetMenu()
  await router.replace({ name: 'login' })
}
</script>

<template>
  <header class="app-header">
    <div class="flex min-w-0 items-center gap-3 sm:gap-5">
      <button
        type="button"
        class="relative grid size-10 shrink-0 place-items-center rounded-xl border border-line bg-surface text-muted transition hover:bg-soft hover:text-ink"
        aria-label="Abrir o contraer menú lateral"
        @click="appStore.toggleSidebar"
      >
        <Menu :size="21" />
      </button>
    </div>

    <div class="flex shrink-0 items-center gap-2 sm:gap-3">
      <AppearanceMenu />
      <button type="button" class="relative grid size-10 shrink-0 place-items-center rounded-xl border border-line bg-surface text-muted transition hover:bg-soft hover:text-ink" aria-label="3 notificaciones pendientes">
        <Bell :size="20" />
        <span class="absolute -top-1 -right-1 grid size-4 place-items-center rounded-full bg-red-500 text-[10px] font-bold text-white">3</span>
      </button>
      <details ref="desplegable" class="relative" @keydown.esc.prevent="desplegable.removeAttribute('open'); desplegable.querySelector('summary').focus()">
        <summary
          class="flex cursor-pointer list-none items-center gap-3 rounded-xl p-1 hover:bg-soft [&::-webkit-details-marker]:hidden"
          aria-label="Menú de usuario"
        >
          <span class="user-avatar grid size-10 shrink-0 place-items-center rounded-xl text-xs font-bold" aria-hidden="true">{{ userInitials }}</span>
          <span class="hidden max-w-44 flex-col text-sm sm:flex [&_strong]:truncate [&_small]:text-xs [&_small]:text-muted">
            <strong>{{ userName }}</strong>
            <small>{{ userAlias }}</small>
          </span>
          <ChevronDown :size="16" aria-hidden="true" />
        </summary>
        <ul class="absolute right-0 z-50 mt-3 min-w-56 rounded-2xl border border-line bg-surface p-2 shadow-xl" @click="desplegable.removeAttribute('open')">
          <li><h6 class="px-3 py-2 text-xs text-muted">Mi cuenta</h6></li>
          <li>
            <a class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm hover:bg-soft" href="#"><UserRound :size="17" /> Perfil</a>
          </li>
          <li>
            <RouterLink class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm hover:bg-soft" to="/settings"><Settings :size="17" /> Configuración</RouterLink>
          </li>
          <li><hr class="my-2 border-line" /></li>
          <li>
            <button class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left text-sm hover:bg-soft text-red-600 dark:text-red-400" type="button" :disabled="signingOut" @click="handleLogout">
              <LogOut :size="17" /> {{ signingOut ? 'Cerrando…' : 'Cerrar sesión' }}
            </button>
          </li>
        </ul>
      </details>
    </div>
  </header>
</template>
