import { messageApi } from '@/api/index';
import { Error } from '@/types/enums/error';
import { Message } from '@/types/models/Message';
import { MessageListItem } from '@/types/models/MessageListItem';
import { Module } from 'vuex';
import { MessageStoreAction, MessageStoreMutation } from '.';

export interface State {
    messageList: MessageListItem[];
    currentMessage?: Message | null;
    currentMessageLoaded: boolean;
    error: Error | null;
}

const getDefaultState = (): State => ({
    messageList: [],
    currentMessage: null,
    currentMessageLoaded: false,
    error: null,
});

const messageStore: Module<State, unknown> = {
    namespaced: true,
    state: getDefaultState(),
    actions: {
        async [MessageStoreAction.LOAD_LIST]({ commit }) {
            const list = await messageApi.getMessageList();
            commit(MessageStoreMutation.SET_ERROR, null);
            commit(MessageStoreMutation.SET_LIST, list);
        },
        async [MessageStoreAction.LOAD_MESSAGE]({ commit }, messageUuid: string) {
            commit(MessageStoreMutation.RESET_CURRENT_MESSAGE);

            try {
                const { message } = await messageApi.getMessageByUuid(messageUuid);
                commit(MessageStoreMutation.SET_ERROR, null);
                commit(MessageStoreMutation.SET_CURRENT_MESSAGE, message);
                commit(MessageStoreMutation.MARK_MESSAGE_READ, message.uuid);
            } catch (error) {
                commit(MessageStoreMutation.SET_ERROR, Error.VALUE_message_user_not_authorized);
            } finally {
                commit(MessageStoreMutation.SET_CURRENT_MESSAGE_LOADED, true);
            }
        },
        [MessageStoreAction.UNLOAD_MESSAGE]({ commit }) {
            commit(MessageStoreMutation.SET_CURRENT_MESSAGE_LOADED, false);
            commit(MessageStoreMutation.RESET_CURRENT_MESSAGE);
        },
    },
    mutations: {
        [MessageStoreMutation.MARK_MESSAGE_READ](state: State, uuid: string) {
            const index = state.messageList.findIndex(m => m.uuid === uuid);
            if (index === -1) return;

            state.messageList[index].isRead = true;
        },
        [MessageStoreMutation.RESET_CURRENT_MESSAGE](state: State) {
            state.currentMessage = null;
        },
        [MessageStoreMutation.SET_CURRENT_MESSAGE](state: State, message: Message) {
            state.currentMessage = message;
        },
        [MessageStoreMutation.SET_CURRENT_MESSAGE_LOADED](state: State, currentMessageLoaded: boolean) {
            state.currentMessageLoaded = currentMessageLoaded;
        },
        [MessageStoreMutation.SET_LIST](state: State, listItems: MessageListItem[]) {
            state.messageList = listItems;
        },
        [MessageStoreMutation.SET_ERROR](state: State, error: Error | null) {
            state.error = error;
        },
    },
};

export default messageStore;
