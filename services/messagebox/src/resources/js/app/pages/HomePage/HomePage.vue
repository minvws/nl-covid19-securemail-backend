<template>
    <div>
        <div class="text-center mb-4">
            <SvgVue icon="message_logo" width="104px" focusable="false" aria-hidden="true" />
        </div>
        <FocusedHeader class="mt-3 mb-2">{{ $t('pages.homePage.title') }}</FocusedHeader>
        <Paragraphs :data="Object.values($t('pages.homePage.paragraphs'))" />
        <DigidLoginButton class="mt-4 mb-3" loginUrl="/auth/digid" />
        <BButton @click="goToTokenPage" class="px-0" variant="link">
            {{ $t('pages.homePage.tokenButton') }}
            <SvgVue class="mr-1" icon="arrow-right" width="20px" focusable="false" aria-hidden="true" />
        </BButton>
    </div>
</template>
<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import DigidLoginButton from '@/app/components/DigidLoginButton/DigidLoginButton.vue';
import Paragraphs from '@/app/components/Paragraphs/Paragraphs.vue';
import FocusedHeader from '@/app/components/FocusedHeader/FocusedHeader.vue';
import { StoreType } from '@/store/storeType';
import { SessionStoreAction } from '@/store/sessionStore';
import { mapActions } from 'vuex';

interface Props {}

interface Data {}

interface Computed {}

interface Methods {
    clearDigidResponse: () => Promise<void>;
    goToTokenPage: () => Promise<void>;
}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { DigidLoginButton, Paragraphs, FocusedHeader },
    name: 'HomePage',
    methods: {
        ...mapActions(StoreType.SESSION, {
            clearDigidResponse: SessionStoreAction.CLEAR_DIGID_RESPONSE,
        }),
        async goToTokenPage() {
            await this.clearDigidResponse();
            this.$router.push({ name: 'auth.token' });
        },
    },
});
</script>

<style lang="scss" scoped></style>
