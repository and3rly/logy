import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { Vue3ProgressPlugin } from '@marcoschulte/vue3-progress';
import { PerfectScrollbarPlugin } from 'vue3-perfect-scrollbar';
import { toast } from 'vue3-toastify';
import mitt from 'mitt';
import * as bootstrap from 'bootstrap';
import 'vue3-perfect-scrollbar/style.css';
import '@marcoschulte/vue3-progress/dist/index.css';
import '@fortawesome/fontawesome-free/scss/fontawesome.scss';
import '@fortawesome/fontawesome-free/scss/regular.scss';
import '@fortawesome/fontawesome-free/scss/solid.scss';
import '@fortawesome/fontawesome-free/scss/brands.scss';
import '@fortawesome/fontawesome-free/scss/v4-shims.scss';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'vue3-toastify/dist/index.css';
import './scss/styles.scss';
import App from './App.vue';
import router from './router';
import Card from '@/components/bootstrap/Card.vue';
import CardBody from '@/components/bootstrap/CardBody.vue';
import CardHeader from '@/components/bootstrap/CardHeader.vue';
import CardFooter from '@/components/bootstrap/CardFooter.vue';
import CardGroup from '@/components/bootstrap/CardGroup.vue';
import CardImgOverlay from '@/components/bootstrap/CardImgOverlay.vue';
import CardExpandToggler from '@/components/bootstrap/CardExpandToggler.vue';
import api from "@/plugins/axios.ts";
import '@/assets/css/app.css';

const emitter = mitt();
const app = createApp(App);

app.component('Card', Card);
app.component('CardBody', CardBody);
app.component('CardHeader', CardHeader);
app.component('CardFooter', CardFooter);
app.component('CardGroup', CardGroup);
app.component('CardImgOverlay', CardImgOverlay);
app.component('CardExpandToggler', CardExpandToggler);

app.use(createPinia());
app.use(router);
app.use(Vue3ProgressPlugin);
app.use(PerfectScrollbarPlugin);

app.use(Vue3Toastify, {
	autoClose: 3000,
	multiLine: true,
	dangerouslyHTMLString: true,
	"theme": "colored",
} as ToastContainerOptions);

app.config.globalProperties.$abrirModal = (id: string) => {
	const elemento = document.getElementById(id);
	if (!elemento) throw new Error(`Modal con id "${id}" no encontrado`);

	// Guardamos el elemento que tiene el foco antes de abrir
	const trigger = document.activeElement as HTMLElement;
	const modal = bootstrap.Modal.getOrCreateInstance(elemento);
	modal.show();

	// Al cerrarse, devolvemos el foco al elemento original
	elemento.addEventListener('hidden.bs.modal', () => {
		trigger?.focus();
	}, { once: true }); // "once" para que no se acumule el listener
};

app.config.globalProperties.$cerrarModal = (id: string) => {
	const elemento = document.getElementById(id);
	if (!elemento) throw new Error(`Modal con id "${id}" no encontrado`);

	const trigger = document.activeElement as HTMLElement;
	trigger?.blur();

	bootstrap.Modal.getOrCreateInstance(elemento).hide();
};

app.config.globalProperties.$baseUrl = "index.php";
app.config.globalProperties.emitter = emitter;
app.config.globalProperties.$toast = toast;
app.config.globalProperties.$http = api;

app.mount('#app');