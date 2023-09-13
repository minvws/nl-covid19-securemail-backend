import { defineComponent } from '@/typeHelpers';
import { setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import { VueConstructor } from 'vue';
import Modal from './modal';

const createComponent = setupTest((localVue: VueConstructor) => {
    const TestComponent = defineComponent({ name: 'Test', template: '<base-modal />' });

    return shallowMount(TestComponent, {
        localVue,
    });
});

describe('modal', () => {
    it('should know show/hide methods', () => {
        const wrapper = createComponent();

        expect((wrapper.vm as any).$modal.show).toBeDefined();
        expect((wrapper.vm as any).$modal.hide).toBeDefined();
    });

    it('should render BaseModal component', () => {
        const wrapper = createComponent();

        expect(wrapper.findComponent({ name: 'BaseModal' }).exists()).toBe(true);
    });

    it('should emit "show" with given params when calling method show', () => {
        const wrapper = createComponent();
        const spy = jest.spyOn(Modal.EventBus, '$emit');

        (wrapper.vm as any).$modal.show({ test: 'test' });

        expect(spy).toHaveBeenCalledWith('show', { test: 'test' });
    });

    it('should emit "hide" calling method hide', () => {
        const wrapper = createComponent();
        const spy = jest.spyOn(Modal.EventBus, '$emit');

        (wrapper.vm as any).$modal.hide();

        expect(spy).toHaveBeenCalledWith('hide');
    });
});
