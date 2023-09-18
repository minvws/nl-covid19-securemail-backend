import { authApi } from '@/api';
import BaseModal from '@/app/components/BaseModal/BaseModal.vue';
import { Environment } from '@/env';
import i18n from '@/i18n';
import Modal from '@/plugins/modal';
import store from '@/store';
import { SessionStoreAction } from '@/store/sessionStore';
import { State as SessionState, State } from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { defineComponent } from '@/typeHelpers';
import { useFakeTimers } from '@/utils/testUtils';
import { createLocalVue, shallowMount } from '@vue/test-utils';
import { AxiosError, AxiosResponse } from 'axios';
import BootstrapVue, { BModal } from 'bootstrap-vue';
import Vuex from 'vuex';

const lifetime = 15;

jest.mock('@/api/auth.api', () => ({
    keepAlive: jest.fn(() => Promise.resolve()),
    logout: jest.fn(() => Promise.resolve()),
}));

jest.mock('@/env', () => {
    const EnvironmentType = jest.requireActual('@/env').EnvironmentType;

    const env: Environment = {
        version: 'latest',
        environment: EnvironmentType.Production,
        lifetime,
    };

    return env;
});

jest.mock('@/store', () => {
    const sessionState: SessionState = {
        name: '',
        isLoggedIn: false,
        language: 'nl',
        sessionMessageUuid: undefined,
        loginOptions: [],
        pairingCodeResponse: null,
        digidResponse: null,
    };

    return {
        dispatch: jest.fn(() => Promise.resolve()),
        state: {
            session: sessionState,
        },
    };
});

global.window = Object.create(window);
Object.defineProperty(window, 'location', {
    value: {
        reload: jest.fn(),
    },
});

const mockRouter = {
    replace: jest.fn(),
};

describe('sessionInterceptor', () => {
    const setWrapper = (sessionState: Partial<State> = {}) => {
        const localVue = createLocalVue();
        localVue.use(BootstrapVue);
        localVue.use(Modal);
        localVue.use(Vuex);

        const TestComponent = defineComponent({ name: 'Test', template: '<base-modal />' });

        store.state.session = {
            ...store.state.session,
            ...sessionState,
        };

        const app = shallowMount(TestComponent, {
            localVue,
            mocks: {
                $router: mockRouter,
            },
            stubs: {
                BaseModal,
                BModal,
            },
        });

        window.app = app.vm;

        return app;
    };

    beforeEach(() => {
        useFakeTimers(new Date('2022-01-01 12:00:00'));
        jest.clearAllMocks();
        jest.clearAllTimers();

        jest.isolateModules(() => {
            jest.requireActual('./sessionInterceptor');
        });
    });

    it('should set an interval for sessionCallback', () => {
        jest.spyOn(global, 'setInterval');
        jest.requireActual('./sessionInterceptor');

        expect(setInterval).toBeCalledTimes(1);
    });

    describe('Modal: session <5 minutes left', () => {
        it('should show modal', () => {
            const wrapper = setWrapper({ isLoggedIn: true });
            const spyI18n = jest.spyOn(i18n, 't').mockReturnValue('');
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            // Set time to 5 minutes left, this should not trigger the modal yet
            jest.advanceTimersByTime((lifetime - 5) * 60 * 1000);
            expect(spyShow).not.toBeCalled();

            // Move time 1 second, this should trigger the modal
            jest.advanceTimersByTime(1000);
            expect(spyShow).toBeCalled();

            // Ensure this is the session modal
            expect(spyI18n).toHaveBeenCalledTimes(3);
            expect(spyI18n).toHaveBeenCalledWith('components.sessionModal.session.title');
        });

        it('should NOT show modal and clear interval if user is not logged in', () => {
            jest.spyOn(global, 'clearInterval');
            const wrapper = setWrapper({ isLoggedIn: false });
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            // Set time to 3 minutes left to trigger modal
            jest.advanceTimersByTime((lifetime - 3) * 60 * 1000);
            expect(spyShow).not.toBeCalled();
            expect(clearInterval).toBeCalledTimes(1);
        });

        it('should call authApi.keepAlive when user confirms modal', () => {
            const wrapper = setWrapper({ isLoggedIn: true });
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            // Set time to 3 minutes left to trigger modal
            jest.advanceTimersByTime((lifetime - 3) * 60 * 1000);

            // Reset count after triggering modal
            spyShow.mockClear();
            expect(spyShow).not.toBeCalled();

            wrapper.findComponent({ name: 'BModal' }).vm.$emit('ok');

            expect(authApi.keepAlive).toBeCalled();
            expect(spyShow).not.toBeCalled();
        });

        it('should show connectivity modal if authApi.keepAlive request fails', async () => {
            const wrapper = setWrapper({ isLoggedIn: true });
            const spyI18n = jest.spyOn(i18n, 't').mockReturnValue('');
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            jest.spyOn(authApi, 'keepAlive').mockRejectedValueOnce({});

            // Set time to 3 minutes left to trigger modal
            jest.advanceTimersByTime((lifetime - 3) * 60 * 1000);

            // Reset spies after triggering modal
            spyI18n.mockClear();
            expect(spyI18n).not.toBeCalled();
            spyShow.mockClear();
            expect(spyShow).not.toBeCalled();

            wrapper.findComponent({ name: 'BModal' }).vm.$emit('ok');
            await wrapper.vm.$nextTick();

            expect(authApi.keepAlive).toBeCalled();
            expect(spyShow).toBeCalled();

            // Ensure this is the connectivity modal
            expect(spyI18n).toHaveBeenCalledTimes(3);
            expect(spyI18n).toHaveBeenCalledWith('components.sessionModal.connectivity.title');

            // Logout dispatch should only be called if response status === 401
            expect(store.dispatch).not.toBeCalled();
        });

        it('should logout user if authApi.keepAlive request fails with status code 401', async () => {
            const wrapper = setWrapper({ isLoggedIn: true });

            const axiosError: Partial<AxiosError> = {
                response: {
                    config: {},
                    data: {},
                    headers: {},
                    status: 401,
                    statusText: 'Unauthorized',
                },
            };
            jest.spyOn(authApi, 'keepAlive').mockRejectedValueOnce(axiosError);

            // Set time to 3 minutes left to trigger modal
            jest.advanceTimersByTime((lifetime - 3) * 60 * 1000);

            wrapper.findComponent({ name: 'BModal' }).vm.$emit('ok');
            await wrapper.vm.$nextTick();

            expect(authApi.keepAlive).toBeCalled();
            expect(store.dispatch).toBeCalledWith(`${StoreType.SESSION}/${SessionStoreAction.LOGOUT}`);
        });
    });

    describe('Modal: session 0 minutes and 0 seconds left', () => {
        it(`should logout user (hide modal, clear interval, dispatch ${SessionStoreAction.LOGOUT}, replace route) and show modal`, async () => {
            jest.spyOn(global, 'clearInterval');
            jest.spyOn(global.location, 'reload');
            const wrapper = setWrapper({ isLoggedIn: true });
            const spyI18n = jest.spyOn(i18n, 't').mockReturnValue('');
            const spyHide = jest.spyOn(wrapper.vm.$modal, 'hide');

            // Set time to 1 second left, this should not trigger the logout yet
            jest.advanceTimersByTime(lifetime * 60 * 1000 - 1000);
            expect(clearInterval).not.toBeCalled();
            expect(store.dispatch).not.toBeCalled();
            expect(mockRouter.replace).not.toBeCalled();
            expect(spyHide).not.toBeCalled();

            // Reset i18n spy after triggering modal
            spyI18n.mockClear();
            expect(spyI18n).not.toBeCalled();

            // Move time by more then 1 second, this should trigger the logout
            jest.advanceTimersByTime(2000);
            await wrapper.vm.$nextTick();

            expect(clearInterval).toBeCalled();
            expect(store.dispatch).toBeCalledWith(`${StoreType.SESSION}/${SessionStoreAction.LOGOUT}`);
            expect(mockRouter.replace).toBeCalledWith({ name: 'home' });
            expect(spyHide).toBeCalled();

            // Ensure this is the logout modal
            expect(spyI18n).toHaveBeenCalledWith('components.sessionModal.logout.title');

            // Window should not be reloaded yet
            expect(window.location.reload).not.toHaveBeenCalled();
        });

        it(`should reload page when user confirms logout modal`, async () => {
            const wrapper = setWrapper({ isLoggedIn: true });

            // Set time to 0 seconds left, this should trigger the logout
            jest.advanceTimersByTime(lifetime * 60 * 1000);

            // Move time by more then 1 second, this should trigger the logout
            jest.advanceTimersByTime(2000);
            await wrapper.vm.$nextTick();

            // Confirm the modal
            wrapper.findComponent({ name: 'BModal' }).vm.$emit('ok');

            expect(window.location.reload).toHaveBeenCalled();
        });
    });

    describe('sessionResponseInterceptor', () => {
        it('should set countDownDate if header "x-session-expiry-date" is set', () => {
            useFakeTimers(new Date('2022-01-01 12:00:00'));

            const wrapper = setWrapper({ isLoggedIn: true });
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            const response: AxiosResponse = {
                config: {},
                data: {},
                headers: {
                    'x-session-expiry-date': '2022-01-01T12:03:00+00:00',
                },
                status: 200,
                statusText: 'OK',
            };

            jest.isolateModules(() => {
                const { sessionResponseInterceptor } = jest.requireActual('./sessionInterceptor');
                sessionResponseInterceptor(response);
            });

            // Run timers that would run now
            jest.runOnlyPendingTimers();

            // Modal should be shown
            expect(spyShow).toBeCalled();
        });

        it('should NOT set countDownDate if header "x-session-expiry-date" is not set', () => {
            useFakeTimers(new Date('2022-01-01 12:00:00'));

            const wrapper = setWrapper({ isLoggedIn: true });
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            const response: AxiosResponse = {
                config: {},
                data: {},
                headers: {},
                status: 200,
                statusText: 'OK',
            };

            jest.isolateModules(() => {
                const { sessionResponseInterceptor } = jest.requireActual('./sessionInterceptor');
                sessionResponseInterceptor(response);
            });

            // Run timers that would run now
            jest.runOnlyPendingTimers();

            // Modal should be shown
            expect(spyShow).not.toBeCalled();
        });

        it('should set countDownDate=null if header "x-session-expiry-date" is wrong', () => {
            useFakeTimers(new Date('2022-01-01 12:00:00'));

            const wrapper = setWrapper({ isLoggedIn: true });
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            const response: AxiosResponse = {
                config: {},
                data: {},
                headers: {
                    'x-session-expiry-date': 'wrong',
                },
                status: 200,
                statusText: 'OK',
            };

            jest.isolateModules(() => {
                const { sessionResponseInterceptor } = jest.requireActual('./sessionInterceptor');
                sessionResponseInterceptor(response);
            });

            // Run timers that would run now
            jest.runOnlyPendingTimers();

            // Modal should not be shown due to invalid countDownDate
            expect(spyShow).not.toBeCalled();
        });
    });

    describe('sessionErrorInterceptor', () => {
        it('should set countDownDate if header "x-session-expiry-date" is set', () => {
            useFakeTimers(new Date('2022-01-01 12:00:00'));

            const wrapper = setWrapper({ isLoggedIn: true });
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            const error: Partial<AxiosError> = {
                response: {
                    config: {},
                    data: {},
                    headers: {
                        'x-session-expiry-date': '2022-01-01T12:03:00+00:00',
                    },
                    status: 400,
                    statusText: 'OK',
                },
            };

            jest.isolateModules(() => {
                const { sessionErrorInterceptor } = jest.requireActual('./sessionInterceptor');
                expect(sessionErrorInterceptor(error as AxiosError)).rejects.toEqual(error);
            });

            // Run timers that would run now
            jest.runOnlyPendingTimers();

            // Modal should be shown
            expect(spyShow).toBeCalled();
        });

        it('should NOT set countDownDate if header "x-session-expiry-date" is not set', () => {
            useFakeTimers(new Date('2022-01-01 12:00:00'));

            const wrapper = setWrapper({ isLoggedIn: true });
            const spyShow = jest.spyOn(wrapper.vm.$modal, 'show');

            const error: Partial<AxiosError> = {
                response: {
                    config: {},
                    data: {},
                    headers: {},
                    status: 200,
                    statusText: 'OK',
                },
            };

            jest.isolateModules(() => {
                const { sessionErrorInterceptor } = jest.requireActual('./sessionInterceptor');
                expect(sessionErrorInterceptor(error as AxiosError)).rejects.toEqual(error);
            });

            // Run timers that would run now
            jest.runOnlyPendingTimers();

            // Modal should be shown
            expect(spyShow).not.toBeCalled();
        });
    });
});
