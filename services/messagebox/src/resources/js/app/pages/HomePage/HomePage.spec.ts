import sessionStore, { State as SessionState } from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { Error } from '@/types/enums/error';
import { flushCallStack, setupTest } from '@/utils/testUtils';
import '@/vee-validate';
import { shallowMount } from '@vue/test-utils';
import BootstrapVue from 'bootstrap-vue';
import SvgVue from 'svg-vue';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';
import HomePage from './HomePage.vue';

const defaultMockRoute = {
    name: 'home',
    params: {},
};

const mockRouter = {
    push: jest.fn(),
};

const createComponent = setupTest(
    (localVue: VueConstructor, sessionState: Partial<SessionState> = {}) => {
        const sessionStoreModule = {
            ...sessionStore,
            state: {
                ...sessionStore.state,
                ...sessionState,
            },
        };

        return shallowMount(HomePage, {
            localVue,
            mocks: {
                $t: (msg: string) => msg,
                $route: defaultMockRoute,
                $router: mockRouter,
            },
            store: new Vuex.Store({
                modules: {
                    [StoreType.SESSION]: sessionStoreModule,
                },
            }),
        });
    },
    [BootstrapVue, SvgVue, Vuex]
);

describe('HomePage.vue', () => {
    it('should load', async () => {
        const wrapper = createComponent();
        await flushCallStack();

        expect(wrapper.find('div').exists()).toBe(true);
    });

    it('should redirect to token page on click button', async () => {
        const wrapper = createComponent();
        await flushCallStack();

        const button = wrapper.findComponent({ name: 'BButton' });
        await button.trigger('click');

        expect(mockRouter.push).toHaveBeenNthCalledWith(1, {
            name: 'auth.token',
        });
    });

    it('should clear digidResponse in store', async () => {
        const wrapper = createComponent({
            digidResponse: {
                status: 'error',
                error: Error.VALUE_digid_service_unavailable,
                name: 'test',
            },
        });

        await flushCallStack();

        expect(wrapper.vm.$store.state.session.digidResponse).not.toBeNull();

        const button = wrapper.findComponent({ name: 'BButton' });
        await button.trigger('click');
        await flushCallStack();

        expect(wrapper.vm.$store.state.session.digidResponse).toBeNull();
    });
});
