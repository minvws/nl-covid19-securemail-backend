import { authApi } from '@/api';
import { Error } from '@/types/enums/error';
import { OTPType } from '@/types/enums/OTPType';
import { Module } from 'vuex';
import { ComponentState, OtpStoreAction, OtpStoreMutation } from '.';

export interface VerifyCode {
    otpType: OTPType;
    code: string;
}

export interface State {
    componentState: ComponentState;
    error: Error | null;
    phoneNumber: string;
    reportedPhoneNumber: boolean;
    isRetrying: boolean;
    retryCount: number;
}

export const getDefaultState = (): State => ({
    componentState: ComponentState.Loading,
    error: null,
    phoneNumber: '',
    reportedPhoneNumber: false,
    isRetrying: false,
    retryCount: 0,
});

const otpStore: Module<State, unknown> = {
    namespaced: true,
    state: getDefaultState(),
    actions: {
        async [OtpStoreAction.GET_CODE]({ commit }, otpType: OTPType) {
            try {
                await authApi.requestOtpCode(otpType);
                commit(OtpStoreMutation.SET_STATE, ComponentState.OtpVerification);
            } catch (error) {
                commit(OtpStoreMutation.SET_ERROR, Error.VALUE_otp_request_failed);
            }
        },
        async [OtpStoreAction.GET_INFO]({ commit }) {
            try {
                const { phoneNumber } = await authApi.requestOtpInfo();
                commit(OtpStoreMutation.SET_INFO, phoneNumber);
                commit(OtpStoreMutation.SET_STATE, ComponentState.OtpRequest);
            } catch (error) {
                commit(OtpStoreMutation.SET_ERROR, Error.VALUE_otp_info_retrieval_failed);
            }
        },
        async [OtpStoreAction.REPORT_PHONE_NUMBER]({ commit, state }) {
            try {
                await authApi.reportWrongPhoneNumber(state.phoneNumber);
                commit(OtpStoreMutation.SET_REPORTED_PHONE_NUMBER, true);
            } catch (error) {
                commit(OtpStoreMutation.SET_ERROR, Error.VALUE_otp_report_phone_number_failed);
            }
        },
        [OtpStoreAction.RESET_STATE]({ commit }) {
            commit(OtpStoreMutation.RESET_STATE);
        },
        async [OtpStoreAction.RETRY_GET_CODE]({ dispatch, commit, state }, otpType: OTPType) {
            if (state.isRetrying) return;

            commit(OtpStoreMutation.SET_IS_RETRYING, true);
            dispatch(OtpStoreAction.GET_CODE, otpType);

            setTimeout(() => {
                commit(OtpStoreMutation.SET_IS_RETRYING, false);
            }, 10 * 1000);
        },
        async [OtpStoreAction.VERIFY_CODE]({ commit }, { otpType, code }: VerifyCode) {
            try {
                await authApi.verifyOtpCode(otpType, code);
                return true;
            } catch (error) {
                commit(OtpStoreMutation.SET_ERROR, Error.VALUE_otp_verification_failed);
            }
        },
    },
    mutations: {
        [OtpStoreMutation.RESET_STATE](state: State) {
            // Object.assign to ensure object reference is not changed
            Object.assign(state, getDefaultState());
        },
        [OtpStoreMutation.SET_ERROR](state: State, error: Error) {
            state.error = error;
        },
        [OtpStoreMutation.SET_INFO](state: State, phoneNumber: string) {
            state.phoneNumber = phoneNumber;
        },
        [OtpStoreMutation.SET_IS_RETRYING](state: State, isRetrying: boolean) {
            state.retryCount = state.retryCount + 1;
            state.isRetrying = isRetrying;
        },
        [OtpStoreMutation.SET_REPORTED_PHONE_NUMBER](state: State, reported: boolean) {
            state.reportedPhoneNumber = reported;
        },
        [OtpStoreMutation.SET_STATE](state: State, componentState: ComponentState) {
            state.componentState = componentState;
        },
    },
};

export default otpStore;
