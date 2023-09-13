<template>
    <BModal ref="modal" v-bind="modalConfig" v-on="$listeners" @hide="onHide" @ok="onOk">
        {{ typeof text === 'function' ? text() : text }}
    </BModal>
</template>

<script lang="ts">
import i18n from '@/i18n';
import Modal, { BaseModalParams } from '@/plugins/modal';
import { defineComponent } from '@/typeHelpers';
import { BModal } from 'bootstrap-vue';

const defaultParams: BaseModalParams = {
    autoFocusButton: 'ok',
    cancelTitle: i18n.t('components.baseModal.cancel').toString(),
    cancelVariant: 'link',
    centered: true,
    footerTag: 'div',
    headerTag: 'div',
    hideHeaderClose: false,
    noCloseOnBackdrop: true,
    noCloseOnEsc: true,
    noEnforceFocus: false,
    okOnly: false,
    okTitle: i18n.t('components.baseModal.ok').toString(),
    okVariant: 'primary',
    titleTag: 'h1',
};

interface Props {
    hasBackButton: boolean;
    homeHref: string;
    isHome: boolean;
}

interface Data {
    modalConfig: BaseModalParams;
    text?: string | (() => string);
    onCancel?: () => void;
    onConfirm?: () => void;
}

interface Computed {}

interface Methods {
    hide: () => void;
    onHide: ({ trigger }: { trigger: string }) => void;
    onOk: () => void;
    show: (params: BaseModalParams) => void;
}

interface AdditionalProps {
    $refs: { modal: BModal };
}

export default defineComponent<Data, Methods, Computed, Props, AdditionalProps>({
    name: 'BaseModal',
    components: { BModal },
    data() {
        return {
            modalConfig: defaultParams,
            text: undefined,
            onCancel: undefined,
            onConfirm: undefined,
        };
    },
    beforeMount() {
        Modal.EventBus.$on('show', (params: BaseModalParams) => {
            this.show({ ...params });
        });
        Modal.EventBus.$on('hide', () => {
            this.hide();
        });
    },
    methods: {
        hide() {
            this.$refs.modal.hide();
        },
        onHide({ trigger }) {
            // call onCancel on all possible ways to close the modal (only way to catch ESC), except confirming
            if (trigger === 'ok' || !this.onCancel) return;

            this.onCancel();
        },
        onOk() {
            if (!this.onConfirm) return;

            this.onConfirm();
        },
        show(params) {
            this.modalConfig = {
                ...defaultParams,
                ...params,
            };

            this.text = params.text;
            this.onCancel = params.onCancel;
            this.onConfirm = params.onConfirm;

            this.$refs.modal.show();
        },
    },
});
</script>

<style lang="scss" scoped></style>
