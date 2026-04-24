import { createApp } from 'vue';
import { createPinia } from 'pinia';
import VueApexCharts from 'vue3-apexcharts';

import App from './App.vue';
import { router } from './router';
import { vuetify } from './plugins/vuetify';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(vuetify);
app.use(VueApexCharts);

app.mount('#app');
