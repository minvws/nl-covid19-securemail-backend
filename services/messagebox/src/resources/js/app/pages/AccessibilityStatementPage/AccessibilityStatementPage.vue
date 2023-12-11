<template>
    <div>
        <FocusedHeader class="mb-2">{{ $t('pages.accessibilityStatementPage.title') }}</FocusedHeader>
        <Paragraphs :data="Object.values($t('pages.accessibilityStatementPage.introParagraphs'))" />
        <Section
            v-for="(section, $index) in Object.values($t('pages.accessibilityStatementPage.sections'))"
            :key="`section_${$index}`"
            :section="section"
        />
    </div>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import Section from '@/app/components/Section/Section.vue';
import Paragraphs from '@/app/components/Paragraphs/Paragraphs.vue';

interface Props {}

interface Data {}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { FocusedHeader, Section, Paragraphs },
    name: 'AccessibilityStatementPage',
});
</script>

<style lang="scss" scoped></style>
