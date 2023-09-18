<template>
    <div>
        <div class="text-center mb-4">
            <SvgVue icon="message_logo" width="104px" focusable="false" aria-hidden="true" />
        </div>

        <FocusedHeader class="mt-3 mb-2" ref="headingTitle">{{ headingTitle }}</FocusedHeader>
        <div v-if="componentState === ComponentState.Loading">
            <BSpinner v-if="!error" variant="primary" />
            <Alert
                v-else
                class="mt-4"
                infoType="error"
                showIcon
                :title="errorObject.title"
                data-testid="error-loading-message"
            >
                <p>{{ errorObject.message }}</p>
            </Alert>
        </div>
        <div v-else>
            <template v-if="componentState === ComponentState.OtpRequest">
                <Paragraphs
                    :data="Object.values($t('pages.twoFactorPage.state.otpRequest.paragraphs'))"
                    :params="{ phoneNumber }"
                />

                <Alert
                    v-if="error"
                    class="my-4"
                    infoType="error"
                    showIcon
                    :title="errorObject.title"
                    role="alert"
                    aria-live="assertive"
                    data-testid="error-request-message"
                >
                    <p>{{ errorObject.message }}</p>
                </Alert>

                <BButton
                    @click="otpRequest"
                    type="submit"
                    variant="primary"
                    class="mt-2"
                    block
                    data-testid="button-otp-request"
                >
                    {{ $t('pages.twoFactorPage.state.otpRequest.button') }}
                    <SvgVue icon="arrow-right" width="24px" class="ml-2" aria-hidden="true" focusable="false" />
                </BButton>
            </template>
            <template v-else-if="componentState === ComponentState.OtpVerification">
                <Paragraphs
                    :data="Object.values($t('pages.twoFactorPage.state.otpVerification.paragraphs'))"
                    :params="{ phoneNumber }"
                />

                <Alert
                    v-if="error"
                    class="my-4"
                    infoType="error"
                    showIcon
                    :title="errorObject.title"
                    role="alert"
                    aria-live="assertive"
                >
                    <p>{{ errorObject.message }}</p>
                </Alert>

                <ValidationObserver ref="observerRef">
                    <BForm @submit.stop.prevent="validateForm">
                        <ValidationProvider
                            :name="i18nOtpType"
                            rules="required|digits:6"
                            v-slot="validationContext"
                            v-mask="'######'"
                            mode="lazy"
                            data-testid="provider"
                        >
                            <BFormGroup :label="i18nOtpType" label-for="code">
                                <BFormInput
                                    id="code"
                                    v-model="code"
                                    :state="getValidationState(validationContext)"
                                    aria-describedby="code-live-feedback"
                                    autocomplete="one-time-code"
                                    data-testid="input-code"
                                />
                                <BFormInvalidFeedback
                                    v-if="validationContext.errors[0]"
                                    id="code-live-feedback"
                                    role="alert"
                                >
                                    {{ validationContext.errors[0] }}
                                </BFormInvalidFeedback>
                            </BFormGroup>
                        </ValidationProvider>

                        <BButton type="submit" variant="primary" class="mt-2" block data-testid="button-verify">
                            {{ $t('pages.twoFactorPage.state.otpVerification.button') }}
                            <SvgVue icon="arrow-right" width="24px" class="ml-2" aria-hidden="true" focusable="false" />
                        </BButton>
                    </BForm>
                </ValidationObserver>

                <div class="mt-3" role="status">
                    <div v-if="retryCount > 0" role="status">
                        <div class="text-center font-weight-bold">
                            <SvgVue
                                icon="checkmark"
                                width="14px"
                                class="mr-2"
                                aria-hidden="true"
                                focusable="false"
                                data-testid="icon-retry-checkmark"
                            />
                            {{ $t('pages.twoFactorPage.retry.ctaResponse') }}
                        </div>
                        <Alert
                            infoType="info"
                            showIcon
                            :title="$t('pages.twoFactorPage.retry.infoTitle')"
                            class="mt-2"
                            data-testid="alert-retry-message"
                        >
                            <p>{{ $t('pages.twoFactorPage.retry.infoText') }}</p>
                        </Alert>
                    </div>

                    <BButton
                        @click="retry"
                        block
                        class="justify-content-center text-primary"
                        :disabled="isRetrying"
                        variant="link"
                        data-testid="button-retry"
                    >
                        {{ $t('pages.twoFactorPage.retry.ctaButton') }}
                    </BButton>
                </div>
            </template>

            <Details class="mt-3" :title="$t('pages.twoFactorPage.wrongPhoneNumber.infoButton')">
                <Alert class="mt-1">
                    <p>{{ $t('pages.twoFactorPage.wrongPhoneNumber.infoText') }}</p>
                    <BButton
                        @click="reportWrongPhoneNumber"
                        block
                        class="mt-3 justify-content-center"
                        :class="{ 'btn-link--light': reportedPhoneNumber }"
                        :disabled="reportedPhoneNumber"
                        variant="link"
                        data-testid="button-report-phone-number"
                    >
                        <template v-if="reportedPhoneNumber">
                            <SvgVue icon="checkmark" width="14px" class="mr-2" aria-hidden="true" focusable="false" />
                            {{ $t('pages.twoFactorPage.wrongPhoneNumber.ctaResponse') }}
                        </template>
                        <template v-else> {{ $t('pages.twoFactorPage.wrongPhoneNumber.ctaButton') }}</template>
                    </BButton>
                </Alert>
            </Details>
        </div>
    </div>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import { ValidationProvider, ValidationObserver } from 'vee-validate';
import { ValidationFlags } from 'vee-validate/dist/types/types';
import Navbar from '@/app/components/Navbar/Navbar.vue';
import Footer from '@/app/components/Footer/Footer.vue';
import Alert from '@/app/components/Alert/Alert.vue';
import Details from '@/app/components/Details/Details.vue';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import Paragraphs from '@/app/components/Paragraphs/Paragraphs.vue';
import { OTPType, OTPTypeOptions } from '@/types/enums/OTPType';
import Vue, { PropType } from 'vue';
import { mapActions, mapState } from 'vuex';
import { getTranslatedOptions } from '@/utils/i18n';
import { StoreType } from '@/store/storeType';
import { OtpStoreAction, ComponentState } from '@/store/otpStore';
import { VerifyCode } from '@/store/otpStore/otpStore';

interface Error {
    title: string;
    message: string;
}

interface Props {
    otpType: OTPType;
}

interface Data {
    ComponentState: typeof ComponentState;
    code: string;
}

interface Computed {
    componentState: ComponentState;
    error: string | null;
    phoneNumber: string | null;
    reportedPhoneNumber: boolean;
    isRetrying: boolean;
    retryCount: number;

    i18nOtpType: string;
    headingTitle: string;
    errorObject: Error | null;
}

interface Methods {
    resetState: () => Promise<void>;
    getInfo: () => Promise<void>;
    getCode: (option: OTPType) => Promise<void>;
    verifyCode: (data: VerifyCode) => Promise<boolean>;
    retryCode: (option: OTPType) => Promise<void>;
    reportPhoneNumber: () => Promise<void>;

    getValidationState: (validationContext: ValidationFlags) => boolean | null;
    otpRequest: () => Promise<void>;
    otpVerify: () => Promise<void>;
    reportWrongPhoneNumber: () => Promise<void>;
    retry: () => Promise<void>;
    validateForm: () => Promise<void>;
}

interface AdditionalProps {
    $refs: {
        headingTitle: InstanceType<typeof FocusedHeader>;
        observerRef: InstanceType<typeof ValidationObserver>;
    };
}

export default defineComponent<Data, Methods, Computed, Props, AdditionalProps>({
    components: {
        Alert,
        Details,
        Footer,
        Navbar,
        FocusedHeader,
        Paragraphs,
        ValidationObserver,
        ValidationProvider,
    },
    name: 'TwoFactorPage',
    props: {
        otpType: {
            type: String as PropType<OTPType>,
            default: OTPType.VALUE_sms,
        },
    },
    data() {
        return {
            ComponentState,
            code: '',
        };
    },
    async created() {
        await this.resetState();
        await this.getInfo();
    },
    computed: {
        ...mapState(StoreType.OTP, [
            'componentState',
            'error',
            'phoneNumber',
            'reportedPhoneNumber',
            'isRetrying',
            'retryCount',
        ]),
        errorObject() {
            if (!this.error) return null;
            return {
                title: this.$t(`errors.${this.error}.title`).toString(),
                message: this.$t(`errors.${this.error}.message`).toString(),
            };
        },
        i18nOtpType() {
            return (
                getTranslatedOptions('OTPType', OTPTypeOptions).find(option => option.value === this.otpType)?.label ??
                this.otpType
            );
        },
        headingTitle() {
            const titles: Record<string, string> = {
                [ComponentState.Loading]: 'pages.twoFactorPage.state.loading.title',
                [ComponentState.OtpRequest]: 'pages.twoFactorPage.state.otpRequest.title',
                [ComponentState.OtpVerification]: 'pages.twoFactorPage.state.otpVerification.title',
            };

            return this.$t(titles[this.componentState], {
                otpType: this.i18nOtpType,
            }).toString();
        },
    },
    methods: {
        ...mapActions(StoreType.OTP, {
            resetState: OtpStoreAction.RESET_STATE,
            getInfo: OtpStoreAction.GET_INFO,
            getCode: OtpStoreAction.GET_CODE,
            verifyCode: OtpStoreAction.VERIFY_CODE,
            retryCode: OtpStoreAction.RETRY_GET_CODE,
            reportPhoneNumber: OtpStoreAction.REPORT_PHONE_NUMBER,
        }),
        getValidationState({ dirty, validated, valid }) {
            return dirty || validated ? valid : null;
        },
        async otpRequest() {
            await this.getCode(this.otpType);
            this.$refs.headingTitle.focus();
        },
        async otpVerify() {
            const success = await this.verifyCode({ otpType: this.otpType, code: this.code });
            if (success) {
                window.location.href = this.$router.resolve({ name: 'inbox' }).href;
            }
        },
        async reportWrongPhoneNumber() {
            this.reportPhoneNumber();
        },
        async retry() {
            await this.retryCode(this.otpType);
        },
        async validateForm() {
            const isValid = await this.$refs.observerRef.validate();
            if (!isValid) {
                this.$refs.observerRef.$el.querySelector<HTMLInputElement>('.form-control.is-invalid')?.focus();
                return;
            }

            await this.otpVerify();
        },
    },
});
</script>

<style lang="scss" scoped></style>
