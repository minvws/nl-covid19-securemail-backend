<template>
    <div>
        <div class="text-center mb-4">
            <SvgVue icon="message_logo" width="104px" focusable="false" aria-hidden="true" />
        </div>
        <FocusedHeader class="mt-3 mb-2">
            {{ $t('pages.loginPage.title', { name }) }}
        </FocusedHeader>
        <Paragraphs :data="Object.values($t('pages.loginPage.paragraphs'))" />
        <template v-for="option in loginOptions">
            <DigidLoginButton
                v-if="option === LoginType.VALUE_digid"
                class="mt-4 mb-3"
                loginUrl="/auth/digid"
                data-testid="button-digid"
            />
            <BButton
                v-if="option === LoginType.VALUE_sms"
                @click="goTo2faPage(option)"
                class="px-0"
                variant="link"
                data-testid="button-sms"
            >
                {{ $t(`pages.loginPage.buttons.${option}`) }}
                <SvgVue class="mr-1" icon="arrow-right" width="20px" focusable="false" aria-hidden="true" />
            </BButton>
        </template>
    </div>
</template>
<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import DigidLoginButton from '@/app/components/DigidLoginButton/DigidLoginButton.vue';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import Paragraphs from '@/app/components/Paragraphs/Paragraphs.vue';
import { LoginType } from '@/types/enums/loginType';
import { OTPType, OTPTypeOptions } from '@/types/enums/OTPType';
import { mapActions, mapState } from 'vuex';
import { StoreType } from '@/store/storeType';
import { SessionStoreAction } from '@/store/sessionStore';

interface Props {}

interface Data {
    LoginType: typeof LoginType;
}

interface Computed {
    name: string;
    loginOptions: LoginType[];
}

interface Methods {
    getLoginOptions: () => Promise<void>;
    clearDigidResponse: () => Promise<void>;
    goTo2faPage: (option: OTPType) => Promise<void>;
}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { DigidLoginButton, FocusedHeader, Paragraphs },
    name: 'LoginPage',
    data() {
        return {
            LoginType,
        };
    },
    computed: {
        ...mapState(StoreType.SESSION, ['loginOptions', 'name']),
    },
    async created() {
        try {
            await this.getLoginOptions();

            if (this.loginOptions.length === 0) {
                this.$router.replace({ name: 'home' });
            } else if (this.loginOptions.length === 1 && Object.keys(OTPTypeOptions).includes(this.loginOptions[0])) {
                await this.clearDigidResponse();
                this.$router.replace({ name: 'auth.2fa', params: { otpType: this.loginOptions[0] } });
            }
        } catch (error) {
            this.$router.push({ name: 'error' });
        }
    },
    methods: {
        ...mapActions(StoreType.SESSION, {
            getLoginOptions: SessionStoreAction.GET_LOGIN_OPTIONS,
            clearDigidResponse: SessionStoreAction.CLEAR_DIGID_RESPONSE,
        }),
        async goTo2faPage(option: OTPType) {
            await this.clearDigidResponse();
            this.$router.push({ name: 'auth.2fa', params: { otpType: option } });
        },
    },
});
</script>
>
