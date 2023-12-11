<template>
    <BButton variant="link" @click="$emit('click')">
        <SvgVue icon="arrow-left" width="24px" class="mr-2" focusable="false" aria-hidden="true" />
        {{ $t('components.backButton.previous') }}
    </BButton>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';

interface Props {}

interface Data {}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    name: 'BackButton',
    props: {},
    data() {
        return {};
    },
    computed: {},
    methods: {},
});
</script>
