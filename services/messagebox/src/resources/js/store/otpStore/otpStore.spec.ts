import { authApi } from '@/api';
import { Error } from '@/types/enums/error';
import { OTPType } from '@/types/enums/OTPType';
import { fakerjs, useFakeTimers } from '@/utils/testUtils';
import { createLocalVue } from '@vue/test-utils';
import Vuex from 'vuex';
import { ComponentState } from '.';
import { RootStoreState } from '..';
import { StoreType } from '../storeType';
import otpStore, { getDefaultState, State, VerifyCode } from './otpStore';
import { OtpStoreAction } from './otpStoreAction';
import { OtpStoreMutation } from './otpStoreMutation';

const phoneNumber = fakerjs.phone.phoneNumber();

jest.spyOn(authApi, 'reportWrongPhoneNumber').mockImplementation(() => Promise.resolve());
jest.spyOn(authApi, 'requestOtpCode').mockImplementation(() => Promise.resolve());
jest.spyOn(authApi, 'requestOtpInfo').mockImplementation(() =>
    Promise.resolve({
        phoneNumber,
    })
);
jest.spyOn(authApi, 'verifyOtpCode').mockImplementation(() => Promise.resolve());

describe('otpStore', () => {
    const getStore = (state: Partial<State> = {}) => {
        const localVue = createLocalVue();
        localVue.use(Vuex);

        return new Vuex.Store<RootStoreState>({
            modules: {
                [StoreType.OTP]: {
                    ...otpStore,
                    state: {
                        ...otpStore.state,
                        ...state,
                    },
                },
            },
        });
    };

    describe(`Action: ${OtpStoreAction.GET_CODE}`, () => {
        it('should request OTP Code and set componentState', async () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];
            const spyCommit = jest.spyOn(store, 'commit');

            expect(state.componentState).toEqual(ComponentState.Loading);
            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.GET_CODE}`, OTPType.VALUE_sms);

            // Check commit and API call
            expect(authApi.requestOtpCode).toHaveBeenCalledWith(OTPType.VALUE_sms);
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.OTP}/${OtpStoreMutation.SET_STATE}`,
                ComponentState.OtpVerification,
                undefined
            );

            // Check state
            expect(state.componentState).toEqual(ComponentState.OtpVerification);
        });

        it(`should set error=${Error.VALUE_otp_request_failed} on exception`, async () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];

            const spyCommit = jest.spyOn(store, 'commit');
            jest.spyOn(authApi, 'requestOtpCode').mockImplementation(() => Promise.reject());

            expect(state.componentState).toEqual(ComponentState.Loading);
            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.GET_CODE}`, OTPType.VALUE_sms);

            // Check commit and API call
            expect(authApi.requestOtpCode).toHaveBeenCalledWith(OTPType.VALUE_sms);
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.OTP}/${OtpStoreMutation.SET_ERROR}`,
                Error.VALUE_otp_request_failed,
                undefined
            );

            // Check state
            expect(state.componentState).toEqual(ComponentState.Loading);
            expect(state.error).toEqual(Error.VALUE_otp_request_failed);
        });
    });

    describe(`Action: ${OtpStoreAction.GET_INFO}`, () => {
        it('should request OTP Info and set phoneNumber', async () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];
            const spyCommit = jest.spyOn(store, 'commit');

            expect(state.componentState).toEqual(ComponentState.Loading);
            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.GET_INFO}`);

            // Check commit and API call
            expect(authApi.requestOtpInfo).toHaveBeenCalledWith();
            expect(spyCommit).toHaveBeenNthCalledWith(
                1,
                `${StoreType.OTP}/${OtpStoreMutation.SET_INFO}`,
                phoneNumber,
                undefined
            );
            expect(spyCommit).toHaveBeenNthCalledWith(
                2,
                `${StoreType.OTP}/${OtpStoreMutation.SET_STATE}`,
                ComponentState.OtpRequest,
                undefined
            );

            // Check state
            expect(state.componentState).toEqual(ComponentState.OtpRequest);
        });

        it(`should set error=${Error.VALUE_otp_info_retrieval_failed} on exception`, async () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];

            const spyCommit = jest.spyOn(store, 'commit');
            jest.spyOn(authApi, 'requestOtpInfo').mockImplementation(() => Promise.reject());

            expect(state.componentState).toEqual(ComponentState.Loading);
            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.GET_INFO}`);

            // Check commit and API call
            expect(authApi.requestOtpInfo).toHaveBeenCalledWith();
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.OTP}/${OtpStoreMutation.SET_ERROR}`,
                Error.VALUE_otp_info_retrieval_failed,
                undefined
            );

            // Check state
            expect(state.componentState).toEqual(ComponentState.Loading);
            expect(state.error).toEqual(Error.VALUE_otp_info_retrieval_failed);
        });
    });

    describe(`Action: ${OtpStoreAction.REPORT_PHONE_NUMBER}`, () => {
        it('should report phone number and set reportedPhoneNumber=true', async () => {
            const store = getStore({ phoneNumber });
            const state = store.state[StoreType.OTP];
            const spyCommit = jest.spyOn(store, 'commit');

            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.REPORT_PHONE_NUMBER}`);

            // Check commit and API call
            expect(authApi.reportWrongPhoneNumber).toHaveBeenCalledWith(phoneNumber);
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.OTP}/${OtpStoreMutation.SET_REPORTED_PHONE_NUMBER}`,
                true,
                undefined
            );

            // Check state
            expect(state.reportedPhoneNumber).toEqual(true);
        });

        it(`should set error=${Error.VALUE_otp_report_phone_number_failed} on exception`, async () => {
            const store = getStore({ phoneNumber });
            const state = store.state[StoreType.OTP];

            const spyCommit = jest.spyOn(store, 'commit');
            jest.spyOn(authApi, 'reportWrongPhoneNumber').mockImplementation(() => Promise.reject());

            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.REPORT_PHONE_NUMBER}`);

            // Check commit and API call
            expect(authApi.reportWrongPhoneNumber).toHaveBeenCalledWith(phoneNumber);
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.OTP}/${OtpStoreMutation.SET_ERROR}`,
                Error.VALUE_otp_report_phone_number_failed,
                undefined
            );

            // Check state
            expect(state.reportedPhoneNumber).toEqual(false);
            expect(state.error).toEqual(Error.VALUE_otp_report_phone_number_failed);
        });
    });

    describe(`Action: ${OtpStoreAction.RESET_STATE}`, () => {
        it('should reset state to the default state', async () => {
            const store = getStore({
                componentState: ComponentState.OtpVerification,
                error: Error.VALUE_otp_request_failed,
                phoneNumber,
                reportedPhoneNumber: true,
                isRetrying: true,
                retryCount: 3,
            });
            const state = store.state[StoreType.OTP];
            const spyCommit = jest.spyOn(store, 'commit');

            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.RESET_STATE}`);

            // Check commit
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.OTP}/${OtpStoreMutation.RESET_STATE}`,
                undefined,
                undefined
            );

            // Check state
            expect(state).toEqual(getDefaultState());
        });
    });

    describe(`Action: ${OtpStoreAction.RETRY_GET_CODE}`, () => {
        it('should execute GET_CODE action and set isRetrying=true', async () => {
            const store = getStore();
            const spyCommit = jest.spyOn(store, 'commit');
            const spyDispatch = jest.spyOn(store, 'dispatch');

            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.RETRY_GET_CODE}`, OTPType.VALUE_sms);

            expect(spyCommit).toHaveBeenNthCalledWith(
                1,
                `${StoreType.OTP}/${OtpStoreMutation.SET_IS_RETRYING}`,
                true,
                undefined
            );
            expect(spyDispatch).toHaveBeenNthCalledWith(
                2,
                `${StoreType.OTP}/${OtpStoreAction.GET_CODE}`,
                OTPType.VALUE_sms
            );
        });

        it('should reset isRetrying=true to false after 10 seconds', async () => {
            // Set fixed time
            useFakeTimers(new Date('2022-01-01 12:00:00'));

            const store = getStore();
            const spyCommit = jest.spyOn(store, 'commit');

            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.RETRY_GET_CODE}`, OTPType.VALUE_sms);

            // Advance timers by 10 sec
            jest.advanceTimersByTime(10 * 1000);

            expect(spyCommit).toHaveBeenLastCalledWith(
                `${StoreType.OTP}/${OtpStoreMutation.SET_IS_RETRYING}`,
                false,
                undefined
            );
        });

        it('should not execute if state.isRetrying=true', async () => {
            const store = getStore({ isRetrying: true });
            const spyCommit = jest.spyOn(store, 'commit');
            const spyDispatch = jest.spyOn(store, 'dispatch');

            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.RETRY_GET_CODE}`);

            expect(spyCommit).not.toHaveBeenCalled();
            expect(spyDispatch).toHaveBeenCalledTimes(1);
        });
    });

    describe(`Action: ${OtpStoreAction.VERIFY_CODE}`, () => {
        it('should verify given code and return true', async () => {
            const store = getStore();
            const payload: VerifyCode = {
                otpType: OTPType.VALUE_sms,
                code: '1234',
            };

            const result = await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.VERIFY_CODE}`, payload);

            // Check commit and API call
            expect(authApi.verifyOtpCode).toHaveBeenCalledWith(payload.otpType, payload.code);
            expect(result).toBe(true);
        });

        it(`should set error=${Error.VALUE_otp_verification_failed} on exception`, async () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];
            const payload: VerifyCode = {
                otpType: OTPType.VALUE_sms,
                code: '1234',
            };

            const spyCommit = jest.spyOn(store, 'commit');
            jest.spyOn(authApi, 'verifyOtpCode').mockImplementation(() => Promise.reject());

            await store.dispatch(`${StoreType.OTP}/${OtpStoreAction.VERIFY_CODE}`, payload);

            // Check commit and API call
            expect(authApi.verifyOtpCode).toHaveBeenCalledWith(payload.otpType, payload.code);
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.OTP}/${OtpStoreMutation.SET_ERROR}`,
                Error.VALUE_otp_verification_failed,
                undefined
            );

            // Check state
            expect(state.error).toEqual(Error.VALUE_otp_verification_failed);
        });
    });

    describe(`Mutation: ${OtpStoreMutation.RESET_STATE}`, () => {
        it('should reset state to the default state', () => {
            const store = getStore({
                componentState: ComponentState.OtpVerification,
                error: Error.VALUE_otp_request_failed,
                phoneNumber,
                reportedPhoneNumber: true,
                isRetrying: true,
                retryCount: 3,
            });
            const state = store.state[StoreType.OTP];

            store.commit(`${StoreType.OTP}/${OtpStoreMutation.RESET_STATE}`);

            expect(state).toEqual(getDefaultState());
        });
    });

    describe(`Mutation: ${OtpStoreMutation.SET_ERROR}`, () => {
        it('should set error to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];

            expect(state.error).toBeNull();
            store.commit(`${StoreType.OTP}/${OtpStoreMutation.SET_ERROR}`, Error.VALUE_otp_info_retrieval_failed);

            expect(state.error).toEqual(Error.VALUE_otp_info_retrieval_failed);
        });
    });

    describe(`Mutation: ${OtpStoreMutation.SET_INFO}`, () => {
        it('should set phoneNumber to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];

            expect(state.phoneNumber).toBe('');
            store.commit(`${StoreType.OTP}/${OtpStoreMutation.SET_INFO}`, '06 12121212');

            expect(state.phoneNumber).toEqual('06 12121212');
        });
    });

    describe(`Mutation: ${OtpStoreMutation.SET_IS_RETRYING}`, () => {
        it('should set isRetrying to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];

            expect(state.isRetrying).toBe(false);
            store.commit(`${StoreType.OTP}/${OtpStoreMutation.SET_IS_RETRYING}`, true);

            expect(state.isRetrying).toBe(true);
        });

        it('should update retryCount with +1 if isRetrying=true', () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];

            expect(state.retryCount).toBe(0);
            store.commit(`${StoreType.OTP}/${OtpStoreMutation.SET_IS_RETRYING}`, true);

            expect(state.retryCount).toBe(1);
        });
    });

    describe(`Mutation: ${OtpStoreMutation.SET_REPORTED_PHONE_NUMBER}`, () => {
        it('should set reportedPhoneNumber to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];

            expect(state.reportedPhoneNumber).toBe(false);
            store.commit(`${StoreType.OTP}/${OtpStoreMutation.SET_REPORTED_PHONE_NUMBER}`, true);

            expect(state.reportedPhoneNumber).toBe(true);
        });
    });

    describe(`Mutation: ${OtpStoreMutation.SET_STATE}`, () => {
        it('should set componentState to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.OTP];

            expect(state.componentState).toBe(ComponentState.Loading);
            store.commit(`${StoreType.OTP}/${OtpStoreMutation.SET_STATE}`, ComponentState.OtpRequest);

            expect(state.componentState).toBe(ComponentState.OtpRequest);
        });
    });
});
