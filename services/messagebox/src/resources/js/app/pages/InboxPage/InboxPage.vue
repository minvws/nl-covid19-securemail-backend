<template>
    <div class="wrapper">
        <Navbar :hasBackButton="currentMessageLoaded" @back="$router.replace({ name: 'inbox' })" />
        <div class="container-fluid py-0">
            <div class="row h-100">
                <aside
                    class="col-12 col-lg-3 d-lg-block p-0"
                    :class="{ 'd-none': currentMessageLoaded }"
                    :aria-label="$t('pages.inboxPage.messagesListTitle')"
                >
                    <h1 class="d-lg-none pt-3 px-3">{{ $t('pages.inboxPage.pageTitle') }}</h1>
                    <p
                        v-if="messageList.length === 0 && !loading"
                        class="py-4 my-3 text-center"
                        data-testid="text-no-messages"
                    >
                        {{ $t('pages.inboxPage.noMessages') }}
                    </p>
                    <p v-else-if="loading" class="py-4 my-3 text-center" data-testid="text-loading">
                        {{ $t('pages.inboxPage.loading') }}
                    </p>
                    <BListGroup v-if="messageList.length > 0" tag="ul">
                        <li v-for="message in messageList" :key="message.uuid">
                            <BListGroupItem
                                :active="currentMessage && currentMessage.uuid === message.uuid"
                                :aria-current="currentMessage && currentMessage.uuid === message.uuid"
                                :class="{ unread: !message.isRead }"
                                class="d-flex align-items-start"
                                @click="openMessage(message.uuid)"
                                button
                            >
                                <SvgVue
                                    icon="unread"
                                    width="8px"
                                    class="mr-2"
                                    :class="{ invisible: message.isRead }"
                                    focusable="false"
                                    :aria-hidden="message.isRead"
                                    :aria-label="$t('pages.inboxPage.unread')"
                                    data-testid="icon-unread"
                                />
                                <div class="flex-fill">
                                    <p class="title mb-0" data-testid="text-from">{{ message.fromName }}</p>
                                    <p class="mb-0" data-testid="text-subject">
                                        {{ message.subject }}
                                    </p>
                                </div>
                                <div class="align-self-center text-right">
                                    <small class="date d-block text-nowrap">
                                        {{ message.createdAt | formatDateTimeShort }}
                                    </small>
                                    <SvgVue
                                        v-if="message.hasAttachments"
                                        icon="attachment"
                                        width="14px"
                                        fill="none"
                                        class="ml-2"
                                        focusable="false"
                                        :aria-label="$t('pages.inboxPage.hasAttachment')"
                                        data-testid="icon-attachment"
                                    />
                                </div>
                            </BListGroupItem>
                        </li>
                    </BListGroup>
                </aside>
                <main
                    v-if="!loading"
                    id="main"
                    role="main"
                    class="d-lg-block flex-fill"
                    :class="{ 'd-none': !currentMessageLoaded }"
                    data-testid="content-main"
                >
                    <router-view></router-view>
                </main>
            </div>
        </div>
        <Footer />
    </div>
</template>
<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import Navbar from '@/app/components/Navbar/Navbar.vue';
import Footer from '@/app/components/Footer/Footer.vue';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import { mapActions, mapState } from 'vuex';
import { StoreType } from '@/store/storeType';
import { Message } from '@/types/models/Message';
import { MessageStoreAction } from '@/store/messageStore';
import { MessageListItem } from '@/types/models/MessageListItem';

interface Props {}

interface Data {
    loading: boolean;
}

interface Computed {
    messageList: MessageListItem[];
    currentMessage: Message | null;
    currentMessageLoaded: boolean;
    sessionMessageUuid: string | undefined;
}

interface Methods {
    loadList: () => Promise<void>;
    loadMessage: (messageUuid: string) => Promise<void>;
    openMessage: (uuid: string) => void;
}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { Footer, Navbar, FocusedHeader },
    name: 'InboxPage',
    data() {
        return {
            loading: true,
        };
    },
    async created() {
        await this.loadList();
        this.loading = false;

        if (this.$route.params?.messageId) {
            // If route has messageId load single message
            await this.loadMessage(this.$route.params.messageId);
        } else if (this.sessionMessageUuid) {
            // If messageUuid is known in session redirect to single message
            this.openMessage(this.sessionMessageUuid);
        } else if (this.messageList.length > 0) {
            // If no message selected and more then 0 messages redirect to single message
            this.openMessage(this.messageList[0].uuid);
        }
    },
    watch: {
        '$route.params.messageId': {
            handler(newVal) {
                const action = newVal ? MessageStoreAction.LOAD_MESSAGE : MessageStoreAction.UNLOAD_MESSAGE;
                this.$store.dispatch(`${StoreType.MESSAGE}/${action}`, newVal);
            },
        },
    },
    computed: {
        ...mapState(StoreType.MESSAGE, ['messageList', 'currentMessage', 'currentMessageLoaded']),
        ...mapState(StoreType.SESSION, ['sessionMessageUuid']),
    },
    methods: {
        ...mapActions(StoreType.MESSAGE, {
            loadList: MessageStoreAction.LOAD_LIST,
            loadMessage: MessageStoreAction.LOAD_MESSAGE,
        }),
        openMessage(uuid) {
            if (this.currentMessage?.uuid === uuid) return;
            this.$router.push({ name: 'inbox.message', params: { messageId: uuid } });
        },
    },
});
</script>
<style lang="scss" scoped>
@import './resources/scss/_variables.scss';

aside {
    background: $white;
    border-top: 1px solid $light-grey;
    border-bottom: 1px solid $light-grey;

    @media (min-width: $breakpoint-lg) {
        overflow-y: auto;
    }

    h1 {
        font-size: 1.5rem;
        line-height: 2rem;
    }
}

main {
    @media (min-width: $breakpoint-lg) {
        padding-top: 40px;
        padding-bottom: 40px;

        overflow-y: auto;
    }

    .card {
        margin: 0 auto;
        @media (min-width: $breakpoint-lg) {
            max-width: 690px;
        }
    }
}
</style>
