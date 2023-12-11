import Vue from 'vue';
import VueI18n from 'vue-i18n';
import { Locales } from './locales';

import en from './languages/en.json';
import nl from './languages/nl.json';
import store from '@/store';
import { StoreType } from '@/store/storeType';

type LocaleUnion = typeof en | typeof nl;
const storeLanguage = store.state[StoreType.SESSION].language;

export const translationFiles: Record<Locales, LocaleUnion> = {
    [Locales.EN]: en,
    [Locales.NL]: nl,
};

Vue.use(VueI18n);

// Set initial language
document.documentElement.setAttribute('lang', storeLanguage);

export default new VueI18n({
    messages: translationFiles,
    locale: storeLanguage,
    fallbackLocale: Locales.NL,
});
