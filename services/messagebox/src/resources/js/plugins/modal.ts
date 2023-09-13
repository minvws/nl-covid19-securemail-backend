import Vue from 'vue';
import BaseModal from '../app/components/BaseModal/BaseModal.vue';

export interface BaseModalMethods {
    show(params: BaseModalParams): void;
    hide(): void;
}

export interface BaseModalParams {
    autoFocusButton?: string;
    cancelTitle?: string;
    cancelVariant?: string;
    centered?: boolean;
    footerTag?: string;
    headerTag?: string;
    hideHeaderClose?: boolean;
    noCloseOnBackdrop?: boolean;
    noCloseOnEsc?: boolean;
    noEnforceFocus?: boolean;
    okOnly?: boolean;
    okTitle?: string;
    okVariant?: string;
    text?: string | (() => string);
    title?: string;
    titleTag?: string;

    onCancel?: () => void;
    onConfirm?: () => void;
}

const Modal = {
    EventBus: new Vue(),
    install(Vue: Vue.VueConstructor) {
        Vue.component('BaseModal', BaseModal);
        Vue.prototype.$modal = {
            show(params: any) {
                Modal.EventBus.$emit('show', params);
            },
            hide() {
                Modal.EventBus.$emit('hide');
            },
        };
    },
};

export default Modal;
