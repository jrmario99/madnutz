import { createApp } from 'vue';
import PrimeVue from 'primevue/config';
import Aura from '@primevue/themes/aura';
import router from './router/index.js';
import App from './App.vue';
import '../css/app.css';
import 'primeicons/primeicons.css';
import { setupHttpInterceptors } from './plugins/http.js';

setupHttpInterceptors(router);

const app = createApp(App);
app.use(router);
app.use(PrimeVue, {
    theme: {
        preset: Aura,
        options: {
            darkModeSelector: false,
        },
    },
});
app.mount('#app');
