import { generateFakeMessage } from '@/app/__fakes__/fakeMessage';
import { MessageStoreMutation } from '@/store/messageStore';
import messageStore, { State as MessageState } from '@/store/messageStore/messageStore';
import sessionStore from '@/store/sessionStore/sessionStore';
import { StoreType } from '@/store/storeType';
import { fakerjs, flushCallStack, setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import { VueConstructor } from 'vue';
import Vuex from 'vuex';
import FocusedHeader from '../FocusedHeader/FocusedHeader.vue';
import { Message as MessageModel } from '@/types/models/Message';
import Message from './Message.vue';
import { Error } from '@/types/enums/error';
import { authApi } from '@/api';
import BootstrapVue from 'bootstrap-vue';
import SvgVue from 'svg-vue';

const fakeMessage = generateFakeMessage();

jest.spyOn(window, 'print').mockReturnValue();
jest.spyOn(authApi, 'logout').mockImplementation(() => Promise.resolve());

const fakerUrl = fakerjs.internet.url();
Object.defineProperty(window, 'location', {
    value: {
        href: fakerUrl,
        assign: jest.fn(),
    },
});

const mockRouter = {
    resolve: jest.fn(() => {
        return {
            href: fakerUrl,
        };
    }),
};

const createComponent = setupTest(
    async (localVue: VueConstructor, messageState: Partial<MessageState> = {}) => {
        // Filters
        localVue.filter('formatDateTimeLong', (data: string) => data);

        const messageStoreModule = {
            ...messageStore,
            state: {
                ...messageStore.state,
                ...messageState,
            },
        };

        const wrapper = shallowMount(Message, {
            localVue,
            stubs: {
                focusedHeader: FocusedHeader,
            },
            mocks: {
                $router: mockRouter,
            },
            store: new Vuex.Store({
                modules: {
                    [StoreType.MESSAGE]: messageStoreModule,
                    [StoreType.SESSION]: sessionStore,
                },
            }),
        });

        await flushCallStack();

        return wrapper;
    },
    [BootstrapVue, SvgVue, Vuex]
);

describe('Message.vue', () => {
    it('should not load because has no currentMessage', async () => {
        const wrapper = await createComponent();

        expect(wrapper.findComponent({ name: 'BCard' }).exists()).toBe(false);
    });

    it('should show error because currentMessage failed', async () => {
        const wrapper = await createComponent({
            error: Error.VALUE_message_user_not_authorized,
            currentMessageLoaded: true,
        });
        expect(wrapper.findComponent({ name: 'BCard' }).exists()).toBe(true);
        expect(wrapper.find('[data-testid="title-error"]').exists()).toBe(true);

        const spyOnLogout = jest.spyOn(authApi, 'logout').mockImplementationOnce(() => Promise.resolve());

        const logoutButton = wrapper.find('[data-testid="button-start-over"]');
        logoutButton.trigger('click');

        expect(spyOnLogout).toHaveBeenCalledTimes(1);
        await wrapper.vm.$nextTick();
        expect(window.location.href).toBe(fakerUrl);
    });

    it('should load because has currentMessage', async () => {
        const wrapper = await createComponent({ currentMessage: fakeMessage, currentMessageLoaded: true });

        expect(wrapper.findComponent({ name: 'BCard' }).exists()).toBe(true);
        expect(wrapper.findComponent({ name: 'FocusedHeader' }).text()).toContain(fakeMessage.subject);
    });

    it('should focus title on change currentMessage', async () => {
        const wrapper = await createComponent({ currentMessage: fakeMessage, currentMessageLoaded: true });
        const spyOnFocus = jest.spyOn(wrapper.vm.$refs.focusedHeader as any, 'focus');

        const message: MessageModel = {
            ...fakeMessage,
            subject: fakerjs.lorem.sentence(),
        };

        await wrapper.vm.$store.commit(`${StoreType.MESSAGE}/${MessageStoreMutation.SET_CURRENT_MESSAGE}`, message);
        await wrapper.vm.$nextTick();

        expect(spyOnFocus).toHaveBeenCalledTimes(1);
    });

    it('should not show expiresAt when expiresAt = null', async () => {
        const message: MessageModel = {
            ...fakeMessage,
            expiresAt: null,
        };
        const wrapper = await createComponent({ currentMessage: message, currentMessageLoaded: true });

        expect(wrapper.find('[data-testid="text-expiresAt"]').exists()).toBe(false);
    });

    it('should show expiresAt when expiresAt = filled', async () => {
        const wrapper = await createComponent({ currentMessage: fakeMessage, currentMessageLoaded: true });

        expect(wrapper.find('[data-testid="text-expiresAt"]').exists()).toBe(true);
        expect(wrapper.find('[data-testid="text-expiresAt"]').text()).toContain('components.message.expiresAt');
    });

    it('should print currentMessage', async () => {
        const printSpy = jest.spyOn(window, 'print');
        const wrapper = await createComponent({ currentMessage: fakeMessage, currentMessageLoaded: true });

        const printButton = wrapper.find('[data-testid="button-print"]');
        await printButton.trigger('click');

        expect(printSpy).toHaveBeenCalledTimes(1);
    });

    it('should download currentMessage', async () => {
        const wrapper = await createComponent({ currentMessage: fakeMessage, currentMessageLoaded: true });

        const downloadButton = wrapper.find('[data-testid="button-download"]');
        await downloadButton.trigger('click');

        expect(window.location.assign).toHaveBeenCalledWith(`/messages/${fakeMessage.uuid}/pdf`);
    });

    it('should not show attachments', async () => {
        const wrapper = await createComponent({ currentMessage: fakeMessage, currentMessageLoaded: true });

        expect(wrapper.find('[data-testid="text-attachments"]').exists()).toBe(false);
    });

    it('should show attachments', async () => {
        const message: MessageModel = {
            ...fakeMessage,
            attachments: [
                {
                    uuid: fakerjs.datatype.uuid(),
                    name: fakerjs.lorem.word(),
                },
            ],
        };

        const wrapper = await createComponent({ currentMessage: message, currentMessageLoaded: true });

        const firstAttachment = message.attachments[0];
        const buttonAttachment = wrapper.find(`[data-testid="button-attachment-${firstAttachment.uuid}"]`);

        expect(wrapper.find('[data-testid="text-attachments"]').exists()).toBe(true);
        expect(buttonAttachment.exists()).toBe(true);

        await buttonAttachment.trigger('click');

        expect(window.location.assign).toHaveBeenCalledWith(
            `/messages/${message.uuid}/attachment/${firstAttachment.uuid}/download`
        );
    });
});
