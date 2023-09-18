import DigidLoginButton from '@/app/components/DigidLoginButton/DigidLoginButton.vue';
import sessionStore from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { Error } from '@/types/enums/error';
import { setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';

Object.defineProperty(window, 'location', {
    value: {
        href: 'http://dummy.com',
    },
});

const createComponent = setupTest((localVue: VueConstructor, props?: object, sessionState: object = {}) => {
    const sessionStoreModule = {
        ...sessionStore,
        state: {
            ...sessionStore.state,
            ...sessionState,
        },
    };

    return shallowMount(DigidLoginButton, {
        localVue,
        propsData: props,
        mocks: {
            $t: (msg: string) => msg,
        },
        store: new Vuex.Store({
            modules: {
                [StoreType.SESSION]: sessionStoreModule,
            },
        }),
    });
});

describe('DigidLoginButton.vue', () => {
    it('should load the supplied prop href in the Bootstrap button', () => {
        const props = {
            loginUrl: 'test',
        };

        const wrapper = createComponent(props);
        expect(wrapper.findComponent({ name: 'BButton' }).attributes('href')).toBe(props.loginUrl);
    });

    it('should redirect when pressing space', () => {
        const props = {
            loginUrl: 'test',
        };

        const wrapper = createComponent(props);
        wrapper.findComponent({ name: 'BButton' }).trigger('keyup.space');

        expect(window.location.href).toBe(props.loginUrl);
    });

    it('should show error when digid failed', () => {
        const props = {
            loginUrl: 'test',
        };

        const state = {
            digidResponse: {
                status: 'error',
                error: Error.VALUE_digid_canceled,
            },
        };

        const wrapper = createComponent(props, state);
        expect(wrapper.find('[data-testid="wrapper-digid-error"]').exists()).toBe(true);
    });
});
