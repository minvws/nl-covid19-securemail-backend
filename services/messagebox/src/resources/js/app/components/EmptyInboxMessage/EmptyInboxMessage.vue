<template>
    <BCard class="py-3" body-class="text-center mt-2">
        <div class="row">
            <div class="col-12 col-lg-8 offset-lg-2 col-xl-6 offset-xl-3">
                <SvgVue class="mb-4" icon="message_logo" width="104px" focusable="false" aria-hidden="true" />
                <FocusedHeader class="mt-3 mb-2">{{ $t('components.emptyInboxMessage.title') }}</FocusedHeader>
                <Paragraphs :data="Object.values($t('components.emptyInboxMessage.paragraphs'))" />
            </div>
        </div>
    </BCard>
</template>
<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import Paragraphs from '@/app/components/Paragraphs/Paragraphs.vue';

interface Props {}

interface Data {}

interface Computed {}

interface Methods {}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { FocusedHeader, Paragraphs },
    name: 'EmptyInboxMessage',
});
</script>
