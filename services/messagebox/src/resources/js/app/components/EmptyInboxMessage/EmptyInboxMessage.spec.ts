import { flushCallStack, setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import { VueConstructor } from 'vue';
import EmptyInboxMessage from './EmptyInboxMessage.vue';

const createComponent = setupTest((localVue: VueConstructor) =>
    shallowMount(EmptyInboxMessage, {
        localVue,
        mocks: {
            $t: (msg: string) => msg,
        },
    })
);

describe('EmptyInboxMessage.vue', () => {
    it('should load', async () => {
        const wrapper = createComponent();
        await flushCallStack();

        expect(wrapper.find('div').exists()).toBe(true);
    });
});
