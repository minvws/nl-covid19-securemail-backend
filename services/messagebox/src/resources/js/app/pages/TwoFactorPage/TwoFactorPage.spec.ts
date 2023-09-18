import { authApi } from '@/api';
import { ComponentState } from '@/store/otpStore';
import otpStore from '@/store/otpStore/otpStore';
import { StoreType } from '@/store/storeType';
import { Error } from '@/types/enums/error';
import { OTPType } from '@/types/enums/OTPType';
import { fakerjs, flushCallStack, setupTest } from '@/utils/testUtils';
import '@/vee-validate';
import { mount } from '@vue/test-utils';
import BootstrapVue from 'bootstrap-vue';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';
import TwoFactorPage from './TwoFactorPage.vue';

const verificationCode = fakerjs.datatype.number({ min: 100000, max: 999999 });

const spyRequestOtpInfo = jest.spyOn(authApi, 'requestOtpInfo').mockImplementation(() =>
    Promise.resolve({
        phoneNumber: fakerjs.phone.phoneNumber(),
    })
);
const spyRequestOtpCode = jest.spyOn(authApi, 'requestOtpCode').mockImplementation(() => Promise.resolve());
const spyVerifyOtpCode = jest.spyOn(authApi, 'verifyOtpCode').mockImplementation(() => Promise.resolve());
const spyReportWrongPhoneNumber = jest
    .spyOn(authApi, 'reportWrongPhoneNumber')
    .mockImplementation(() => Promise.resolve());

Object.defineProperty(window, 'location', {
    value: {
        href: fakerjs.internet.url(),
    },
});

const defaultMockRoute = {
    name: 'auth.2fa',
    params: {},
};

const mockRouter = {
    push: jest.fn(),
    replace: jest.fn(),
    resolve: jest.fn(() => {
        return {
            href: 'https://localhost/inbox',
        };
    }),
};

const createComponent = setupTest(
    async (localVue: VueConstructor) => {
        const wrapper = mount(TwoFactorPage, {
            localVue,
            directives: {
                mask: jest.fn(),
            },
            propsData: {
                otpType: OTPType.VALUE_sms,
            },
            stubs: {
                SvgVue: true,
            },
            mocks: {
                mask: () => null,
                $t: (msg: string) => msg,
                $route: defaultMockRoute,
                $router: mockRouter,
            },
            store: new Vuex.Store({
                modules: {
                    [StoreType.OTP]: otpStore,
                },
            }),
            attachTo: document.body,
        });

        await flushCallStack();

        return wrapper;
    },
    [BootstrapVue, Vuex]
);

describe('TwoFactorPage.vue', () => {
    it('should load', async () => {
        const wrapper = await createComponent();
        expect(wrapper.find('div').exists()).toBe(true);
    });

    it('should show spinner if loading', async () => {
        spyRequestOtpInfo.mockImplementationOnce(() => new Promise(resolve => setTimeout(resolve, 100)));

        const wrapper = await createComponent();
        expect(wrapper.findComponent({ name: 'BSpinner' }).exists()).toBe(true);
    });

    it('should change component state from loading to info', async () => {
        const wrapper = await createComponent();

        expect(spyRequestOtpInfo).toHaveBeenCalledTimes(1);
        expect(wrapper.vm.$store.state.otp.componentState).toBe(ComponentState.OtpRequest);
    });

    it('should show error', async () => {
        spyRequestOtpInfo.mockImplementationOnce(() => Promise.reject());
        const wrapper = await createComponent();

        expect(spyRequestOtpInfo).toHaveBeenCalledTimes(1);
        expect(wrapper.vm.$store.state.otp.error).toBe(Error.VALUE_otp_info_retrieval_failed);

        const error = wrapper.find('[data-testid="error-loading-message"]');
        expect(error.exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'BSpinner' }).exists()).toBe(false);
    });

    it('should request code but fails', async () => {
        spyRequestOtpCode.mockImplementationOnce(() => Promise.reject());
        const wrapper = await createComponent();

        const requestButton = wrapper.find('[data-testid="button-otp-request"]');
        await requestButton.trigger('click');

        expect(spyRequestOtpCode).toHaveBeenNthCalledWith(1, OTPType.VALUE_sms);
        expect(wrapper.vm.$store.state.otp.error).toBe(Error.VALUE_otp_request_failed);
    });

    describe('State: Has requested OTP Code', () => {
        let wrapper: Awaited<ReturnType<typeof createComponent>>;

        beforeEach(async () => {
            wrapper = await createComponent();

            const requestButton = wrapper.find('[data-testid="button-otp-request"]');
            await requestButton.trigger('click');
        });

        it('should request code', async () => {
            expect(spyRequestOtpCode).toHaveBeenNthCalledWith(1, OTPType.VALUE_sms);
            expect(wrapper.vm.$store.state.otp.componentState).toBe(ComponentState.OtpVerification);
        });

        it('should request code again and retry when clicking retry button', async () => {
            const retryButton = wrapper.find('[data-testid="button-retry"]');
            await retryButton.trigger('click');

            expect(wrapper.vm.$store.state.otp.isRetrying).toBe(true);
            expect(wrapper.vm.$store.state.otp.retryCount).toBe(1);
        });

        it('should NOT show retry alert if not retried yet', async () => {
            expect(wrapper.find('[data-testid="alert-retry-message"]').exists()).toBe(false);
        });

        it('should show retry alert if retried (retry count > 0)', async () => {
            const retryButton = wrapper.find('[data-testid="button-retry"]');
            await retryButton.trigger('click');

            expect(wrapper.find('[data-testid="alert-retry-message"]').exists()).toBe(true);
        });

        it('should validate code', async () => {
            const verifyButton = wrapper.find('[data-testid="button-verify"]');
            await verifyButton.trigger('submit');
            await flushCallStack();

            const codeInput = wrapper.find('[data-testid="input-code"]');
            expect(codeInput.classes()).toContain('is-invalid');

            codeInput.setValue(verificationCode);
            codeInput.trigger('blur');
            await flushCallStack();

            expect(codeInput.classes()).toContain('is-valid');
            expect(codeInput.props().state).toBe(true);
        });

        it('should focus on invalid field after submit (otp request)', async () => {
            const verifyButton = wrapper.find('[data-testid="button-verify"]');
            await verifyButton.trigger('submit');
            await flushCallStack();

            const codeInput = wrapper.find('[data-testid="input-code"]');
            expect(document.activeElement).toBe(codeInput.element);
        });

        it('should invalidate code', async () => {
            const emptyCode = '';

            const codeInput = wrapper.find('[data-testid="input-code"]');
            codeInput.setValue(emptyCode);
            codeInput.trigger('blur');
            await flushCallStack();

            expect(codeInput.classes()).toContain('is-invalid');
            expect(codeInput.props().state).toBe(false);
        });

        it('should verify filled in code', async () => {
            wrapper.vm.$set(wrapper.vm.$data, 'code', verificationCode);
            await wrapper.vm.$nextTick();

            const verifyButton = wrapper.find('[data-testid="button-verify"]');
            await verifyButton.trigger('submit');
            await flushCallStack();

            expect(spyVerifyOtpCode).toHaveBeenNthCalledWith(1, OTPType.VALUE_sms, verificationCode);
        });

        it('should NOT verify filled in code if wrong', async () => {
            spyVerifyOtpCode.mockImplementationOnce(() => Promise.reject());

            wrapper.vm.$set(wrapper.vm.$data, 'code', verificationCode);
            await wrapper.vm.$nextTick();

            const verifyButton = wrapper.find('[data-testid="button-verify"]');
            await verifyButton.trigger('submit');
            await flushCallStack();

            expect(spyVerifyOtpCode).toHaveBeenNthCalledWith(1, OTPType.VALUE_sms, verificationCode);
            expect(wrapper.vm.$store.state.otp.error).toBe(Error.VALUE_otp_verification_failed);
        });

        it('should report wrong phone number', async () => {
            const requestButton = wrapper.find('[data-testid="button-report-phone-number"]');
            await requestButton.trigger('click');
            await flushCallStack();

            expect(spyReportWrongPhoneNumber).toHaveBeenCalledTimes(1);
        });
    });
});
