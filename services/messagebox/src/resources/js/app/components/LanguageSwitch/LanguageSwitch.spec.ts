import { Locales } from '@/i18n/locales';
import sessionStore, { State as SessionState } from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import { BDropdown, BDropdownItem } from 'bootstrap-vue';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';
import LanguageSwitch from './LanguageSwitch.vue';

const createComponent = setupTest((localVue: VueConstructor, sessionState: Partial<SessionState> = {}) => {
    const sessionStoreModule = {
        ...sessionStore,
        state: {
            ...sessionStore.state,
            ...sessionState,
        },
    };

    return shallowMount<Vue>(LanguageSwitch, {
        localVue,
        mocks: {
            $t: (msg: string) => msg,
        },
        store: new Vuex.Store({
            modules: {
                [StoreType.SESSION]: sessionStoreModule,
            },
        }),
        stubs: {
            BDropdown,
            BDropdownItem,
        },
        attachTo: document.body,
    });
});

describe('LanguageSwitch.vue', () => {
    it('should load', () => {
        const wrapper = createComponent();

        expect(wrapper.findComponent({ name: 'BDropdown' }).exists()).toBe(true);
    });

    it('should add dropdownId to menu', () => {
        const wrapper = createComponent();

        expect(wrapper.find('ul[role="menu"]').attributes('id')).toBe('language-dropdown');
    });

    it('should render languageOptions', () => {
        const wrapper = createComponent();

        const languages = Object.values(Locales);

        expect(wrapper.findAllComponents({ name: 'BDropdownItem' }).length).toBe(languages.length);
    });

    it('should render the current language with aria-current', () => {
        const language = Locales.EN;
        const wrapper = createComponent({ language });

        const currentItem = wrapper.find('a[aria-current]');

        expect(currentItem.text()).toContain(`app.locales.${language}`);
    });

    it('should render non-current languages as buttons without aria-current', () => {
        const wrapper = createComponent();

        const nonCurrentItems = wrapper.findAll('a:not([aria-current])');
        const languages = Object.values(Locales);

        // Expect all languages minus the current language
        expect(nonCurrentItems.length).toBe(languages.length - 1);
    });

    it('should focus on first non-current language when opening dropdown', async () => {
        const wrapper = createComponent();

        const nonCurrentItems = wrapper.findAll('a:not([aria-current])');
        expect(document.activeElement).toBe(document.body);

        wrapper
            .findAllComponents({ name: 'BDropdown' })
            .at(0)
            .vm.$emit('shown');
        await wrapper.vm.$nextTick();

        expect(document.activeElement).toBe(nonCurrentItems.at(0).element);
    });

    it('should set the language if clicked', async () => {
        const languages = Object.values(Locales);
        const wrapper = createComponent({ language: languages[0] });

        expect(wrapper.vm.$store.state.session.language).toBe(languages[0]);
        const currentItem = wrapper.find('a:not([aria-current])');
        await currentItem.vm.$emit('click');

        expect(wrapper.vm.$store.state.session.language).toBe(languages[1]);
    });
});
