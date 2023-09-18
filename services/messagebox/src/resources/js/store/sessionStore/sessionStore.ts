import { authApi } from '@/api';
import i18n from '@/i18n';
import { Locales } from '@/i18n/locales';
import { Error } from '@/types/enums/error';
import { LoginType } from '@/types/enums/loginType';
import { AxiosError } from 'axios';
import { Module } from 'vuex';
import { SessionStoreAction, SessionStoreMutation } from '.';
export interface LoginByCode {
    email: string;
    pairingCode: string;
}

export type PairingCodeResponse = {
    error: Error.VALUE_pairing_code_expired | Error.VALUE_pairing_code_invalid;
    pairingCodeUuid: string | null;
    emailAddress: string;
};

export type DigidResponse = {
    status: string;
    error: Error;
    name?: string;
};

export interface State {
    name: string;
    language: string;
    isLoggedIn: boolean;
    sessionMessageUuid?: string;
    loginOptions: LoginType[];
    pairingCodeResponse: PairingCodeResponse | null;
    digidResponse: DigidResponse | null;
}

const getDefaultState = (): State => ({
    name: '',
    language: localStorage.getItem('language') || window.language || Locales.NL,
    isLoggedIn: window.isLoggedIn || false,
    sessionMessageUuid: window.sessionMessageUuid,
    loginOptions: [],
    pairingCodeResponse: window.pairingCodeResponse,
    digidResponse: window.digidResponse,
});

const sessionStore: Module<State, unknown> = {
    namespaced: true,
    state: getDefaultState(),
    actions: {
        async [SessionStoreAction.GET_LOGIN_OPTIONS]({ commit }) {
            commit(SessionStoreMutation.SET_LOGIN_OPTIONS, []);
            const { loginTypes, name } = await authApi.getLoginOptions();
            commit(SessionStoreMutation.SET_LOGIN_OPTIONS, loginTypes);
            commit(SessionStoreMutation.SET_NAME, name);
        },
        async [SessionStoreAction.LOGIN_BY_CODE]({ commit }, { email, pairingCode }: LoginByCode) {
            try {
                await authApi.loginByCode(email, pairingCode);
                return true;
            } catch (error) {
                const errorResponse = error as AxiosError<PairingCodeResponse>;
                commit(SessionStoreMutation.SET_PAIRING_CODE_RESPONSE, errorResponse.response?.data);
                return errorResponse.response?.data.error;
            }
        },
        async [SessionStoreAction.LOGOUT]({ commit }) {
            await authApi.logout();
            commit(SessionStoreMutation.LOGOUT);
        },
        [SessionStoreAction.SET_LANGUAGE]({ commit }, language: Locales) {
            i18n.locale = language;
            localStorage.setItem('language', language);
            document.documentElement.setAttribute('lang', language);
            commit(SessionStoreMutation.SET_LANGUAGE, language);
        },
        async [SessionStoreAction.GET_NEW_PAIRING_CODE]({}, pairingCodeUuid: string) {
            await authApi.requestNewPairingCode(pairingCodeUuid);
        },
        [SessionStoreAction.CLEAR_DIGID_RESPONSE]({ commit }) {
            commit(SessionStoreMutation.CLEAR_DIGID_RESPONSE);
        },
    },
    mutations: {
        [SessionStoreMutation.LOGOUT](state: State) {
            state.isLoggedIn = false;
        },
        [SessionStoreMutation.SET_NAME](state: State, name: string) {
            state.name = name;
        },
        [SessionStoreMutation.SET_LANGUAGE](state: State, language: Locales) {
            state.language = language;
        },
        [SessionStoreMutation.SET_LOGIN_OPTIONS](state: State, loginOptions: LoginType[]) {
            state.loginOptions = loginOptions;
        },
        [SessionStoreMutation.CLEAR_DIGID_RESPONSE](state: State) {
            state.digidResponse = null;
        },
        [SessionStoreMutation.SET_PAIRING_CODE_RESPONSE](state: State, pairingCodeResponse: PairingCodeResponse) {
            state.pairingCodeResponse = pairingCodeResponse;
        },
    },
};

export default sessionStore;
