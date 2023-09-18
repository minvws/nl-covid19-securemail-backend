import { authApi } from '@/api';
import sessionStore from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { LoginType } from '@/types/enums/loginType';
import { flushCallStack, setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import BootstrapVue from 'bootstrap-vue';
import SvgVue from 'svg-vue';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';
import LoginPage from './LoginPage.vue';

jest.mock('@/api/auth.api', () => ({
    getLoginOptions: jest.fn(() =>
        Promise.resolve({
            loginTypes: [],
        })
    ),
}));

const mockRoute = {
    name: 'auth.login',
};

const mockRouter = {
    push: jest.fn(),
    replace: jest.fn(),
};

const createComponent = setupTest(
    (localVue: VueConstructor) =>
        shallowMount(LoginPage, {
            localVue,
            mocks: {
                $t: (msg: string) => msg,
                $route: mockRoute,
                $router: mockRouter,
            },
            store: new Vuex.Store({
                modules: {
                    [StoreType.SESSION]: sessionStore,
                },
            }),
        }),
    [BootstrapVue, SvgVue, Vuex]
);

describe('LoginPage.vue', () => {
    it('should load', async () => {
        const wrapper = createComponent();
        await flushCallStack();

        expect(wrapper.find('div').exists()).toBe(true);
    });

    it('should show no buttons', async () => {
        const wrapper = createComponent();
        await flushCallStack();

        expect(wrapper.vm.$store.state.session.loginOptions).toEqual([]);
    });

    it('should redirect to error page', async () => {
        jest.spyOn(authApi, 'getLoginOptions').mockImplementationOnce(() => Promise.reject());
        createComponent();
        await flushCallStack();

        expect(mockRouter.push).toHaveBeenCalledWith({ name: 'error' });
    });

    it('should show DigiD and SMS button', async () => {
        jest.spyOn(authApi, 'getLoginOptions').mockImplementationOnce(() =>
            Promise.resolve({
                loginTypes: [LoginType.VALUE_digid, LoginType.VALUE_sms],
            })
        );

        const wrapper = createComponent();
        await flushCallStack();

        expect(wrapper.vm.$store.state.session.loginOptions).toEqual([LoginType.VALUE_digid, LoginType.VALUE_sms]);
        expect(wrapper.find('[data-testid="button-digid"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="button-sms"]').exists()).toBe(true);
    });

    it('should show DigiD button', async () => {
        jest.spyOn(authApi, 'getLoginOptions').mockImplementationOnce(() =>
            Promise.resolve({
                loginTypes: [LoginType.VALUE_digid],
            })
        );

        const wrapper = createComponent();
        await flushCallStack();

        expect(wrapper.vm.$store.state.session.loginOptions).toEqual([LoginType.VALUE_digid]);
        expect(wrapper.find('[data-testid="button-digid"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="button-sms"]').exists()).toBe(false);
    });

    it('should show SMS button', async () => {
        jest.spyOn(authApi, 'getLoginOptions').mockImplementationOnce(() =>
            Promise.resolve({
                loginTypes: [LoginType.VALUE_sms],
            })
        );

        const wrapper = createComponent();
        await flushCallStack();

        expect(wrapper.vm.$store.state.session.loginOptions).toEqual([LoginType.VALUE_sms]);
        expect(wrapper.find('[data-testid="button-digid"]').exists()).toBe(false);
        expect(wrapper.find('[data-testid="button-sms"]').exists()).toBe(true);
    });

    it('should redirect to auth.2fa route if sms button is clicked', async () => {
        jest.spyOn(authApi, 'getLoginOptions').mockImplementationOnce(() =>
            Promise.resolve({
                loginTypes: [LoginType.VALUE_digid, LoginType.VALUE_sms],
            })
        );

        const wrapper = createComponent();
        await flushCallStack();

        wrapper.find('[data-testid="button-sms"]').trigger('click');
        await wrapper.vm.$nextTick();

        expect(mockRouter.push).toHaveBeenNthCalledWith(1, {
            name: 'auth.2fa',
            params: { otpType: LoginType.VALUE_sms },
        });
    });

    it('should redirect to home if no login options', async () => {
        createComponent();
        await flushCallStack();

        expect(mockRouter.replace).toHaveBeenCalledWith({ name: 'home' });
    });

    it('should redirect to auth.2fa route if 1 login option which is a key of OTPTypeOptions', async () => {
        jest.spyOn(authApi, 'getLoginOptions').mockImplementationOnce(() =>
            Promise.resolve({
                loginTypes: [LoginType.VALUE_sms],
            })
        );

        createComponent();
        await flushCallStack();

        expect(mockRouter.replace).toHaveBeenCalledWith({
            name: 'auth.2fa',
            params: { otpType: LoginType.VALUE_sms },
        });
    });

    it('should not redirect if 1 login options which is NOT a key of OTPTypeOptions', async () => {
        jest.spyOn(authApi, 'getLoginOptions').mockImplementationOnce(() =>
            Promise.resolve({
                loginTypes: [LoginType.VALUE_digid],
            })
        );

        createComponent();
        await flushCallStack();

        expect(mockRouter.replace).not.toHaveBeenCalled();
    });

    it('should not redirect if multiple login options', async () => {
        jest.spyOn(authApi, 'getLoginOptions').mockImplementationOnce(() =>
            Promise.resolve({
                loginTypes: [LoginType.VALUE_digid, LoginType.VALUE_sms],
            })
        );

        createComponent();
        await flushCallStack();

        expect(mockRouter.replace).not.toHaveBeenCalled();
    });
});
