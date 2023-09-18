<template>
    <BCard v-if="currentMessageLoaded">
        <template v-if="currentMessage">
            <FocusedHeader class="mb-3 mb-lg-4" ref="focusedHeader">{{ currentMessage.subject }}</FocusedHeader>
            <div class="message-info mb-3 mb-lg-4">
                <strong>{{ currentMessage.fromName }}</strong>
                <div>
                    {{
                        $t('components.message.receivedAt', {
                            datetime: $options.filters.formatDateTimeLong(currentMessage.createdAt),
                        })
                    }}
                </div>
                <div v-if="currentMessage.expiresAt" data-testid="text-expiresAt">
                    {{
                        $t('components.message.expiresAt', {
                            datetime: $options.filters.formatDateTimeLong(currentMessage.expiresAt),
                        })
                    }}
                </div>
            </div>
            <div class="mb-3 mb-lg-4">
                <BButton
                    variant="link"
                    class="px-0 mr-3"
                    @click="print"
                    :title="$t('components.message.printButton')"
                    data-testid="button-print"
                >
                    <SvgVue
                        icon="print"
                        width="24px"
                        aria-hidden="true"
                        focusable="false"
                        class="d-none d-lg-block mr-2"
                    />
                    {{ $t('components.message.printButton') }}
                </BButton>
                <BButton
                    variant="link"
                    class="px-0"
                    @click="download"
                    :title="$t('components.message.downloadButton')"
                    data-testid="button-download"
                >
                    <SvgVue
                        icon="download"
                        width="24px"
                        aria-hidden="true"
                        focusable="false"
                        class="d-none d-lg-block mr-2"
                    />
                    {{ $t('components.message.downloadButton') }}
                </BButton>
            </div>
            <hr />
            <div class="message-body pt-3 pt-lg-4" v-html="currentMessage.text"></div>
            <template v-if="currentMessage.attachments && currentMessage.attachments.length > 0">
                <hr />
                <div class="pt-3 pt-lg-4">
                    <p class="text-muted mb-0" data-testid="text-attachments">
                        {{ $t('components.message.attachments') }}
                    </p>
                    <div class="d-flex flex-column align-items-start">
                        <BButton
                            v-for="attachment in currentMessage.attachments"
                            :key="attachment.uuid"
                            variant="link"
                            class="p-0 mt-2"
                            @click="openAttachment(attachment.uuid)"
                            :data-testid="'button-attachment-' + attachment.uuid"
                        >
                            <SvgVue icon="download" width="24px" aria-hidden="true" focusable="false" class="mr-1" />
                            {{ attachment.name }}
                        </BButton>
                    </div>
                </div>
            </template>
        </template>
        <template v-else-if="error">
            <FocusedHeader class="mb-3 mb-lg-4" data-testid="title-error">
                {{ $t('components.message.error.title') }}
            </FocusedHeader>
            <Paragraphs :data="Object.values($t('components.message.error.paragraphs'))" />
            <BButton variant="primary" @click="startOver" data-testid="button-start-over">
                {{ $t('components.message.error.button') }}
            </BButton>
        </template>
    </BCard>
</template>
<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import Paragraphs from '@/app/components/Paragraphs/Paragraphs.vue';
import { Message } from '@/types/models/Message';
import { mapActions, mapState } from 'vuex';
import { StoreType } from '@/store/storeType';
import { Error } from '@/types/enums/error';
import { SessionStoreAction } from '@/store/sessionStore';

interface Props {}

interface Data {}

interface Computed {
    currentMessage: Message;
    currentMessageLoaded: boolean;
    error: Error | null;
}

interface Methods {
    logout: () => Promise<void>;
    startOver: () => void;
    print: () => void;
    download: () => void;
    openAttachment: (uuid: string) => void;
}

interface AdditionalProps {
    $refs: { focusedHeader: InstanceType<typeof FocusedHeader> };
}

export default defineComponent<Data, Methods, Computed, Props, AdditionalProps>({
    components: { FocusedHeader, Paragraphs },
    name: 'Message',
    computed: {
        ...mapState(StoreType.MESSAGE, ['currentMessage', 'error', 'currentMessageLoaded']),
    },
    watch: {
        async currentMessage(newMessage) {
            // Make sure focusedHeader is rendered
            await this.$nextTick();

            this.$refs.focusedHeader?.focus();
        },
    },
    methods: {
        ...mapActions(StoreType.SESSION, {
            logout: SessionStoreAction.LOGOUT,
        }),
        async startOver() {
            await this.logout();
            // This needs to be a hard redirect because of csrf token
            window.location.href = this.$router.resolve({ name: 'home' }).href;
        },
        print() {
            window.print();
        },
        download() {
            window.location.assign(`/messages/${this.currentMessage.uuid}/pdf`);
        },
        openAttachment(uuid) {
            window.location.assign(`/messages/${this.currentMessage.uuid}/attachment/${uuid}/download`);
        },
    },
});
</script>
<style lang="scss" scoped>
@import './resources/scss/_variables.scss';

.card-body {
    padding: 1rem;

    @media (min-width: $breakpoint-lg) {
        padding: 40px;
    }
}

h1 {
    font-size: 1.5rem;
    line-height: 2rem;

    @media (min-width: $breakpoint-lg) {
        font-size: 2rem;
        line-height: 3rem;
    }
}
</style>
