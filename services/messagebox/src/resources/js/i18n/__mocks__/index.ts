import Vue from 'vue';
import VueI18n from 'vue-i18n';
import { Locales } from '../locales';
import nl from '../languages/nl.json';

Vue.use(VueI18n);

export default new VueI18n({
    messages: {
        [Locales.NL]: nl,
    },
    locale: Locales.NL,
    fallbackLocale: Locales.NL,
});
