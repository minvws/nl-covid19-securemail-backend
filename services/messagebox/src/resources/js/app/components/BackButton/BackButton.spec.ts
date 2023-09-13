import { setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import { VueConstructor } from 'vue';
import BackButton from './BackButton.vue';

const createComponent = setupTest((localVue: VueConstructor, props?: object) =>
    shallowMount(BackButton, {
        localVue,
        propsData: props,
        mocks: {
            $t: (msg: string) => msg,
        },
    })
);

describe('BackButton.vue', () => {
    it('should load Bootstrap button in the template', () => {
        const props = {
            href: 'test',
        };

        const wrapper = createComponent(props);

        expect(wrapper.findComponent({ name: 'BButton' }).exists()).toBe(true);
    });

    it('should emit "click" on click', async () => {
        const wrapper = createComponent();

        wrapper.findComponent({ name: 'BButton' }).trigger('click');

        expect(wrapper.emitted().click).toBeTruthy();
    });
});
