<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { AlertCircle, Eye, EyeOff, LockKeyhole, LogIn, UserRound } from '@lucide/vue'
import AppearanceMenu from '../components/ui/AppearanceMenu.vue'
import { useSessionStore } from '../stores/session'

const sessionStore = useSessionStore()
const router = useRouter()
const route = useRoute()
const form = reactive({ alias: '', clave: '' })
const loading = ref(false)
const message = ref('')
const showPassword = ref(false)
const canSubmit = computed(() => form.alias.trim() && form.clave && !loading.value)

const submitLogin = async () => {
  if (!canSubmit.value) return

  loading.value = true
  message.value = ''
  const result = await sessionStore.login({ alias: form.alias.trim(), clave: form.clave })

  if (sessionStore.isAuthenticated) {
    const destination = typeof route.query.redirect === 'string' && route.query.redirect.startsWith('/')
      ? route.query.redirect
      : '/dashboard'
    await router.replace(destination)
  } else {
    message.value = result.mensaje || 'No fue posible iniciar sesión.'
  }

  loading.value = false
}
</script>

<template>
  <main class="relative grid min-h-dvh bg-surface min-[900px]:grid-cols-2">
    <div class="fixed top-4 right-4 z-20"><AppearanceMenu /></div>

    <section class="relative hidden min-h-dvh items-center justify-center bg-linear-to-br from-slate-950 to-cyan-950 p-12 min-[900px]:flex" aria-label="Logy Sistema de Inventario">
      <div class="w-full max-w-2xl text-center [&_img]:w-full [&_p]:mt-8 [&_p]:text-sm [&_p]:text-slate-400">
        <img src="/assets/img/login/logy.png" alt="Logy Sistema de Inventario" />
        <p>Control claro para una operación eficiente.</p>
      </div>
      <span class="absolute bottom-6 left-8 text-xs text-slate-500">© {{ new Date().getFullYear() }} Logy</span>
    </section>

    <section class="flex items-center justify-center px-6 py-24">
      <div class="w-full max-w-md [&_h1]:text-3xl [&_h1]:font-bold [&_h1]:tracking-tight">
        <div class="mb-8 flex items-center gap-3 min-[900px]:hidden"><span class="grid size-10 shrink-0 place-items-center rounded-xl bg-linear-to-br from-blue-600 to-sky-400 text-xl font-bold text-white shadow-lg shadow-blue-500/15">L</span><strong>Logy</strong></div>
        <span class="mb-3 block text-xs font-semibold tracking-widest text-accent uppercase">Acceso seguro</span>
        <h1>Bienvenido de nuevo</h1>
        <p class="mt-3 mb-8 text-sm leading-relaxed text-muted">Ingresa tus credenciales para acceder al panel administrativo.</p>

        <div v-if="message" class="mb-5 flex items-start gap-3 rounded-xl bg-red-500/10 p-4 text-sm text-red-700 dark:text-red-300" role="alert">
          <AlertCircle :size="19" aria-hidden="true" />
          <span>{{ message }}</span>
        </div>

        <form @submit.prevent="submitLogin">
          <div class="mb-5 [&_label]:text-sm [&_label]:font-medium">
            <label for="login-alias">Usuario</label>
            <div class="mt-2 flex h-12 items-center gap-3 rounded-xl border border-line bg-soft px-3 text-muted focus-within:border-accent focus-within:ring-2 focus-within:ring-accent/15 [&_input]:min-w-0 [&_input]:w-full [&_input]:bg-transparent [&_input]:text-ink [&_input]:outline-none">
              <UserRound :size="19" aria-hidden="true" />
              <input
                id="login-alias"
                v-model="form.alias"
                type="text"
                autocomplete="username"
                placeholder="Ingresa tu usuario"
                required
                autofocus
              />
            </div>
          </div>

          <div class="mb-5 [&_label]:text-sm [&_label]:font-medium">
            <div class="flex items-center justify-between gap-3">
              <label for="login-password">Contraseña</label>
              <button type="button" class="text-xs text-muted hover:text-accent">¿Olvidaste tu contraseña?</button>
            </div>
            <div class="mt-2 flex h-12 items-center gap-3 rounded-xl border border-line bg-soft px-3 text-muted focus-within:border-accent focus-within:ring-2 focus-within:ring-accent/15 [&_input]:min-w-0 [&_input]:w-full [&_input]:bg-transparent [&_input]:text-ink [&_input]:outline-none">
              <LockKeyhole :size="19" aria-hidden="true" />
              <input
                id="login-password"
                v-model="form.clave"
                :type="showPassword ? 'text' : 'password'"
                autocomplete="current-password"
                placeholder="Ingresa tu contraseña"
                required
              />
              <button
                type="button"
                class="grid size-8 shrink-0 place-items-center rounded-lg hover:bg-surface"
                :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                @click="showPassword = !showPassword"
              >
                <EyeOff v-if="showPassword" :size="18" />
                <Eye v-else :size="18" />
              </button>
            </div>
          </div>

          <button type="submit" class="btn-primary mt-7 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl px-4 text-sm font-semibold" :disabled="!canSubmit">
            <span v-if="loading" class="inline-block size-4 animate-spin rounded-full border-2 border-current border-r-transparent" aria-hidden="true"></span>
            <LogIn v-else :size="19" aria-hidden="true" />
            {{ loading ? 'Iniciando sesión…' : 'Iniciar sesión' }}
          </button>
        </form>

        <p class="mx-auto mt-7 max-w-sm text-center text-xs leading-relaxed text-muted">Si tienes problemas para ingresar, comunícate con el administrador del sistema.</p>
      </div>
    </section>
  </main>
</template>
