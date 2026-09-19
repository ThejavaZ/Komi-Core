import { createApp } from 'vue';
import { createI18n } from 'vue-i18n';
import App from './App.vue';
import router from './router';
import es from './locales/es.json';
import en from './locales/en.json';

const savedLang = localStorage.getItem('admin_lang') || 'es';

const i18n = createI18n({
    legacy: false,
    locale: savedLang,
    fallbackLocale: 'es',
    messages: { es, en },
});

const app = createApp(App);

app.use(router);
app.use(i18n);
app.mount('#admin-app');
