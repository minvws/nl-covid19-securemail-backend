<template>
    <div>
        <p v-for="(paragraph, $index) in paragraphs" :key="`paragraph_${$index}`" v-html="paragraph" />
    </div>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';

interface Props {
    data: string[];
    params: Record<string, string>;
}

interface Data {}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    name: 'Paragraphs',
    props: {
        data: {
            type: Array,
            required: true,
        },
        params: {
            type: Object,
            required: false,
            default: () => ({}),
        },
    },
    computed: {
        paragraphs() {
            return this.data.map(paragraph =>
                paragraph.replace(/{(\w+)}/g, (match: string, param: string) => this.params[param] || match)
            );
        },
    },
});
</script>

<style lang="scss" scoped></style>
