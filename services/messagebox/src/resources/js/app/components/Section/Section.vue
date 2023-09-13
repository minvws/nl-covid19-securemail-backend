<template>
    <section>
        <h2 v-if="section.heading">{{ section.heading }}</h2>

        <Paragraphs data-testid="paragraphs" v-if="section.paragraphs" :data="section.paragraphs" />

        <p data-testid="listIntro" v-if="section.listIntro" v-html="section.listIntro" />
        <ul data-testid="listItems" v-if="section.listItems">
            <li v-for="(item, $index) in section.listItems" :key="`listItem_${$index}`" v-html="item" />
        </ul>
        <p data-testid="listOutro" v-if="section.listOutro" v-html="section.listOutro" />
    </section>
</template>

<script lang="ts">
import Paragraphs from '../Paragraphs/Paragraphs.vue';
import { defineComponent } from '@/typeHelpers';

interface Props {
    section: {
        heading?: string;
        paragraphs?: string[];
        listIntro?: string;
        listItems?: string[];
        listOutro?: string;
    };
}

interface Data {}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { Paragraphs },
    name: 'Section',
    props: {
        section: {
            type: Object,
            required: true,
        },
    },
    data() {
        return {};
    },
    computed: {},
    methods: {},
});
</script>

<style lang="scss" scoped>
section {
    word-break: break-word;
}
</style>
