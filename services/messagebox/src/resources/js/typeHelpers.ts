import _Vue, { VueConstructor } from 'vue';
import { ThisTypedComponentOptionsWithRecordProps } from 'vue/types/options';
import VueRouter, { Route } from 'vue-router';
import { DateTimeFormatResult, NumberFormatResult, TranslateResult } from 'vue-i18n';
import store from './store';
import { BaseModalParams } from './plugins/modal';

/**
 * Used when the body of an object is not known
 */
export interface AnyObject {
    [key: string]: any;
}

export interface AppProps {
    $modal: {
        show: (params: BaseModalParams) => void;
        hide: () => void;
    };
    $store: typeof store;
    $router: VueRouter;
    $route: Route;
    $t: TranslateResult;
    $d: DateTimeFormatResult;
    $n: NumberFormatResult;
}

/**
 * Helper to define a Vue component by extending Vue, adding the global app properties
 * and allows for adding additional props using the generics.
 *
 * @param options
 * @returns A component reference with the global properties and any custom additional properties
 */
export const defineComponent = <
    Data,
    Methods,
    Computed,
    Props,
    AdditionalProps = object,
    V extends _Vue & AppProps & AdditionalProps = _Vue & AppProps & AdditionalProps
>(
    options: ThisTypedComponentOptionsWithRecordProps<V, Data, Methods, Computed, Props>
) => {
    const Vue = _Vue as VueConstructor<V>;

    return Vue.extend(options);
};
