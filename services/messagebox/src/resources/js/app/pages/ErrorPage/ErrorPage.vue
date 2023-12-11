<template>
    <div>
        <div class="text-center mb-4">
            <SvgVue :icon="error.logo" width="104px" focusable="false" aria-hidden="true" />
        </div>

        <Alert :title="error.title" :infoType="error.type" showIcon>
            <p v-html="error.message" />
        </Alert>

        <BButton
            v-if="(!isConfirmed && error.confirmation) || !error.confirmation"
            block
            class="mt-4"
            variant="primary"
            @click="error.callback"
        >
            {{ error.button }}
        </BButton>

        <div v-else class="mt-4 text-center">
            <SvgVue
                icon="checkmark"
                width="14px"
                class="mr-2"
                aria-hidden="true"
                focusable="false"
                data-testid="icon-confirmation-checkmark"
            />
            {{ error.confirmation }}
        </div>
    </div>
</template>
<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import Alert from '@/app/components/Alert/Alert.vue';
import { Error } from '@/types/enums/error';
import { PropType } from 'vue';
import { StoreType } from '@/store/storeType';
import { SessionStoreAction } from '@/store/sessionStore';
import { mapActions, mapState } from 'vuex';
import { State as SessionState } from '@/store/sessionStore/sessionStore';

enum LogoType {
    Default = 'message_logo',
    Locked = 'message_locked',
}

enum ErrorType {
    Error = 'error',
    Info = 'info',
}

type ErrorDefinition = {
    callback?: Function;
    logo?: LogoType;
    type?: ErrorType;
};

interface Props {
    code: Error;
}

interface Data {
    defaultCallback: Function;
    isConfirmed: Boolean;
}

interface Computed {
    sessionMessageUuid: SessionState['sessionMessageUuid'];
    pairingCodeResponse: SessionState['pairingCodeResponse'];
    digidResponse: SessionState['digidResponse'];
    errorDefinitions: Record<string, ErrorDefinition>;
    error: {
        title: string;
        message: string;
        button?: string;
    } & ErrorDefinition;
}

interface Methods {
    logout: () => Promise<void>;
    getNewPairingCode: (pairingCodeUuid: string | undefined | null) => Promise<void>;
}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { Alert },
    name: 'ErrorPage',
    props: {
        code: {
            type: String as PropType<Error>,
            required: true,
        },
    },
    data() {
        return {
            defaultCallback: () => this.$router.go(-1),
            isConfirmed: false,
        };
    },
    computed: {
        ...mapState(StoreType.SESSION, ['sessionMessageUuid', 'pairingCodeResponse', 'digidResponse']),
        errorDefinitions() {
            return {
                [Error.VALUE_message_user_not_authorized]: {
                    callback: async () => {
                        // This needs to be a hard redirect because of csrf token
                        if (this.sessionMessageUuid) {
                            window.location.href = this.$router.resolve({ name: 'auth.login' }).href;
                        } else {
                            window.location.href = this.$router.resolve({ name: 'home' }).href;
                        }
                    },
                    logo: LogoType.Locked,
                },
                [Error.VALUE_pairing_code_expired]: {
                    callback: async () => {
                        await this.getNewPairingCode(this.pairingCodeResponse?.pairingCodeUuid);
                        this.isConfirmed = true;
                    },
                    type: ErrorType.Info,
                },
            };
        },
        error() {
            const errorKey = Object.values(Error).includes(this.code) ? this.code : Error.VALUE_unknown;
            const errorDefinition = this.errorDefinitions[errorKey];
            const i18nKey = `errors.${errorKey}`;

            return {
                title: this.$i18n.t(`${i18nKey}.title`).toString(),
                message: this.$i18n
                    .t(`${i18nKey}.message`, {
                        emailAddress: this.pairingCodeResponse?.emailAddress,
                        name: this.digidResponse?.name,
                    })
                    .toString(),
                button: (this.$i18n.te(`${i18nKey}.button`)
                    ? this.$i18n.t(`${i18nKey}.button`)
                    : this.$i18n.t('pages.errorPage.goBack')
                ).toString(),
                confirmation: this.$i18n.te(`${i18nKey}.confirmation`)
                    ? this.$i18n.t(`${i18nKey}.confirmation`).toString()
                    : null,
                type: errorDefinition?.type ?? ErrorType.Error,
                logo: errorDefinition?.logo ?? LogoType.Default,
                callback: errorDefinition?.callback ?? this.defaultCallback,
            };
        },
    },
    methods: {
        ...mapActions(StoreType.SESSION, {
            logout: SessionStoreAction.LOGOUT,
            getNewPairingCode: SessionStoreAction.GET_NEW_PAIRING_CODE,
        }),
    },
});
</script>

<style lang="scss" scoped></style>
