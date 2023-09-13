import { fakerjs, flushCallStack, setupTest } from '@/utils/testUtils';
import { shallowMount } from '@vue/test-utils';
import { VueConstructor } from 'vue';
import Paragraphs from '../Paragraphs/Paragraphs.vue';
import Section from './Section.vue';

const createComponent = setupTest((localVue: VueConstructor, sectionData = {}) => {
    const section = sectionData;
    return shallowMount(Section, {
        localVue,
        stubs: {
            Paragraphs,
        },
        mocks: {
            $t: (msg: string) => msg,
        },
        propsData: {
            section,
        },
    });
});

describe('Section.vue', () => {
    it('should load', async () => {
        const wrapper = createComponent();
        await flushCallStack();

        expect(wrapper.find('section').exists()).toBe(true);
        expect(wrapper.find('section').text()).toEqual('');
    });

    it.each([
        {
            prop: 'heading',
            value: fakerjs.lorem.sentence(),
            component: 'h2',
        },
        {
            prop: 'paragraphs',
            value: [fakerjs.lorem.paragraph(), fakerjs.lorem.paragraph()],
            component: '[data-testid="paragraphs"]',
        },
        {
            prop: 'listIntro',
            value: fakerjs.lorem.paragraph(),
            component: '[data-testid="listIntro"]',
        },
        {
            prop: 'listItems',
            value: [fakerjs.lorem.sentence(), fakerjs.lorem.sentence()],
            component: '[data-testid="listItems"]',
            childComponent: 'li',
        },
        {
            prop: 'listOutro',
            value: fakerjs.lorem.paragraph(),
            component: '[data-testid="listOutro"]',
        },
    ])(
        'if prop $prop isset it should show component $component with $value value',
        async ({ prop, value, component, childComponent }) => {
            const wrapper = createComponent({ [prop]: value });
            await flushCallStack();

            const section = wrapper.find('section');
            expect(section.find(component).exists()).toBe(true);

            if (Array.isArray(value)) {
                if (childComponent) {
                    expect(section.find(component).findAll(childComponent).length).toBe(value.length);
                }

                expect(section.find(component).text()).toBe(value.join(''));
            } else {
                expect(section.find(component).text()).toBe(value);
            }
        }
    );
});
