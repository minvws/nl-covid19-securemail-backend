import { authApi } from '@/api';
import i18n from '@/i18n';
import { Locales } from '@/i18n/locales';
import { LoginType } from '@/types/enums/loginType';
import { createLocalVue } from '@vue/test-utils';
import Vuex from 'vuex';
import { RootStoreState } from '..';
import { StoreType } from '../storeType';
import { LoginByCode, State } from './sessionStore';
import { SessionStoreAction } from './sessionStoreAction';
import { SessionStoreMutation } from './sessionStoreMutation';

jest.mock('@/api/auth.api', () => {
    const LoginType = jest.requireActual('@/types/enums/loginType').LoginType;

    return {
        getLoginOptions: jest.fn(() =>
            Promise.resolve({
                loginTypes: [LoginType.VALUE_digid, LoginType.VALUE_sms],
            })
        ),
        loginByCode: jest.fn(() => Promise.resolve()),
        logout: jest.fn(() => Promise.resolve()),
    };
});

describe('sessionStore', () => {
    beforeEach(() => {
        localStorage.clear();
        window.isLoggedIn = false;
        window.language = undefined;
        window.sessionMessageUuid = undefined;
        window.digidResponse = null;
    });

    const getStore = (state: Partial<State> = {}) => {
        const localVue = createLocalVue();
        localVue.use(Vuex);

        let store = new Vuex.Store<RootStoreState>({});

        // Isolate import of sessionStore to ensure getDefaultState is called
        jest.isolateModules(() => {
            const sessionStore = jest.requireActual('./sessionStore').default;

            store.registerModule(StoreType.SESSION, {
                ...sessionStore,
                state: {
                    ...sessionStore.state,
                    ...state,
                },
            });
        });

        return store;
    };

    describe('State: default language', () => {
        it('should 1. Try to get it from the localStorage, key=language', () => {
            localStorage.setItem('language', Locales.EN);
            const state = getStore().state[StoreType.SESSION];

            expect(state.language).toBe(Locales.EN);
        });

        it('should 2. Try to get it from the window.language', () => {
            window.language = Locales.EN;
            const state = getStore().state[StoreType.SESSION];

            expect(state.language).toBe(Locales.EN);
        });

        it('should 3. Otherwise revert to the default language', () => {
            const state = getStore().state[StoreType.SESSION];

            expect(state.language).toBe(Locales.NL);
        });
    });

    describe('State: isLoggedIn', () => {
        it('should 1. Try to get the value from window.isLoggedIn', () => {
            window.isLoggedIn = true;
            const state = getStore().state[StoreType.SESSION];

            expect(state.isLoggedIn).toBe(true);
        });

        it('should 2. Otherwise default to false', () => {
            const state = getStore().state[StoreType.SESSION];

            expect(state.isLoggedIn).toBe(false);
        });
    });

    describe('State: sessionMessageUuid', () => {
        it('should 1. Try to get the value from window.sessionMessageUuid', () => {
            window.sessionMessageUuid = '0000';
            const state = getStore().state[StoreType.SESSION];

            expect(state.sessionMessageUuid).toBe('0000');
        });

        it('should 2. Otherwise default to undefined', () => {
            const state = getStore().state[StoreType.SESSION];

            expect(state.sessionMessageUuid).toBeUndefined();
        });
    });

    describe(`Action: ${SessionStoreAction.GET_LOGIN_OPTIONS}`, () => {
        it('should retrieve and commit login options', async () => {
            const store = getStore();
            const state = store.state[StoreType.SESSION];
            const spyCommit = jest.spyOn(store, 'commit');

            expect(state.loginOptions).toEqual([]);
            await store.dispatch(`${StoreType.SESSION}/${SessionStoreAction.GET_LOGIN_OPTIONS}`);

            // Check commits and API call
            expect(spyCommit).toHaveBeenNthCalledWith(
                1,
                `${StoreType.SESSION}/${SessionStoreMutation.SET_LOGIN_OPTIONS}`,
                [],
                undefined
            );
            expect(authApi.getLoginOptions).toHaveBeenCalled();
            expect(spyCommit).toHaveBeenNthCalledWith(
                2,
                `${StoreType.SESSION}/${SessionStoreMutation.SET_LOGIN_OPTIONS}`,
                [LoginType.VALUE_digid, LoginType.VALUE_sms],
                undefined
            );

            // Check state
            expect(state.loginOptions).toEqual([LoginType.VALUE_digid, LoginType.VALUE_sms]);
        });
    });

    describe(`Action: ${SessionStoreAction.LOGIN_BY_CODE}`, () => {
        it('should call loginBycode', async () => {
            const store = getStore();
            const payload: LoginByCode = {
                email: 'test@example.com',
                pairingCode: '1234',
            };

            await store.dispatch(`${StoreType.SESSION}/${SessionStoreAction.LOGIN_BY_CODE}`, payload);

            expect(authApi.loginByCode).toHaveBeenCalledWith(payload.email, payload.pairingCode);
        });
    });

    describe(`Action: ${SessionStoreAction.LOGOUT}`, () => {
        it('should call logout method', async () => {
            const store = getStore();

            await store.dispatch(`${StoreType.SESSION}/${SessionStoreAction.LOGOUT}`);
            expect(authApi.logout).toHaveBeenCalled();
        });

        it('should set isLoggedIn to false', async () => {
            const store = getStore({ isLoggedIn: true });

            expect(store.state[StoreType.SESSION].isLoggedIn).toBe(true);
            await store.dispatch(`${StoreType.SESSION}/${SessionStoreAction.LOGOUT}`);

            expect(store.state[StoreType.SESSION].isLoggedIn).toBe(false);
        });
    });

    describe(`Action: ${SessionStoreAction.SET_LANGUAGE}`, () => {
        it('should set i18n, localStorage (key=language), state and HTML tag to given language', () => {
            const store = getStore();
            const state = store.state[StoreType.SESSION];

            expect(i18n.locale).toBe(Locales.NL);
            expect(localStorage.getItem('language')).toBeNull();
            expect(state.language).toBe(Locales.NL);
            // Null due to jest.mock()
            expect(document.documentElement.getAttribute('lang')).toBe(null);

            store.dispatch(`${StoreType.SESSION}/${SessionStoreAction.SET_LANGUAGE}`, Locales.EN);

            expect(i18n.locale).toBe(Locales.EN);
            expect(localStorage.getItem('language')).toBe(Locales.EN);
            expect(state.language).toBe(Locales.EN);
            expect(document.documentElement.getAttribute('lang')).toBe(Locales.EN);
        });
    });

    describe(`Mutation: ${SessionStoreMutation.LOGOUT}`, () => {
        it('should set isLoggedIn to false', () => {
            const store = getStore({ isLoggedIn: true });
            const state = store.state[StoreType.SESSION];

            expect(state.isLoggedIn).toBe(true);
            store.commit(`${StoreType.SESSION}/${SessionStoreMutation.LOGOUT}`);

            expect(state.isLoggedIn).toBe(false);
        });
    });

    describe(`Mutation: ${SessionStoreMutation.SET_LANGUAGE}`, () => {
        it('should set language to given language', () => {
            const store = getStore();
            const state = store.state[StoreType.SESSION];

            expect(state.language).toBe(Locales.NL);
            store.commit(`${StoreType.SESSION}/${SessionStoreMutation.SET_LANGUAGE}`, Locales.EN);

            expect(state.language).toBe(Locales.EN);
        });
    });

    describe(`Mutation: ${SessionStoreMutation.SET_LOGIN_OPTIONS}`, () => {
        it('should set loginOptions to given loginOptions', () => {
            const store = getStore();
            const state = store.state[StoreType.SESSION];

            expect(state.loginOptions).toEqual([]);
            const loginOptions = [LoginType.VALUE_digid];
            store.commit(`${StoreType.SESSION}/${SessionStoreMutation.SET_LOGIN_OPTIONS}`, loginOptions);

            expect(state.loginOptions).toEqual(loginOptions);
        });
    });
});
