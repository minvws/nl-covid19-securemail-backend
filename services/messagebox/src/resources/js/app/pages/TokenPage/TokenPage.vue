<template>
    <div>
        <div class="text-center mb-4">
            <SvgVue icon="message_logo" width="104px" focusable="false" aria-hidden="true" />
        </div>
        <FocusedHeader class="mt-3 mb-2">{{ $t('pages.tokenPage.title') }}</FocusedHeader>
        <Paragraphs :data="Object.values($t('pages.tokenPage.paragraphs'))" />
        <ValidationObserver ref="observerRef">
            <BForm @submit.stop.prevent="validate">
                <ValidationProvider
                    :name="$t('pages.tokenPage.email.label')"
                    rules="required|email"
                    v-slot="validationContext"
                >
                    <BFormGroup
                        :label="$t('pages.tokenPage.email.label')"
                        label-for="email"
                        :description="$t('pages.tokenPage.email.description')"
                    >
                        <BFormInput
                            id="email"
                            v-model="email"
                            type="email"
                            autocomplete="email"
                            :state="getValidationState(validationContext)"
                            aria-describedby="email-live-feedback"
                            data-testid="input-email"
                        />
                        <BFormInvalidFeedback v-if="validationContext.errors[0]" id="email-live-feedback" role="alert">
                            {{ validationContext.errors[0] }}
                        </BFormInvalidFeedback>
                    </BFormGroup>
                </ValidationProvider>
                <ValidationProvider
                    :name="$t('pages.tokenPage.pairingCode.label')"
                    rules="required|min:6|max:7"
                    v-slot="validationContext"
                >
                    <BFormGroup
                        :label="$t('pages.tokenPage.pairingCode.label')"
                        label-for="pairingCode"
                        :description="$t('pages.tokenPage.pairingCode.description')"
                    >
                        <BFormInput
                            id="pairingCode"
                            v-model="pairingCode"
                            type="text"
                            :state="getValidationState(validationContext)"
                            aria-describedby="pairingCode-live-feedback"
                            data-testid="input-code"
                        />
                        <BFormInvalidFeedback
                            v-if="validationContext.errors[0]"
                            id="pairingCode-live-feedback"
                            role="alert"
                        >
                            {{ validationContext.errors[0] }}
                        </BFormInvalidFeedback>
                    </BFormGroup>
                </ValidationProvider>
                <BButton type="submit" variant="primary" class="mt-2" block data-testid="button-submit">
                    {{ $t('pages.tokenPage.next') }}
                    <SvgVue icon="arrow-right" width="24px" class="ml-2" aria-hidden="true" focusable="false" />
                </BButton>
            </BForm>
        </ValidationObserver>
    </div>
</template>
<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import { ValidationProvider, ValidationObserver } from 'vee-validate';
import { ValidationFlags } from 'vee-validate/dist/types/types';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import Paragraphs from '@/app/components/Paragraphs/Paragraphs.vue';
import { StoreType } from '@/store/storeType';
import { SessionStoreAction } from '@/store/sessionStore';
import { LoginByCode } from '@/store/sessionStore/sessionStore';
import { mapActions } from 'vuex';

interface Props {}

interface Data {
    email: string;
    pairingCode: string;
}

interface Computed {}

interface Methods {
    loginByCode: (data: LoginByCode) => Promise<true | Error>;
    getValidationState: (validationContext: ValidationFlags) => boolean | null;
    submit: () => Promise<void>;
    validate: () => Promise<void>;
}

interface AdditionalProps {
    $refs: {
        observerRef: InstanceType<typeof ValidationObserver>;
    };
}

export default defineComponent<Data, Methods, Computed, Props, AdditionalProps>({
    components: { FocusedHeader, Paragraphs, ValidationObserver, ValidationProvider },
    name: 'TokenPage',
    data() {
        return {
            email: '',
            pairingCode: '',
        };
    },
    methods: {
        ...mapActions(StoreType.SESSION, {
            loginByCode: SessionStoreAction.LOGIN_BY_CODE,
        }),
        getValidationState({ dirty, validated, valid }) {
            return dirty || validated ? valid : null;
        },
        async submit() {
            const result = await this.loginByCode({ email: this.email, pairingCode: this.pairingCode });
            if (result === true) {
                this.$router.push({ name: 'auth.login' });
                return;
            }

            this.$router.push({ name: 'error', params: { code: result.toString() } });
        },
        async validate() {
            const isValid = await this.$refs.observerRef.validate();
            if (!isValid) {
                this.$refs.observerRef.$el.querySelector<HTMLInputElement>('.form-control.is-invalid')?.focus();
                return;
            }

            await this.submit();
        },
    },
});
</script>

<style lang="scss" scoped></style>
