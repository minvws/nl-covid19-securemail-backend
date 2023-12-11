import { setupTest } from '@/utils/testUtils';
import { shallowMount, Wrapper } from '@vue/test-utils';
import { VueConstructor } from 'vue';
import FocusedHeader from './FocusedHeader.vue';

const createComponent = setupTest((localVue: VueConstructor) =>
    shallowMount(FocusedHeader, {
        localVue,
        // must attach to body to be able to determine document.activeElement
        attachTo: document.body,
    })
);

describe('FocusedHeader.vue', () => {
    // Because we use vuerouter children routes the default history length is 2
    it('should not focus on h1 on mount when history length < 2', () => {
        const wrapper = createComponent();
        expect(wrapper.find('h1').element).not.toEqual(document.activeElement);
    });

    it('should focus on h1 on mount when history length > 2', () => {
        window.history.pushState({}, '');
        window.history.pushState({}, '');
        const wrapper = createComponent();
        expect(wrapper.find('h1').element).toEqual(document.activeElement);
    });
});
