import { createApp } from 'vue';
import App from './App.vue';

const el = document.getElementById('pos-app');
if (el) {
    const bootstrap = JSON.parse(el.dataset.bootstrap || '{}');
    createApp(App, {
        initialBootstrap: bootstrap,
        apiBase: el.dataset.apiBase,
        dashboardUrl: el.dataset.dashboardUrl,
        locale: el.dataset.locale || 'en',
    }).mount(el);
}
