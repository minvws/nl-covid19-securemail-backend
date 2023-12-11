<template>
    <div>
        <FocusedHeader class="mb-2">{{ $t('pages.privacyStatementPage.title') }}</FocusedHeader>
        <Paragraphs :data="Object.values($t('pages.privacyStatementPage.introParagraphs'))" />
        <Section
            v-for="(section, $index) in Object.values($t('pages.privacyStatementPage.sections'))"
            :key="`section_${$index}`"
            :section="section"
        />
    </div>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import Paragraphs from '@/app/components/Paragraphs/Paragraphs.vue';
import Section from '@/app/components/Section/Section.vue';

interface Props {}

interface Data {}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { FocusedHeader, Paragraphs, Section },
    name: 'PrivacyStatementPage',
    props: {},
    data() {
        return {};
    },
    computed: {},
    methods: {},
});
</script>

<style lang="scss" scoped></style>
