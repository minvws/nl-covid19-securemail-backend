import { messageApi } from '@/api';
import { generateFakeMessage } from '@/app/__fakes__/fakeMessage';
import { generateFakeMessageListItems } from '@/app/__fakes__/fakeMessageListItem';
import messageStore from '@/store/messageStore/messageStore';
import sessionStore, { State as SessionState } from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { flushCallStack, setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import BootstrapVue from 'bootstrap-vue';
import SvgVue from 'svg-vue';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';
import InboxPage from './InboxPage.vue';

const fakeMessage = generateFakeMessage();
const fakeMessageListItems = generateFakeMessageListItems(1);

const spyGetMessageList = jest
    .spyOn(messageApi, 'getMessageList')
    .mockImplementation(() => Promise.resolve(fakeMessageListItems));
const spyGetMessageByUuid = jest
    .spyOn(messageApi, 'getMessageByUuid')
    .mockImplementation(() => Promise.resolve({ message: fakeMessage }));

const defaultMockRoute = {
    name: 'inbox',
    params: {},
};

const mockRouter = {
    push: jest.fn(),
    replace: jest.fn(),
};

const createComponent = setupTest(
    async (localVue: VueConstructor, route: object = {}, sessionState: Partial<SessionState> = {}) => {
        const sessionStoreModule = {
            ...sessionStore,
            state: {
                ...sessionStore.state,
                ...sessionState,
            },
        };

        const messageStoreModule = {
            ...messageStore,
            state: {
                ...messageStore.state,
            },
        };

        const mockRoute = {
            ...defaultMockRoute,
            ...route,
        };

        localVue.filter('formatDateTimeShort', jest.fn());

        const wrapper = shallowMount(InboxPage, {
            localVue,
            stubs: ['router-view'],
            mocks: {
                $t: (msg: string) => msg,
                $route: mockRoute,
                $router: mockRouter,
            },
            store: new Vuex.Store({
                modules: {
                    [StoreType.SESSION]: sessionStoreModule,
                    [StoreType.MESSAGE]: messageStoreModule,
                },
            }),
        });
        await flushCallStack();

        return wrapper;
    },
    [BootstrapVue, SvgVue, Vuex]
);

describe('InboxPage.vue', () => {
    it('should load', async () => {
        const wrapper = await createComponent();

        expect(wrapper.find('div').exists()).toBe(true);
    });

    it('should show loading when waiting for messageList', async () => {
        spyGetMessageList.mockImplementationOnce(() => new Promise(resolve => setTimeout(resolve, 100)));
        const wrapper = await createComponent();

        expect(wrapper.find('[data-testid="text-loading"]').exists()).toBe(true);
    });

    it('should show no messages if there are no messages', async () => {
        spyGetMessageList.mockImplementationOnce(() => Promise.resolve([]));
        const wrapper = await createComponent();

        expect(wrapper.find('[data-testid="text-no-messages"]').exists()).toBe(true);
    });

    it('should show single message', async () => {
        const wrapper = await createComponent();

        const listGroup = wrapper.findComponent({ name: 'BListGroup' });
        expect(listGroup.exists()).toBe(true);

        const listGroupItems = wrapper.findAllComponents({ name: 'BListGroupItem' });
        expect(listGroupItems.length).toBe(1);
        expect(
            listGroupItems
                .at(0)
                .find('[data-testid="text-from"]')
                .text()
        ).toEqual(fakeMessageListItems[0].fromName);
    });

    it('should show single read message', async () => {
        spyGetMessageList.mockImplementationOnce(() =>
            Promise.resolve([
                {
                    ...fakeMessageListItems[0],
                    isRead: true,
                },
            ])
        );
        const wrapper = await createComponent();

        const listGroup = wrapper.findComponent({ name: 'BListGroup' });
        expect(listGroup.exists()).toBe(true);

        const listGroupItems = wrapper.findAllComponents({ name: 'BListGroupItem' });
        expect(listGroupItems.length).toBe(1);
        expect(
            listGroupItems
                .at(0)
                .find('[data-testid="text-from"]')
                .text()
        ).toEqual(fakeMessageListItems[0].fromName);
        expect(
            listGroupItems
                .at(0)
                .find('[data-testid="icon-unread"]')
                .classes()
        ).toContain('invisible');
    });

    it('should show single unread message', async () => {
        spyGetMessageList.mockImplementationOnce(() =>
            Promise.resolve([
                {
                    ...fakeMessageListItems[0],
                    isRead: false,
                },
            ])
        );
        const wrapper = await createComponent();

        const listGroup = wrapper.findComponent({ name: 'BListGroup' });
        expect(listGroup.exists()).toBe(true);

        const listGroupItems = wrapper.findAllComponents({ name: 'BListGroupItem' });
        expect(listGroupItems.length).toBe(1);
        expect(
            listGroupItems
                .at(0)
                .find('[data-testid="text-from"]')
                .text()
        ).toEqual(fakeMessageListItems[0].fromName);
        expect(
            listGroupItems
                .at(0)
                .find('[data-testid="icon-unread"]')
                .classes()
        ).not.toContain('invisible');
    });

    it('should show two messages', async () => {
        const messageList = generateFakeMessageListItems(2);
        spyGetMessageList.mockImplementationOnce(() => Promise.resolve(messageList));
        const wrapper = await createComponent();

        const listGroup = wrapper.findComponent({ name: 'BListGroup' });
        expect(listGroup.exists()).toBe(true);

        const listGroupItems = wrapper.findAllComponents({ name: 'BListGroupItem' });
        expect(listGroupItems.length).toBe(2);
        expect(
            listGroupItems
                .at(0)
                .find('[data-testid="text-from"]')
                .text()
        ).toEqual(messageList[0].fromName);
        expect(
            listGroupItems
                .at(1)
                .find('[data-testid="text-from"]')
                .text()
        ).toEqual(messageList[1].fromName);
    });

    it('should show attachment icon on list', async () => {
        const messageList = generateFakeMessageListItems(2);
        messageList[0].hasAttachments = true;
        messageList[1].hasAttachments = false;

        jest.spyOn(messageApi, 'getMessageList').mockImplementationOnce(() => Promise.resolve(messageList));
        const wrapper = await createComponent();

        const listGroupItems = wrapper.findAllComponents({ name: 'BListGroupItem' });
        expect(listGroupItems.length).toBe(2);

        expect(
            listGroupItems
                .at(0)
                .find('[data-testid="icon-attachment"]')
                .exists()
        ).toBe(true);
        expect(
            listGroupItems
                .at(1)
                .find('[data-testid="icon-attachment"]')
                .exists()
        ).toBe(false);
    });

    it('should open message onclick', async () => {
        const wrapper = await createComponent();

        const listGroupItems = wrapper
            .findComponent({ name: 'BListGroup' })
            .findAllComponents({ name: 'BListGroupItem' });

        await listGroupItems.at(0).trigger('click');
        expect(mockRouter.push).toHaveBeenNthCalledWith(1, {
            name: 'inbox.message',
            params: { messageId: fakeMessageListItems[0].uuid },
        });
    });

    it('should open message based on route', async () => {
        await createComponent({
            name: 'inbox.message',
            params: {
                messageId: fakeMessage.uuid,
            },
        });

        expect(spyGetMessageByUuid).toHaveBeenNthCalledWith(1, fakeMessage.uuid);
    });

    it('should open message after route change', async () => {
        const wrapper = await createComponent();

        wrapper.vm.$set(wrapper.vm.$route, 'params', { messageId: fakeMessage.uuid });
        await flushCallStack();

        expect(spyGetMessageByUuid).toHaveBeenNthCalledWith(1, fakeMessage.uuid);
    });

    it('should deselect message when going back in route', async () => {
        const wrapper = await createComponent({
            params: {
                messageId: fakeMessage.uuid,
            },
        });

        expect(wrapper.vm.$store.state.message.currentMessage?.uuid).toBe(fakeMessage.uuid);
        wrapper.vm.$set(wrapper.vm.$route, 'params', { messageId: null });
        await flushCallStack();

        expect(wrapper.vm.$store.state.message.currentMessage).toBe(null);
    });

    // current message already set.
    it('should not open message because same message already selected', async () => {
        spyGetMessageList.mockImplementationOnce(() =>
            Promise.resolve([
                {
                    ...fakeMessageListItems[0],
                    uuid: fakeMessage.uuid,
                },
            ])
        );

        const wrapper = await createComponent({
            params: {
                messageId: fakeMessage.uuid,
            },
        });

        expect(wrapper.vm.$store.state.message.currentMessage?.uuid).toBe(fakeMessage.uuid);
        const listGroupItems = wrapper
            .findComponent({ name: 'BListGroup' })
            .findAllComponents({ name: 'BListGroupItem' });

        await listGroupItems.at(0).trigger('click');
        expect(mockRouter.push).not.toHaveBeenCalled();
    });

    it('should open message based on sessionMessageUuid', async () => {
        await createComponent(
            {},
            {
                sessionMessageUuid: fakeMessage.uuid,
            }
        );

        expect(mockRouter.push).toHaveBeenNthCalledWith(1, {
            name: 'inbox.message',
            params: { messageId: fakeMessage.uuid },
        });
    });
});
