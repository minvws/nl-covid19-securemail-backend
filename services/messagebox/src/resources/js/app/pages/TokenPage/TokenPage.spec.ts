import { authApi } from '@/api';
import sessionStore, { PairingCodeResponse } from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { Error } from '@/types/enums/error';
import { flushCallStack, setupTest } from '@/utils/testUtils';
import '@/vee-validate';
import { mount } from '@vue/test-utils';
import { AxiosError } from 'axios';
import BootstrapVue from 'bootstrap-vue';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';
import TokenPage from './TokenPage.vue';

jest.spyOn(authApi, 'loginByCode').mockImplementation(() => Promise.resolve());

const defaultMockRoute = {
    name: 'auth.token',
    params: {},
};

const mockRouter = {
    push: jest.fn(),
};

const createComponent = setupTest(
    async (localVue: VueConstructor) => {
        const wrapper = mount(TokenPage, {
            localVue,
            stubs: {
                SvgVue: true,
            },
            mocks: {
                $t: (msg: string) => msg,
                $route: defaultMockRoute,
                $router: mockRouter,
            },
            store: new Vuex.Store({
                modules: {
                    [StoreType.SESSION]: sessionStore,
                },
            }),
            attachTo: document.body,
        });

        await flushCallStack();

        return wrapper;
    },
    [BootstrapVue, Vuex]
);

describe('TokenPage.vue', () => {
    it('should load', async () => {
        const wrapper = await createComponent();

        expect(wrapper.find('div').exists()).toBe(true);
    });

    it('should validate email', async () => {
        const email = 'test@test.nl';
        const wrapper = await createComponent();

        const input = wrapper.find('[data-testid="input-email"]');
        input.setValue(email);
        input.trigger('blur');
        await flushCallStack();

        expect(input.classes()).toContain('is-valid');
        expect(input.props().state).toBe(true);
        expect(wrapper.vm.$data.email).toBe(email);
    });

    it('should invalidate email', async () => {
        const email = 'test@test';
        const wrapper = await createComponent();

        const input = wrapper.find('[data-testid="input-email"]');
        input.setValue(email);
        input.trigger('blur');
        await flushCallStack();

        expect(input.classes()).toContain('is-invalid');
        expect(input.props().state).toBe(false);
    });

    it('should validate code', async () => {
        const code = '123123';
        const wrapper = await createComponent();

        const input = wrapper.find('[data-testid="input-code"]');
        input.setValue(code);
        input.trigger('blur');
        await flushCallStack();

        expect(input.classes()).toContain('is-valid');
        expect(input.props().state).toBe(true);
        expect(wrapper.vm.$data.pairingCode).toBe(code);
    });

    it('should invalidate code', async () => {
        const code = '12312';
        const wrapper = await createComponent();

        const input = wrapper.find('[data-testid="input-code"]');
        input.setValue(code);
        input.trigger('blur');
        await flushCallStack();

        expect(input.classes()).toContain('is-invalid');
        expect(input.props().state).toBe(false);
    });

    it('should focus on invalid field after submit', async () => {
        const email = 'test@test.nl';
        const code = '12312';

        const wrapper = await createComponent();

        wrapper.vm.$set(wrapper.vm.$data, 'email', email);
        wrapper.vm.$set(wrapper.vm.$data, 'pairingCode', code);

        const submit = wrapper.find('[data-testid="button-submit"]');
        await submit.trigger('submit');
        await flushCallStack();

        const input = wrapper.find('[data-testid="input-code"]');
        expect(document.activeElement).toBe(input.element);
    });

    it('should redirect to LoginPage after submit', async () => {
        const email = 'test@test.nl';
        const code = '123123';

        const loginByCodeSpy = jest.spyOn(authApi, 'loginByCode').mockImplementationOnce(() => Promise.resolve());

        const wrapper = await createComponent();

        wrapper.vm.$set(wrapper.vm.$data, 'email', email);
        wrapper.vm.$set(wrapper.vm.$data, 'pairingCode', code);
        await wrapper.vm.$nextTick();

        const submit = wrapper.find('[data-testid="button-submit"]');
        await submit.trigger('submit');
        await flushCallStack();

        expect(loginByCodeSpy).toHaveBeenNthCalledWith(1, email, code);
        expect(mockRouter.push).toHaveBeenNthCalledWith(1, {
            name: 'auth.login',
        });
    });

    it('should redirect to ErrorPage after invalid data', async () => {
        const email = 'test@test.nl';
        const code = '123123';
        const axiosError: Partial<AxiosError<PairingCodeResponse>> = {
            response: {
                status: 410,
                statusText: 'Gone',
                headers: {},
                config: {},
                data: {
                    error: Error.VALUE_pairing_code_expired,
                    emailAddress: 'test@test.nl',
                    pairingCodeUuid: '123-123-123',
                },
            },
        };

        const loginByCodeSpy = jest
            .spyOn(authApi, 'loginByCode')
            .mockImplementationOnce(() => Promise.reject(axiosError));

        const wrapper = await createComponent();

        wrapper.vm.$set(wrapper.vm.$data, 'email', email);
        wrapper.vm.$set(wrapper.vm.$data, 'pairingCode', code);
        await wrapper.vm.$nextTick();

        const submit = wrapper.find('[data-testid="button-submit"]');
        await submit.trigger('submit');
        await flushCallStack();

        expect(loginByCodeSpy).toHaveBeenNthCalledWith(1, email, code);
        expect(mockRouter.push).toHaveBeenNthCalledWith(1, {
            name: 'error',
            params: {
                code: Error.VALUE_pairing_code_expired,
            },
        });
    });
});
