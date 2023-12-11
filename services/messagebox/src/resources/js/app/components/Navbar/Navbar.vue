<template>
    <header>
        <BNavbar id="header" toggleable="lg" role="navigation">
            <div class="container-fluid">
                <BNavbarNav>
                    <li :class="[isHome ? 'd-flex' : 'd-none d-lg-flex']" data-testid="brand-container">
                        <BNavbarBrand :href="homeHref" class="mb-0">
                            <SvgVue icon="logo" height="32px" role="img" :aria-label="$t('components.navbar.logo')" />
                        </BNavbarBrand>
                    </li>
                    <li v-if="hasBackButton" class="d-flex d-lg-none">
                        <BackButton @click="$emit('back')" class="pl-0" />
                    </li>
                    <li class="title d-none d-lg-flex flex-fill justify-content-center">
                        {{ $t('components.navbar.title') }}
                    </li>
                    <li class="d-flex" data-testid="navbar-logout-container">
                        <BButton
                            v-if="isLoggedIn"
                            variant="link"
                            class="order-0"
                            :class="{ 'd-none d-lg-flex': hasBackButton }"
                            @click="onLogout"
                        >
                            <SvgVue aria-hidden="true" class="mr-1" focusable="false" icon="logout" height="24px" />
                            {{ $t('components.navbar.logout') }}
                        </BButton>
                    </li>
                    <li class="d-flex justify-content-end" data-testid="navbar-right-container">
                        <LanguageSwitch class="order-1" />
                    </li>
                </BNavbarNav>
            </div>
        </BNavbar>
    </header>
</template>

<script lang="ts">
import { defineComponent } from '@/typeHelpers';
import LanguageSwitch from '../LanguageSwitch/LanguageSwitch.vue';
import BackButton from '../BackButton/BackButton.vue';
import { mapActions, mapState } from 'vuex';
import { StoreType } from '@/store/storeType';
import { SessionStoreAction } from '@/store/sessionStore';
import i18n from '@/i18n';

interface Props {
    hasBackButton: boolean;
    homeHref: string;
    isHome: boolean;
}

interface Data {}

interface Computed {
    isLoggedIn: boolean;
}

interface Methods {
    logout: () => Promise<void>;
    onLogout: () => void;
}

export default defineComponent<Data, Methods, Computed, Props>({
    components: { LanguageSwitch, BackButton },
    name: 'Navbar',
    props: {
        hasBackButton: {
            type: Boolean,
            default: false,
        },
        isHome: {
            type: Boolean,
            default: false,
        },
        homeHref: {
            type: String,
            default: '/',
        },
    },
    computed: {
        ...mapState(StoreType.SESSION, ['isLoggedIn']),
    },
    methods: {
        ...mapActions(StoreType.SESSION, {
            logout: SessionStoreAction.LOGOUT,
        }),
        onLogout() {
            this.$modal.show({
                hideHeaderClose: true,
                title: i18n.t('components.navbar.logoutModal.title').toString(),
                okTitle: i18n.t('components.navbar.logoutModal.buttonOk').toString(),
                cancelTitle: i18n.t('components.navbar.logoutModal.buttonCancel').toString(),
                onConfirm: async () => {
                    await this.logout();
                    // This needs to be a hard redirect because of csrf token
                    window.location.href = this.$router.resolve({ name: 'home' }).href;
                },
            });
        },
    },
});
</script>

<style lang="scss" scoped></style>
