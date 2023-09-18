<template>
    <details @toggle="e => (isOpen = e.target.open)">
        <summary class="btn btn-tertiary w-100">
            {{ title }}

            <SvgVue
                :icon="`chevron-${isOpen ? 'up' : 'down'}-solid`"
                class="ml-2"
                height="6px"
                focusable="false"
                aria-hidden="true"
            />
        </summary>

        <slot />
    </details>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';

interface Props {
    title: string;
}

interface Data {
    isOpen: boolean;
}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    name: 'Details',
    props: {
        title: {
            type: String,
            required: true,
        },
    },
    data() {
        return {
            isOpen: false,
        };
    },
});
</script>

<style lang="scss" scoped></style>
