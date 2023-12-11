import { messageApi } from '@/api';
import { generateFakeMessage } from '@/app/__fakes__/fakeMessage';
import { generateFakeMessageListItems } from '@/app/__fakes__/fakeMessageListItem';
import { Error } from '@/types/enums/error';
import { MessageListItem } from '@/types/models/MessageListItem';
import { createLocalVue } from '@vue/test-utils';
import Vuex from 'vuex';
import { RootStoreState } from '..';
import { StoreType } from '../storeType';
import messageStore, { State } from './messageStore';
import { MessageStoreAction } from './messageStoreAction';
import { MessageStoreMutation } from './messageStoreMutation';

const fakeMessage = generateFakeMessage();
const fakeMessageListItems: MessageListItem[] = generateFakeMessageListItems(2);

jest.spyOn(messageApi, 'getMessageList').mockImplementation(() => Promise.resolve(fakeMessageListItems));
jest.spyOn(messageApi, 'getMessageByUuid').mockImplementation(() => Promise.resolve({ message: fakeMessage }));

describe('messageStore', () => {
    const getStore = (state: Partial<State> = {}) => {
        const localVue = createLocalVue();
        localVue.use(Vuex);

        return new Vuex.Store<RootStoreState>({
            modules: {
                [StoreType.MESSAGE]: {
                    ...messageStore,
                    state: {
                        ...messageStore.state,
                        ...state,
                    },
                },
            },
        });
    };

    describe(`Action: ${MessageStoreAction.LOAD_LIST}`, () => {
        it('should request message list and commit them', async () => {
            const store = getStore();
            const state = store.state[StoreType.MESSAGE];
            const spyCommit = jest.spyOn(store, 'commit');

            expect(state.messageList).toEqual([]);
            await store.dispatch(`${StoreType.MESSAGE}/${MessageStoreAction.LOAD_LIST}`);

            // Check commit and API call
            expect(messageApi.getMessageList).toHaveBeenCalledWith();
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.MESSAGE}/${MessageStoreMutation.SET_LIST}`,
                fakeMessageListItems,
                undefined
            );

            // Check state
            expect(state.messageList).toEqual(fakeMessageListItems);
        });
    });

    describe(`Action: ${MessageStoreAction.LOAD_MESSAGE}`, () => {
        it('should request message and commit them', async () => {
            const store = getStore();
            const state = store.state[StoreType.MESSAGE];
            const spyCommit = jest.spyOn(store, 'commit');

            expect(state.currentMessage).toBeNull();
            await store.dispatch(`${StoreType.MESSAGE}/${MessageStoreAction.LOAD_MESSAGE}`, fakeMessage.uuid);

            // Check commit and API call
            expect(spyCommit).toHaveBeenNthCalledWith(
                1,
                `${StoreType.MESSAGE}/${MessageStoreMutation.RESET_CURRENT_MESSAGE}`,
                undefined,
                undefined
            );
            expect(messageApi.getMessageByUuid).toHaveBeenCalledWith(fakeMessage.uuid);
            expect(spyCommit).toHaveBeenNthCalledWith(
                2,
                `${StoreType.MESSAGE}/${MessageStoreMutation.SET_ERROR}`,
                null,
                undefined
            );
            expect(spyCommit).toHaveBeenNthCalledWith(
                3,
                `${StoreType.MESSAGE}/${MessageStoreMutation.SET_CURRENT_MESSAGE}`,
                fakeMessage,
                undefined
            );
            expect(spyCommit).toHaveBeenNthCalledWith(
                4,
                `${StoreType.MESSAGE}/${MessageStoreMutation.MARK_MESSAGE_READ}`,
                fakeMessage.uuid,
                undefined
            );
            expect(spyCommit).toHaveBeenNthCalledWith(
                5,
                `${StoreType.MESSAGE}/${MessageStoreMutation.SET_CURRENT_MESSAGE_LOADED}`,
                true,
                undefined
            );

            // Check state
            expect(state.error).toEqual(null);
            expect(state.currentMessage).toEqual(fakeMessage);
            expect(state.currentMessageLoaded).toBeTruthy();
        });

        it('should request message with failed api call and commit them', async () => {
            const store = getStore();
            const state = store.state[StoreType.MESSAGE];
            const spyCommit = jest.spyOn(store, 'commit');

            expect(state.currentMessage).toBeNull();
            jest.spyOn(messageApi, 'getMessageByUuid').mockImplementation(() => Promise.reject());
            await store.dispatch(`${StoreType.MESSAGE}/${MessageStoreAction.LOAD_MESSAGE}`, fakeMessage.uuid);

            // Check commit and API call
            expect(spyCommit).toHaveBeenNthCalledWith(
                1,
                `${StoreType.MESSAGE}/${MessageStoreMutation.RESET_CURRENT_MESSAGE}`,
                undefined,
                undefined
            );
            expect(messageApi.getMessageByUuid).toHaveBeenCalledWith(fakeMessage.uuid);
            expect(spyCommit).toHaveBeenNthCalledWith(
                2,
                `${StoreType.MESSAGE}/${MessageStoreMutation.SET_ERROR}`,
                Error.VALUE_message_user_not_authorized,
                undefined
            );
            expect(spyCommit).toHaveBeenNthCalledWith(
                3,
                `${StoreType.MESSAGE}/${MessageStoreMutation.SET_CURRENT_MESSAGE_LOADED}`,
                true,
                undefined
            );

            // Check state
            expect(state.error).toEqual(Error.VALUE_message_user_not_authorized);
            expect(state.currentMessage).toEqual(null);
            expect(state.currentMessageLoaded).toBeTruthy();
        });
    });

    describe(`Action: ${MessageStoreAction.UNLOAD_MESSAGE}`, () => {
        it('should unload message', async () => {
            const store = getStore({ currentMessage: fakeMessage });
            const state = store.state[StoreType.MESSAGE];
            const spyCommit = jest.spyOn(store, 'commit');

            expect(state.currentMessage).toEqual(fakeMessage);
            await store.dispatch(`${StoreType.MESSAGE}/${MessageStoreAction.UNLOAD_MESSAGE}`);

            // Check commit
            expect(spyCommit).toHaveBeenCalledWith(
                `${StoreType.MESSAGE}/${MessageStoreMutation.RESET_CURRENT_MESSAGE}`,
                undefined,
                undefined
            );

            // Check state
            expect(state.currentMessage).toBeNull();
        });
    });

    describe(`Mutation: ${MessageStoreMutation.MARK_MESSAGE_READ}`, () => {
        it('should mark given message as read if found', () => {
            const store = getStore({ messageList: [{ ...fakeMessageListItems[0], isRead: false }] });
            const state = store.state[StoreType.MESSAGE];
            const uuid = fakeMessageListItems[0].uuid;

            expect(state.messageList[0].isRead).toEqual(false);
            store.commit(`${StoreType.MESSAGE}/${MessageStoreMutation.MARK_MESSAGE_READ}`, uuid);

            // Check state
            expect(state.messageList.find(message => message.uuid === uuid)?.isRead).toEqual(true);
        });

        it('should not mark given message as read if not found', () => {
            const store = getStore({ messageList: fakeMessageListItems });
            const state = store.state[StoreType.MESSAGE];
            const uuid = '0000';

            store.commit(`${StoreType.MESSAGE}/${MessageStoreMutation.MARK_MESSAGE_READ}`, uuid);

            // Check state
            expect(state.messageList.find(message => message.uuid === uuid)?.isRead).toBeUndefined();
        });
    });

    describe(`Mutation: ${MessageStoreMutation.RESET_CURRENT_MESSAGE}`, () => {
        it('should reset current message', () => {
            const store = getStore({ currentMessage: fakeMessage });
            const state = store.state[StoreType.MESSAGE];

            expect(state.currentMessage).toEqual(fakeMessage);
            store.commit(`${StoreType.MESSAGE}/${MessageStoreMutation.RESET_CURRENT_MESSAGE}`);

            expect(state.currentMessage).toBeNull();
        });
    });

    describe(`Mutation: ${MessageStoreMutation.SET_CURRENT_MESSAGE}`, () => {
        it('should set currentMessage to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.MESSAGE];

            expect(state.currentMessage).toBeNull();
            store.commit(`${StoreType.MESSAGE}/${MessageStoreMutation.SET_CURRENT_MESSAGE}`, fakeMessage);

            expect(state.currentMessage).toEqual(fakeMessage);
        });
    });

    describe(`Mutation: ${MessageStoreMutation.SET_LIST}`, () => {
        it('should set messageList to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.MESSAGE];

            expect(state.messageList).toEqual([]);
            store.commit(`${StoreType.MESSAGE}/${MessageStoreMutation.SET_LIST}`, fakeMessageListItems);

            expect(state.messageList).toEqual(fakeMessageListItems);
        });
    });

    describe(`Mutation: ${MessageStoreMutation.SET_ERROR}`, () => {
        it('should set error to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.MESSAGE];

            expect(state.error).toEqual(null);
            store.commit(
                `${StoreType.MESSAGE}/${MessageStoreMutation.SET_ERROR}`,
                Error.VALUE_message_user_not_authorized
            );

            expect(state.error).toEqual(Error.VALUE_message_user_not_authorized);
        });
    });

    describe(`Mutation: ${MessageStoreMutation.SET_CURRENT_MESSAGE_LOADED}`, () => {
        it('should set currentMessageLoaded to given value', () => {
            const store = getStore();
            const state = store.state[StoreType.MESSAGE];

            expect(state.currentMessageLoaded).toEqual(false);
            store.commit(`${StoreType.MESSAGE}/${MessageStoreMutation.SET_CURRENT_MESSAGE_LOADED}`, true);

            expect(state.currentMessageLoaded).toEqual(true);
        });
    });
});
