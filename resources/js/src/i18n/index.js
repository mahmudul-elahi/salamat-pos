import {createI18n} from 'vue-i18n';

import en from '@/i18n/locales/en.json';

const i18n = createI18n({
    legacy: false,
    locale: localStorage.getItem('lang') || 'en',
    messages: {en},
    fallbackLocale: 'en',
});


export default i18n;
