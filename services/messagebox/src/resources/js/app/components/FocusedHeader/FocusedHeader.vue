<template>
    <h1 class="focus-header" tabindex="-1" ref="header">
        <slot />
    </h1>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';

interface Props {}

interface Data {}

interface Computed {}

interface Methods {
    focus: () => void;
}

export default defineComponent<Data, Methods, Computed, Props, { $refs: { header: HTMLHeadingElement } }>({
    name: 'FocusedHeader',
    mounted() {
        // Because we use vuerouter children routes the default history length is 2
        if (window.history.length > 2) {
            this.focus();
        }
    },
    methods: {
        focus() {
            this.$refs.header.focus();
        },
    },
});
</script>

<style lang="scss" scoped>
.focus-header {
    outline: 0 !important;
}
</style>
