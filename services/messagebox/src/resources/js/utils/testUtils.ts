import Modal from '@/plugins/modal';
import { faker } from '@faker-js/faker';
import { RenderResult } from '@testing-library/vue';
import { createLocalVue, Wrapper } from '@vue/test-utils';
import BootstrapVue from 'bootstrap-vue';
import SvgVue from 'svg-vue';
import Vue, { VueConstructor } from 'vue';
import VueI18n from 'vue-i18n';
import VueRouter from 'vue-router';
import Vuex from 'vuex';

/**
 * Flushes the Promise stack by awaiting a new immediate Promise.
 * @returns {Promise<void>} The Promise to be awaited for flush
 */
export const flushCallStack = () => new Promise(resolve => setTimeout(resolve));

/**
 * Mocks timers with modern implementation.
 * @param {Date} systemTime - The time to set System Time to
 */
export const useFakeTimers = (systemTime: Date) => {
    jest.useFakeTimers('modern');
    jest.setSystemTime(systemTime);
};

// ================================================================================================ setupTest()

const defaultPlugins = [BootstrapVue, Modal, Vuex, SvgVue, VueRouter, VueI18n];

let localVue: VueConstructor;
let currentWrapper: Wrapper<Vue> | RenderResult;

/**
 * Use this setup function to create your testcomponent factory. It will help with:
 * - destroying the wrapper after the test (to prevent memory leaks)
 * - creating the localVue instance
 *
 * @param factory a function that takes the localvue and any custom arguments and returns a wrapper
 * @param plugins overwrite the plugins that are used in the localVue construction
 * @returns a function that takes the custom arguments and returns a wrapper. Use this function to create your components
 */
interface ISetup {
    <TProperties extends unknown[], TRenderResult extends Promise<RenderResult>>(
        factory: (localVue: VueConstructor, ...args: TProperties) => TRenderResult,
        plugins?: typeof defaultPlugins
    ): (...args: TProperties) => TRenderResult;
    <TProperties extends unknown[], TWrapper extends RenderResult>(
        factory: (localVue: VueConstructor, ...args: TProperties) => TWrapper,
        plugins?: typeof defaultPlugins
    ): (...args: TProperties) => TWrapper;
    <TProperties extends unknown[], TComponent extends Vue, TWrapper extends Wrapper<TComponent>>(
        factory: (localVue: VueConstructor, ...args: TProperties) => TWrapper,
        plugins?: typeof defaultPlugins
    ): (...args: TProperties) => TWrapper;
    <TProperties extends unknown[], TComponent extends Vue, TWrapper extends Promise<Wrapper<TComponent>>>(
        factory: (localVue: VueConstructor, ...args: TProperties) => TWrapper,
        plugins?: typeof defaultPlugins
    ): (...args: TProperties) => TWrapper;
}

export const setupTest: ISetup = <TProperties extends unknown[]>(
    factory: Parameters<ISetup>[0],
    plugins = defaultPlugins
) => {
    if (localVue) {
        throw new Error('Only one setup allowed per test file');
    }

    localVue = createLocalVue();
    plugins.forEach(plugin => localVue.use(plugin));

    afterEach(() => {
        if (isRenderResult(currentWrapper)) {
            currentWrapper.unmount();
            return;
        }
        currentWrapper.destroy();
    });

    return (...a: TProperties) =>
        setupWrapper<Vue, ReturnType<typeof factory>>((b: VueConstructor) => factory(b, ...a));
};

interface IWrapper {
    <TComponent extends Vue, TWrapper extends Wrapper<TComponent>>(
        createWrapper: (localVue: VueConstructor) => TWrapper
    ): TWrapper;
    <TComponent extends Vue, TWrapper extends Promise<Wrapper<TComponent>>>(
        createWrapper: (localVue: VueConstructor) => TWrapper
    ): TWrapper;
}

const setupWrapper: IWrapper = (createWrapper: Parameters<IWrapper>[0]) => {
    if (!localVue) {
        throw new Error('First use setupTest()');
    }

    const wrapper = createWrapper(localVue);
    if (wrapper instanceof Promise) {
        return wrapper.then(resolvedWrapper => (currentWrapper = resolvedWrapper));
    }

    return (currentWrapper = wrapper);
};

/**
 * Decorator for faker.
 * @returns faker, decorated with custom helpers.
 */
export const fakerjs = {
    ...faker,
    custom: {
        arrayOfUuids: (min = 1, max = 10) =>
            faker.helpers.uniqueArray(faker.datatype.uuid, faker.datatype.number({ min, max })),
        typedArray: <Type>(el: Type, min = 1, max = 10) =>
            [...Array(faker.datatype.number({ min, max }))].map(() => el),
    },
};

function isRenderResult(result: Wrapper<Vue> | RenderResult): result is RenderResult {
    return result && !!(result as RenderResult)?.isUnmounted;
}
