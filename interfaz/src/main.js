import { createApp } from 'vue'
import { createPinia } from 'pinia'
import VueSelect from 'vue-select'
import Vue3Toastify, { toast } from 'vue3-toastify'
import 'vue3-toastify/dist/index.css'
import 'vue-select/dist/vue-select.css'
import '@fortawesome/fontawesome-free/css/all.min.css'
import './assets/css/main.css'
import App from './App.vue'
import router from './router'

const app = createApp(App)
app.component('VSelect', VueSelect)
app.use(createPinia()).use(router).use(Vue3Toastify, {
  autoClose: 3000,
  position: 'top-right',
  theme: 'colored',
  dangerouslyHTMLString: false,
  onOpen() {
    // La capa superior permite mostrar avisos sobre los diálogos nativos.
    document.querySelectorAll('.Toastify__toast-container').forEach(contenedor => {
      if (typeof contenedor.showPopover !== 'function') return
      contenedor.setAttribute('popover', 'manual')
      if (contenedor.matches(':popover-open')) contenedor.hidePopover()
      contenedor.showPopover()
    })
  },
})
app.config.globalProperties.$toast = toast
app.mount('#app')
