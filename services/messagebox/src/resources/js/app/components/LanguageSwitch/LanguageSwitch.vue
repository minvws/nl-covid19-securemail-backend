<template>
    <BDropdown @shown="focus" ref="dropdown" :toggle-attrs="{ 'aria-controls': dropdownId }" right variant="link">
        <template #button-content>
            <span class="sr-only">{{ $t('components.languageSwitch.language') }}:&nbsp;</span>
            {{ $t(`app.locales.${language}`) }}
            <span class="sr-only">, {{ $t('components.languageSwitch.switchLanguage').toLowerCase() }}</span>
        </template>
        <BDropdownItem
            v-for="languageOption in languageOptions"
            :key="languageOption"
            @click="setLanguage(languageOption)"
            :aria-current="languageOption === language"
            :lang="languageOption"
        >
            {{ $t(`app.locales.${languageOption}`) }}
        </BDropdownItem>
    </BDropdown>
</template>

<script lang="ts">
import { StoreType } from '@/store/storeType';
import { defineComponent } from '@/typeHelpers';
import { mapActions, mapState } from 'vuex';
import { Locales } from '@/i18n/locales';
import { SessionStoreAction } from '@/store/sessionStore';
import { BDropdown } from 'bootstrap-vue';

interface Props {}

interface Data {
    dropdownId: string;
}

interface Computed {
    language: Locales;
    languageOptions: Locales[];
}

interface Methods {
    focus(): void;
    setLanguage(language: Locales): void;
}

interface AdditionalProps {
    $refs: {
        dropdown: BDropdown & { $refs: { menu: HTMLUListElement } };
    };
}

export default defineComponent<Data, Methods, Computed, Props, AdditionalProps>({
    name: 'LanguageSwitch',
    computed: {
        ...mapState(StoreType.SESSION, ['language']),
        languageOptions() {
            return Object.values(Locales);
        },
    },
    data() {
        return {
            dropdownId: 'language-dropdown',
        };
    },
    mounted() {
        this.$refs.dropdown?.$refs.menu?.setAttribute('id', this.dropdownId);
    },
    methods: {
        ...mapActions(StoreType.SESSION, {
            setLanguage: SessionStoreAction.SET_LANGUAGE,
        }),
        focus() {
            this.$refs.dropdown?.$el.querySelector<HTMLAnchorElement>('a:not([aria-current])')?.focus();
        },
    },
});
</script>

<style lang="scss" scoped></style>
